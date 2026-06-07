<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

class ProductPaymentController extends Controller
{
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

        MidtransConfig::$serverKey = (string) config('services.midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('services.midtrans.is_production');
        MidtransConfig::$isSanitized = (bool) config('services.midtrans.is_sanitized', true);
        MidtransConfig::$is3ds = (bool) config('services.midtrans.is_3ds', true);

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

    public function finish(Request $request, string $kode)
    {
        $order = ProductOrder::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with('items')
            ->firstOrFail();

        $status = $request->input('transaction_status');

        if (in_array($status, ['settlement', 'capture'], true) && $order->status === 'pending') {
            DB::transaction(function () use ($order, $request) {
                $order->update(['status' => 'paid']);
                $order->pembayaran()->update([
                    'midtrans_transaction_id' => $request->input('transaction_id'),
                    'metode' => $request->input('payment_type'),
                    'status' => 'success',
                    'paid_at' => now(),
                ]);

                foreach ($order->items as $item) {
                    Product::where('id_product', $item->id_product)->decrement('stok', $item->qty);
                    Product::where('id_product', $item->id_product)->increment('total_sold', $item->qty);
                }
            });
        }

        return redirect()->route('shop.order.show', $order->kode_order)
            ->with('success', 'Terima kasih! Pesanan kamu sedang diproses.');
    }
}
