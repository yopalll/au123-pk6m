<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Notification as MidtransNotification;
use Midtrans\Snap;

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
 *   5. Midtrans posts to /midtrans/webhook with the final transaction_status.
 *      webhook() verifies the signature, updates pembayaran + order accordingly.
 *   6. On success, the user is redirected to /booking/{kode}/konfirmasi.
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

        $params = [
            'transaction_details' => [
                'order_id'     => $order->kode_order,
                'gross_amount' => (int) round((float) $order->total_pembayaran),
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
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap token error', [
                'order' => $order->kode_order,
                'error' => $e->getMessage(),
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
            ],
        );

        return response()->json([
            'snap_token' => $snapToken,
            'payment_id' => $payment->id_pembayaran,
        ]);
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

        $order = Order::where('kode_order', $orderCode)->first();

        if (! $order) {
            return response()->json(['message' => 'order not found'], 404);
        }

        $transactionStatus = $notification->transaction_status;
        $fraudStatus       = $notification->fraud_status ?? null;

        DB::transaction(function () use ($order, $notification, $transactionStatus, $fraudStatus) {
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
                    // Keep order at 'pending' so the user can retry payment.
                    break;
            }

            $payment->save();
            $order->save();
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
            ->where('status', 'pending')
            ->with(['user', 'salon', 'details.service'])
            ->firstOrFail();
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
                'price'    => (int) round((float) $detail->harga_at_order),
                'quantity' => 1,
            ];
        }

        // Reconcile rounding so the sum equals gross_amount exactly.
        $sum = array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $items));
        $diff = (int) round((float) $order->total_pembayaran) - $sum;

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
