<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
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
        $buyNow = session(self::BUY_NOW_KEY) !== null;

        if ($buyNow) {
            $cartItems = $this->buyNowItems();

            if (! $cartItems) {
                return redirect()->route('shop.index')->with('error', 'Produk sedang tidak tersedia.');
            }
        } else {
            $cartItems = Cart::where('id_user', auth()->id())->where('selected', true)->with('product')->get()
                ->filter(fn ($i) => $i->product !== null);

            if ($cartItems->isEmpty()) {
                return redirect()->route('shop.cart')->with('error', 'Pilih produk yang ingin dibayar terlebih dahulu.');
            }
        }

        $addresses = UserAddress::where('id_user', auth()->id())->orderByDesc('is_default')->get();
        $subtotal = $cartItems->sum(fn ($i) => $i->product->harga * $i->qty);
        $threshold = (int) config('ongkir.free_ongkir_threshold', 100000);
        $userPoint = auth()->user()->points;

        [$biayaKirim, $freeOngkir] = $this->resolveOngkir(auth()->user(), $subtotal);

        return view('shop.checkout', compact('cartItems', 'addresses', 'subtotal', 'threshold', 'userPoint', 'biayaKirim', 'freeOngkir'));
    }

    /** Kunci session untuk item "Beli Langsung" (tidak masuk keranjang). */
    private const BUY_NOW_KEY = 'shop.buy_now';

    /**
     * Beli Langsung — checkout sekali jalan TANPA menyentuh keranjang.
     * Item disimpan sementara di session lalu diproses di index()/store().
     */
    public function buyNow(Request $request)
    {
        $request->validate([
            'id_product' => 'required|exists:products,id_product',
            'qty'        => 'nullable|integer|min:1|max:99',
        ]);

        $product = Product::find($request->id_product);

        if (! $product || $product->status !== 'active' || $product->stok < 1) {
            return back()->with('error', 'Produk sedang tidak tersedia.');
        }

        $qty = min((int) ($request->qty ?? 1), $product->stok);

        session([self::BUY_NOW_KEY => [
            'id_product' => $product->id_product,
            'qty'        => $qty,
        ]]);

        return redirect()->route('shop.checkout');
    }

    /**
     * Item "Beli Langsung" dari session sebagai koleksi Cart transient
     * (tidak tersimpan di DB). Null bila tidak sedang beli langsung.
     */
    private function buyNowItems(): ?\Illuminate\Support\Collection
    {
        $bn = session(self::BUY_NOW_KEY);

        if (! $bn) {
            return null;
        }

        $product = Product::find($bn['id_product']);

        if (! $product) {
            session()->forget(self::BUY_NOW_KEY);

            return null;
        }

        $item = new Cart(['id_product' => $product->id_product, 'qty' => (int) $bn['qty']]);
        $item->setRelation('product', $product);

        return collect([$item]);
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
            'promo_code' => 'nullable|string|max:50',
            'poin_digunakan' => 'nullable|integer|min:0',
            'catatan' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $isBuyNow = session(self::BUY_NOW_KEY) !== null;

        if ($isBuyNow) {
            $cartItems = $this->buyNowItems();

            if (! $cartItems) {
                return redirect()->route('shop.index')->with('error', 'Produk sedang tidak tersedia.');
            }
        } else {
            $cartItems = Cart::where('id_user', $user->id_user)->where('selected', true)->with('product')->get()
                ->filter(fn ($i) => $i->product !== null);

            if ($cartItems->isEmpty()) {
                return redirect()->route('shop.cart')->with('error', 'Pilih produk yang ingin dibayar terlebih dahulu.');
            }
        }

        // Pastikan alamat milik user
        $address = UserAddress::where('id_address', $request->id_address)
            ->where('id_user', $user->id_user)->firstOrFail();

        $subtotal = $cartItems->sum(fn ($i) => $i->product->harga * $i->qty);

        // Ongkir flat dihitung di server (tanpa API). Gratis bila subtotal >= threshold
        // atau lewat benefit tier poin. Nilai dari client diabaikan demi keamanan.
        [$biayaKirim] = $this->resolveOngkir($user, $subtotal);

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

        try {
            $order = DB::transaction(function () use (
                $user, $address, $idPromo, $subtotal, $biayaKirim, $totalDiskon,
                $poinDigunakan, $potonganPoin, $grandTotal, $request, $cartItems, $isBuyNow
            ) {
                // Validasi ketersediaan stok & status produk (lock untuk cegah race).
                foreach ($cartItems as $item) {
                    $product = Product::where('id_product', $item->id_product)->lockForUpdate()->first();
                    if (! $product || $product->status !== 'active') {
                        throw new \RuntimeException('UNAVAILABLE:'.$item->product->nama);
                    }
                    if ($product->stok < $item->qty) {
                        throw new \RuntimeException('STOCK:'.$product->nama);
                    }
                }

                // Konsumsi kuota promo (re-check di dalam transaksi, mirip booking).
                if ($idPromo) {
                    $promo = Promo::where('id_promo', $idPromo)->lockForUpdate()->first();
                    if (! $promo || $promo->isSoldOut()) {
                        throw new \RuntimeException('PROMO_EXHAUSTED:');
                    }
                    $promo->increment('used_counter');
                }

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
                    'kurir' => config('ongkir.courier', 'VIYGO'),
                    'layanan_kirim' => config('ongkir.service', 'Reguler'),
                    'estimasi_tiba' => config('ongkir.etd', '2-4 hari'),
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

                // Beli Langsung tidak menyentuh keranjang. Untuk checkout normal,
                // hanya hapus item yang di-checkout; item tak tercentang tetap ada.
                if (! $isBuyNow) {
                    Cart::where('id_user', $user->id_user)
                        ->whereIn('id_cart', $cartItems->pluck('id_cart'))
                        ->delete();
                }

                return $order;
            });
        } catch (\RuntimeException $e) {
            [$code, $detail] = array_pad(explode(':', $e->getMessage(), 2), 2, '');
            $message = match ($code) {
                'STOCK' => "Stok \"{$detail}\" tidak mencukupi.",
                'UNAVAILABLE' => "Produk \"{$detail}\" sudah tidak tersedia.",
                'PROMO_EXHAUSTED' => 'Kode promo sudah habis terpakai.',
                default => 'Gagal membuat pesanan. Silakan coba lagi.',
            };

            return redirect()->route('shop.checkout')->with('error', $message);
        }

        // Selesai: bersihkan item beli-langsung dari session.
        session()->forget(self::BUY_NOW_KEY);

        return redirect()->route('shop.order.payment', $order->kode_order);
    }

    /**
     * Hitung ongkir flat (tanpa API).
     * Gratis bila subtotal >= threshold ATAU lewat benefit tier poin.
     *
     * @return array{0: float, 1: bool} [biaya_kirim, is_free]
     */
    private function resolveOngkir(User $user, float $subtotal): array
    {
        $threshold = (int) config('ongkir.free_ongkir_threshold', 100000);
        $flat = (int) config('ongkir.flat_cost', 10000);

        $isFree = $subtotal >= $threshold || $this->tierFreeOngkir($user);

        return [$isFree ? 0.0 : (float) $flat, $isFree];
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

        // Scope byCode() = status active + belum kadaluwarsa + uppercase/trim.
        $promo = Promo::byCode($code)->first();

        // Shop hanya menerima promo platform-wide (id_salon = null);
        // promo milik salon tertentu adalah ranah booking V1.
        if (! $promo || $promo->id_salon) {
            return [null, 0.0];
        }
        if ($promo->time_start && now()->lt($promo->time_start)) {
            return [null, 0.0];
        }
        if (! $promo->meetsMinimum($subtotal)) {
            return [null, 0.0];
        }
        if ($promo->isSoldOut()) {
            return [null, 0.0];
        }

        return [$promo->id_promo, $promo->calculateDiscount($subtotal)];
    }
}
