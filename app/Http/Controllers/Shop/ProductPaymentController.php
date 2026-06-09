<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use App\Services\ProductPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;

class ProductPaymentController extends Controller
{
    public function __construct(private ProductPaymentService $payments) {}

    public function index(string $kode)
    {
        $order = ProductOrder::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with(['items', 'pembayaran', 'address'])
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return redirect()->route('shop.order.show', $order->kode_order);
        }

        return view('shop.payment', [
            'order' => $order,
            'clientKey' => config('services.midtrans.client_key'),
        ]);
    }

    public function token(Request $request, string $kode)
    {
        $order = ProductOrder::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with(['items', 'user', 'pembayaran', 'address'])
            ->firstOrFail();

        if (! config('services.midtrans.server_key')) {
            return response()->json(['error' => 'Midtrans belum dikonfigurasi (set MIDTRANS_SERVER_KEY di .env).'], 422);
        }

        $this->configureMidtrans();

        $midOrderId = 'SHOP-'.$order->kode_order;

        $params = [
            'transaction_details' => [
                'order_id' => $midOrderId,
                'gross_amount' => (int) $order->grand_total,
            ],
            'customer_details' => [
                'first_name' => $order->user->first_name,
                'last_name' => $order->user->last_name,
                'email' => $order->user->email,
                'phone' => $order->user->phone_number ?? '',
            ],
            'item_details' => $order->items->map(fn ($item) => [
                'id' => (string) $item->id_product,
                'price' => (int) $item->harga_satuan,
                'quantity' => (int) $item->qty,
                'name' => mb_substr($item->nama_produk, 0, 50),
            ])->values()->all(),
        ];

        // Tambahkan ongkir & potongan sebagai item agar gross_amount cocok
        if ($order->biaya_kirim > 0) {
            $params['item_details'][] = ['id' => 'SHIP', 'price' => (int) $order->biaya_kirim, 'quantity' => 1, 'name' => 'Ongkos Kirim'];
        }
        $potongan = (int) ($order->total_diskon + $order->potongan_poin);
        if ($potongan > 0) {
            $params['item_details'][] = ['id' => 'DISC', 'price' => -$potongan, 'quantity' => 1, 'name' => 'Diskon'];
        }

        try {
            $snapToken = Snap::getSnapToken($params);
            $order->pembayaran()->update([
                'snap_token' => $snapToken,
                'midtrans_order_id' => $midOrderId,
            ]);

            return response()->json(['snap_token' => $snapToken]);
        } catch (\Throwable $e) {
            Log::error('Product Midtrans Snap token error', ['kode' => $kode, 'msg' => $e->getMessage()]);

            return response()->json(['error' => 'Gagal membuat token pembayaran.'], 500);
        }
    }

    /**
     * Called by the browser after Snap's onSuccess/onPending callback.
     *
     * SECURITY: the client-supplied transaction_status is IGNORED. We fetch the
     * authoritative status from Midtrans server-side (mirroring the salon flow)
     * before touching the order. Settlement is delegated to a shared service so
     * this path and the webhook can never drift apart.
     */
    public function finish(Request $request, string $kode)
    {
        $order = ProductOrder::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with('pembayaran')
            ->firstOrFail();

        // Already settled — just show the order.
        if ($order->status !== 'pending') {
            return redirect()->route('shop.order.show', $order->kode_order);
        }

        if (! config('services.midtrans.server_key')) {
            return redirect()->route('shop.order.show', $order->kode_order)
                ->with('info', 'Pembayaran sedang diverifikasi.');
        }

        $this->configureMidtrans();

        $midOrderId = $order->pembayaran?->midtrans_order_id ?? ('SHOP-'.$order->kode_order);

        try {
            $status = MidtransTransaction::status($midOrderId);
        } catch (\Throwable $e) {
            // Do NOT trust the client. Leave as pending — the webhook will settle.
            Log::error('Shop Midtrans status check failed', ['kode' => $kode, 'msg' => $e->getMessage()]);

            return redirect()->route('shop.order.show', $order->kode_order)
                ->with('info', 'Pembayaran sedang diverifikasi. Status akan diperbarui otomatis.');
        }

        $this->payments->settle(
            $order,
            (string) ($status->transaction_status ?? 'pending'),
            $status->fraud_status ?? null,
            [
                'payment_type' => $status->payment_type ?? null,
                'transaction_id' => $status->transaction_id ?? null,
                'gross_amount' => $status->gross_amount ?? null,
            ]
        );

        $fresh = $order->fresh();
        if ($fresh->status === 'paid') {
            return redirect()->route('shop.order.show', $order->kode_order)
                ->with('success', 'Terima kasih! Pesanan kamu sedang diproses.');
        }

        return redirect()->route('shop.order.show', $order->kode_order)
            ->with('info', 'Menunggu konfirmasi pembayaran.');
    }

    private function configureMidtrans(): void
    {
        MidtransConfig::$serverKey = (string) config('services.midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('services.midtrans.is_production');
        MidtransConfig::$isSanitized = (bool) config('services.midtrans.is_sanitized', true);
        MidtransConfig::$is3ds = (bool) config('services.midtrans.is_3ds', true);
    }
}
