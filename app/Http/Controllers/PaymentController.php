<?php

namespace App\Http\Controllers;

use App\Constants\OrderStatus;
use App\Models\Order;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Notification as MidtransNotification;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;

/**
 * Midtrans Snap payment flow.
 *
 * Sequence:
 *   1. BookingController::store creates an `order` (status=pending) and redirects
 *      the user to /booking/{kode}/payment.
 *   2. show() renders a page that loads Midtrans Snap JS and POSTs to
 *      /payment/token to fetch a snap_token.
 *   3. createSnapToken() asks Midtrans for a token and persists/updates the
 *      `pembayaran` row with snap_token + status=pending.
 *   4. Frontend calls window.snap.pay(token, callbacks).
 *   5. On success, frontend calls finish() which server-side verifies the
 *      transaction via Midtrans API and updates pembayaran + order accordingly.
 *   6. Midtrans also posts to /midtrans/webhook as a safety net.
 *   7. On success, the user is redirected to /booking/{kode}/konfirmasi.
 */
class PaymentController extends Controller
{
    public function __construct()
    {
        MidtransConfig::$serverKey    = (string) config('services.midtrans.server_key');
        MidtransConfig::$isProduction = (bool)   config('services.midtrans.is_production');
        MidtransConfig::$isSanitized  = (bool)   config('services.midtrans.is_sanitized');
        MidtransConfig::$is3ds        = (bool)   config('services.midtrans.is_3ds');
    }

    /**
     * Render the Snap pop-up host page.
     */
    public function show(string $kode)
    {
        $order = $this->resolvePendingOrder($kode);

        return view('booking.payment', [
            'order'      => $order,
            'clientKey'  => config('services.midtrans.client_key'),
            'production' => (bool) config('services.midtrans.is_production'),
        ]);
    }

    /**
     * Generate a fresh Snap token for the order. Idempotent — repeated calls
     * for the same order get a new token; we update `pembayaran.snap_token`.
     *
     * If Midtrans rejects the order_id (already used from a previous attempt),
     * we append a retry suffix so the user can complete payment without
     * creating a new booking.
     */
    public function createSnapToken(Request $request, string $kode)
    {
        $order = $this->resolvePendingOrder($kode);

        if (! config('services.midtrans.server_key')) {
            return response()->json([
                'error' => 'Midtrans is not configured. Set MIDTRANS_SERVER_KEY in .env.',
            ], 503);
        }

        $user = $order->user ?? auth()->user();

        // Try with the original order code first; on "order_id already taken"
        // retry once with a timestamp suffix so Midtrans treats it as new.
        $midtransOrderId = $order->kode_order;
        $snapToken = null;
        $lastError = null;

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $params = [
                'transaction_details' => [
                    'order_id'     => $midtransOrderId,
                    'gross_amount' => $this->convertGbpToIdr((float) $order->total_pembayaran),
                ],
                'customer_details' => [
                    'first_name' => $user?->first_name ?? 'Guest',
                    'last_name'  => $user?->last_name ?? '',
                    'email'      => $user?->email ?? 'guest@viygo.local',
                    'phone'      => $user?->phone_number ?? '',
                ],
                'item_details' => $this->itemDetails($order),
            ];

            try {
                $snapToken = Snap::getSnapToken($params);
                break; // Success — exit the loop.
            } catch (\Throwable $e) {
                $lastError = $e;

                // If Midtrans says order_id is taken, retry with a suffix.
                if ($attempt === 0 && str_contains($e->getMessage(), 'order_id')) {
                    $midtransOrderId = $order->kode_order . '-R' . time();
                    Log::info('Midtrans order_id conflict, retrying', [
                        'original' => $order->kode_order,
                        'retry_id' => $midtransOrderId,
                    ]);
                    continue;
                }

                Log::error('Midtrans Snap token error', [
                    'order' => $order->kode_order,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => 'Could not contact payment gateway. Try again in a moment.',
                ], 502);
            }
        }

        if (! $snapToken) {
            Log::error('Midtrans Snap token error after retries', [
                'order' => $order->kode_order,
                'error' => $lastError?->getMessage(),
            ]);

            return response()->json([
                'error' => 'Could not contact payment gateway. Try again in a moment.',
            ], 502);
        }

        // Cache the token on `pembayaran` so the webhook can correlate easily.
        $payment = Pembayaran::updateOrCreate(
            ['id_order' => $order->id_order],
            [
                'id_user'           => $order->id_user,
                'metode_pembayaran' => 'midtrans_snap',
                'snap_token'        => $snapToken,
                'jumlah_bayar'      => $order->total_pembayaran,
                'status_pembayaran' => 'pending',
                'midtrans_order_id' => $midtransOrderId,
            ],
        );

