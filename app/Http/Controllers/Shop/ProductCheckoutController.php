<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use App\Models\ProductPembayaran;
use App\Models\Promo;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductCheckoutController extends Controller
{
    public function index()
    {
        $cartItems = Cart::where('id_user', auth()->id())->with('product')->get()
            ->filter(fn ($i) => $i->product !== null);

        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.cart')->with('error', 'Keranjang kosong.');
        }

        $addresses = UserAddress::where('id_user', auth()->id())->orderByDesc('is_default')->get();
        $subtotal = $cartItems->sum(fn ($i) => $i->product->harga * $i->qty);
        $totalBerat = max(100, $cartItems->sum(fn ($i) => ($i->product->berat_gram ?? 200) * $i->qty));
        $threshold = (int) config('ongkir.free_ongkir_threshold', 500000);
        $userPoint = auth()->user()->points;

        return view('shop.checkout', compact('cartItems', 'addresses', 'subtotal', 'totalBerat', 'threshold', 'userPoint'));
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:50',
            'nama_penerima' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'alamat_lengkap' => 'required|string|max:500',
            'kota' => 'required|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kode_pos' => 'required|string|max:10',
        ]);

        $isFirst = ! UserAddress::where('id_user', auth()->id())->exists();

        UserAddress::create([
            ...$data,
            'id_user' => auth()->id(),
            'kota_id' => $request->kota_id ?? '',
            'provinsi' => $data['provinsi'] ?? '',
            'provinsi_id' => $request->provinsi_id ?? '',
            'is_default' => $isFirst,
        ]);

        return back()->with('success', 'Alamat berhasil disimpan.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_address' => 'required|exists:user_addresses,id_address',
            'kurir' => 'required|string|max:30',
            'layanan_kirim' => 'required|string|max:30',
            'biaya_kirim' => 'required|numeric|min:0',
            'estimasi_tiba' => 'nullable|string|max:30',
            'promo_code' => 'nullable|string|max:50',
            'poin_digunakan' => 'nullable|integer|min:0',
            'catatan' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $cartItems = Cart::where('id_user', $user->id_user)->with('product')->get()
            ->filter(fn ($i) => $i->product !== null);

        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.cart')->with('error', 'Keranjang kosong.');
        }

        // Pastikan alamat milik user
        $address = UserAddress::where('id_address', $request->id_address)
            ->where('id_user', $user->id_user)->firstOrFail();

        $subtotal = $cartItems->sum(fn ($i) => $i->product->harga * $i->qty);
        $biayaKirim = (float) $request->biaya_kirim;

        // Free ongkir: (a) subtotal >= threshold, atau (b) benefit tier
        if ($subtotal >= config('ongkir.free_ongkir_threshold', 500000) || $this->tierFreeOngkir($user)) {
            $biayaKirim = 0;
        }

        // Promo (reuse tabel promo V1)
        [$idPromo, $totalDiskon] = $this->applyPromo($request->promo_code, $subtotal);

        // Poin (PointService dipakai penuh di Phase 3B; di sini hanya hitung potongan jika saldo cukup)
        $poinDigunakan = 0;
        $potonganPoin = 0;
        if ($request->poin_digunakan && ($up = $user->points) && $up->saldo >= (int) $request->poin_digunakan) {
            $poinDigunakan = (int) $request->poin_digunakan;
            $potonganPoin = $poinDigunakan * 1000; // 1 poin = Rp 1.000
        }

        $grandTotal = max(0, $subtotal + $biayaKirim - $totalDiskon - $potonganPoin);

        $order = DB::transaction(function () use (
            $user, $address, $idPromo, $subtotal, $biayaKirim, $totalDiskon,
            $poinDigunakan, $potonganPoin, $grandTotal, $request, $cartItems
        ) {
            do {
                $kode = 'VYG-S-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
            } while (ProductOrder::where('kode_order', $kode)->exists());

            $order = ProductOrder::create([
                'id_user' => $user->id_user,
                'id_address' => $address->id_address,
                'id_promo' => $idPromo,
                'kode_order' => $kode,
                'subtotal' => $subtotal,
                'biaya_kirim' => $biayaKirim,
                'total_diskon' => $totalDiskon,
                'poin_digunakan' => $poinDigunakan,
                'potongan_poin' => $potonganPoin,
                'grand_total' => $grandTotal,
                'kurir' => $request->kurir,
                'layanan_kirim' => $request->layanan_kirim,
                'estimasi_tiba' => $request->estimasi_tiba,
                'status' => 'pending',
                'catatan' => $request->catatan,
            ]);

            foreach ($cartItems as $item) {
                ProductOrderItem::create([
                    'id_product_order' => $order->id_product_order,
                    'id_product' => $item->id_product,
                    'nama_produk' => $item->product->nama,
                    'qty' => $item->qty,
                    'harga_satuan' => $item->product->harga,
                    'berat_gram' => $item->product->berat_gram ?? 200,
                    'subtotal' => $item->product->harga * $item->qty,
                ]);
            }

            ProductPembayaran::create([
                'id_product_order' => $order->id_product_order,
                'id_user' => $user->id_user,
                'jumlah' => $grandTotal,
                'status' => 'pending',
            ]);

            // Potong saldo poin + catat transaksi (Modul 3 — Empty Return reward)
            if ($poinDigunakan > 0) {
                PointService::spend($user->id_user, $poinDigunakan, $order->id_product_order);
            }

            Cart::where('id_user', $user->id_user)->delete();

            return $order;
        });

        return redirect()->route('shop.order.payment', $order->kode_order);
    }

    /**
     * Benefit free ongkir berdasarkan tier poin:
     * Gold = unlimited, Silver = 1x per bulan.
     */
    private function tierFreeOngkir(User $user): bool
    {
        $tier = $user->points?->tier;

        if ($tier === 'gold') {
            return true;
        }

        if ($tier === 'silver') {
            $usedThisMonth = ProductOrder::where('id_user', $user->id_user)
                ->where('biaya_kirim', 0)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->exists();

            return ! $usedThisMonth;
        }

        return false;
    }

    /**
     * @return array{0: ?int, 1: float} [id_promo, total_diskon]
     */
    private function applyPromo(?string $code, float $subtotal): array
    {
        if (! $code) {
            return [null, 0.0];
        }

        $promo = Promo::where('kode_promo', $code)->where('status', 'active')->first();

        if (! $promo) {
            return [null, 0.0];
        }
        if ($promo->time_start && now()->lt($promo->time_start)) {
            return [null, 0.0];
        }
        if ($promo->time_expired && now()->gt($promo->time_expired)) {
            return [null, 0.0];
        }
        if ($promo->min_transaksi && $subtotal < $promo->min_transaksi) {
            return [null, 0.0];
        }

        $diskon = $promo->tipe_promo === 'percentage'
            ? $subtotal * ((float) $promo->diskon / 100)
            : (float) $promo->diskon;

        if ($promo->diskon_max && $diskon > $promo->diskon_max) {
            $diskon = (float) $promo->diskon_max;
        }

        return [$promo->id_promo, round($diskon, 2)];
    }
}