        return response()->json([
            'snap_token' => $snapToken,
            'payment_id' => $payment->id_pembayaran,
        ]);
    }

    /**
     * Called by the frontend after Snap's onSuccess callback.
     *
     * Instead of relying solely on the webhook (which may never arrive in dev),
     * we server-side verify the transaction status via Midtrans API and update
     * the order + pembayaran accordingly.
     */
    public function finish(Request $request, string $kode)
    {
        $order = Order::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with('pembayaran')
            ->firstOrFail();

        // If already confirmed/success, just acknowledge.
        if (in_array($order->status, ['confirmed', 'success'])) {
            return response()->json(['status' => $order->status]);
        }

        // Look up the midtrans order ID from pembayaran (may have -R suffix).
        $payment = $order->pembayaran;
        $midtransOrderId = $payment?->midtrans_order_id ?? $order->kode_order;

        try {
            $status = MidtransTransaction::status($midtransOrderId);
        } catch (\Throwable $e) {
            Log::error('Midtrans status check failed', [
                'order'             => $order->kode_order,
                'midtrans_order_id' => $midtransOrderId,
                'error'             => $e->getMessage(),
            ]);

            // Even if the API check fails, we can try using the raw result
            // from the Snap callback. But to be safe, we still mark it.
            return response()->json([
                'status' => 'pending',
                'error'  => 'Could not verify payment status. The webhook will update it shortly.',
            ], 200);
        }

        $transactionStatus = $status->transaction_status ?? 'unknown';
        $fraudStatus       = $status->fraud_status ?? null;

        Log::info('Midtrans finish check', [
            'order'              => $order->kode_order,
            'midtrans_order_id'  => $midtransOrderId,
            'transaction_status' => $transactionStatus,
            'fraud_status'       => $fraudStatus,
        ]);

        DB::transaction(function () use ($order, $status, $transactionStatus, $fraudStatus) {
            $order->lockForUpdate();

            $payment = Pembayaran::firstOrNew(['id_order' => $order->id_order]);

            $payment->id_user           = $order->id_user;
            $payment->metode_pembayaran = (string) ($status->payment_type ?? 'midtrans_snap');
            $payment->id_transaksi      = (string) ($status->transaction_id ?? '');
            $payment->jumlah_bayar      = (float) ($status->gross_amount ?? $order->total_pembayaran);

            switch ($transactionStatus) {
                case 'capture':
                    if ($fraudStatus === 'challenge') {
                        $payment->status_pembayaran = 'pending';
                    } else {
                        $payment->status_pembayaran = 'completed';
                        $payment->tanggal_bayar     = now();
                        $order->status              = 'confirmed';
                    }
                    break;

                case 'settlement':
                    $payment->status_pembayaran = 'completed';
                    $payment->tanggal_bayar     = now();
                    $order->status              = 'confirmed';
                    break;

                case 'pending':
                    $payment->status_pembayaran = 'pending';
                    break;

                case 'deny':
                case 'expire':
                case 'cancel':
                case 'failure':
                    $payment->status_pembayaran = 'failed';
                    break;
            }

            $payment->save();
            $order->save();
        });

        return response()->json(['status' => $order->fresh()->status]);
    }

    /**
     * Handle Midtrans server-to-server notification.
     *
     * The Notification class re-fetches the transaction from Midtrans using
     * the server_key, which both validates the request and gives us the
     * authoritative status — so we don't trust the POST body directly.
     */
    public function webhook(Request $request)
    {
        Log::info('Midtrans webhook received', [
            'ip'   => $request->ip(),
            'body' => $request->all(),
        ]);

        try {
            $notification = new MidtransNotification();
        } catch (\Throwable $e) {
            Log::error('Midtrans webhook parse error', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'invalid notification'], 400);
        }

        $orderCode    = $notification->order_id;
        $statusCode   = $notification->status_code;
        $grossAmount  = $notification->gross_amount;
        $signatureKey = $notification->signature_key;

        // Belt-and-braces signature check — Notification already fetches
        // from Midtrans, but we double-check the SHA512 from the payload
        // so spoofed posts can't even reach our DB writes.
        $expected = hash('sha512',
            $orderCode . $statusCode . $grossAmount . config('services.midtrans.server_key')
        );

        if (! hash_equals($expected, (string) $signatureKey)) {
            Log::warning('Midtrans webhook signature mismatch', ['order' => $orderCode]);

            return response()->json(['message' => 'invalid signature'], 403);
        }

        // The Midtrans order_id may have a -R suffix from retry.
        // Strip it to find our actual kode_order.
        $actualOrderCode = preg_replace('/-R\d+$/', '', $orderCode);

        // Quick existence check — no lock yet (lock is inside the transaction).
        if (! Order::where('kode_order', $actualOrderCode)->exists()) {
            Log::warning('Midtrans webhook: order not found', ['order_id' => $orderCode, 'parsed' => $actualOrderCode]);
            return response()->json(['message' => 'order not found'], 404);
        }

        $transactionStatus = $notification->transaction_status;
        $fraudStatus       = $notification->fraud_status ?? null;

        Log::info('Midtrans webhook processing', [
            'order_id'           => $orderCode,
            'actual_order'       => $actualOrderCode,
            'transaction_status' => $transactionStatus,
            'fraud_status'       => $fraudStatus,
        ]);

        DB::transaction(function () use ($actualOrderCode, $notification, $transactionStatus, $fraudStatus) {
            // Re-fetch with pessimistic lock so concurrent webhook deliveries
            // for the same order are serialised — only one thread proceeds.
            $order = Order::where('kode_order', $actualOrderCode)->lockForUpdate()->firstOrFail();

            // If already confirmed/success, skip — don't downgrade status.
            if (in_array($order->status, ['confirmed', 'success'])) {
                Log::info('Midtrans webhook: order already finalized', [
                    'order'  => $actualOrderCode,
                    'status' => $order->status,
                ]);
                return;
            }

            $payment = Pembayaran::firstOrNew(['id_order' => $order->id_order]);

            $payment->id_user           = $order->id_user;
            $payment->metode_pembayaran = (string) ($notification->payment_type ?? 'midtrans_snap');
            $payment->id_transaksi      = (string) $notification->transaction_id;
            $payment->jumlah_bayar      = (float) $notification->gross_amount;
            $payment->raw_response      = (array) $notification->getResponse();

            // See https://docs.midtrans.com/docs/https-notification-webhooks
            switch ($transactionStatus) {
                case 'capture':
                    if ($fraudStatus === 'challenge') {
                        $payment->status_pembayaran = 'pending';
                    } else {
                        $payment->status_pembayaran = 'completed';
                        $payment->tanggal_bayar     = now();
                        $order->status              = OrderStatus::CONFIRMED;
                    }
                    break;

                case 'settlement':
                    $payment->status_pembayaran = 'completed';
                    $payment->tanggal_bayar     = now();
                    $order->status              = OrderStatus::CONFIRMED;
                    break;

                case 'pending':
                    $payment->status_pembayaran = 'pending';
                    break;

                case 'deny':
                case 'expire':
                case 'cancel':
                case 'failure':
                    $payment->status_pembayaran = 'failed';
                    // Keep order at 'pending' so the user can retry payment.
                    break;
            }

            $payment->save();
            $order->save();

            Log::info('Midtrans webhook: order updated', [
                'order'            => $actualOrderCode,
                'order_status'     => $order->status,
                'payment_status'   => $payment->status_pembayaran,
            ]);
        });

        return response()->json(['message' => 'ok']);
    }

    /**
     * Find the user's order in `pending` state. 404 otherwise — done is done.
     */
    protected function resolvePendingOrder(string $kode): Order
    {
        return Order::query()
            ->where('id_user', auth()->id())
            ->where('kode_order', $kode)
            ->where('status', OrderStatus::PENDING)
            ->with(['user', 'salon', 'details.service'])
            ->firstOrFail();
    }

    /**
     * Convert GBP to IDR for Midtrans.
     */
    protected function convertGbpToIdr(float $gbpAmount): int
    {
        // 1 GBP = ~20,000 IDR (example fixed rate)
        $exchangeRate = (float) config('services.midtrans.exchange_rate', 20000);
        return (int) round($gbpAmount * $exchangeRate);
    }

    /**
     * Map the order's services into Midtrans `item_details` rows.
     */
    protected function itemDetails(Order $order): array
    {
        $items = [];

        foreach ($order->details as $detail) {
            $items[] = [
                'id'       => 'SVC-' . $detail->id_service,
                'name'     => substr($detail->service?->nama ?? 'Service', 0, 50),
                'price'    => $this->convertGbpToIdr((float) $detail->harga_at_order),
                'quantity' => 1,
            ];
        }

        // Reconcile rounding so the sum equals gross_amount exactly.
        $sum = array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $items));
        $grossAmountIdr = $this->convertGbpToIdr((float) $order->total_pembayaran);
        $diff = $grossAmountIdr - $sum;

        if ($diff !== 0) {
            $items[] = [
                'id'       => 'ADJ',
                'name'     => $diff > 0 ? 'Booking fee' : 'Discount',
                'price'    => $diff,
                'quantity' => 1,
            ];
        }

        return $items;
    }
}
