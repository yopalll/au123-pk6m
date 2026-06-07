# Phase 2B — Modul 1: E-commerce Skincare
## Step 2.2 — 10 Sub-steps Berurutan

> **Prerequisite:** Phase 1B (Models), Phase 1C (data produk ter-seed), Phase 1D (config ongkir)  
> **Design Reference:**  
> - Shop landing (mobile): `docs_v2/design/m_j1_skincare_shop_landing/`  
> - Product detail: `docs_v2/design/j2_product_detail_midnight_renewal_serum_1/` + `_2/`  
> - Shopping bag: `docs_v2/design/j3_shopping_bag_2/`  
> - Order confirmation: `docs_v2/design/j4_order_confirmation_2/`  
> **Verifikasi akhir:** Browse → add to cart → checkout → Midtrans payment → pesanan tercatat

---

## SUB-STEP 2.2.1 — Setup Controllers & Routes

### Buat Controllers (semua di `app/Http/Controllers/Shop/`)

```
app/Http/Controllers/Shop/
├── ShopController.php           — katalog, kategori, koleksi, detail, cari
├── SkincarefinderController.php — quiz + result
├── WishlistController.php       — toggle wishlist
├── CartController.php           — CRUD cart
├── OngkirController.php         — proxy ke api.co.id
├── ProductCheckoutController.php— checkout flow
├── ProductOrderController.php   — riwayat + detail pesanan
├── ProductPaymentController.php — Midtrans untuk produk
└── ProductReviewController.php  — submit review
```

### Daftarkan Routes di `routes/web.php`

```php
// ============================================================
// SHOP — E-commerce Skincare (Public)
// ============================================================
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/',                     [ShopController::class, 'index'])->name('index');
    Route::get('/kategori/{slug}',      [ShopController::class, 'kategori'])->name('kategori');
    Route::get('/koleksi/{slug}',       [ShopController::class, 'koleksi'])->name('koleksi');
    Route::get('/produk/{slug}',        [ShopController::class, 'show'])->name('produk.show');
    Route::get('/cari',                 [ShopController::class, 'search'])->name('cari');
    Route::get('/skincare-finder',      [SkincarefinderController::class, 'index'])->name('skincareFinder');
    Route::post('/skincare-finder',     [SkincarefinderController::class, 'result'])->name('skincareFinder.result');

    // Auth required
    Route::middleware('auth')->group(function () {
        Route::get('/wishlist',             [WishlistController::class, 'index'])->name('wishlist');
        Route::post('/wishlist/toggle',     [WishlistController::class, 'toggle'])->name('wishlist.toggle');

        Route::get('/cart',                 [CartController::class, 'index'])->name('cart');
        Route::post('/cart/add',            [CartController::class, 'add'])->name('cart.add');
        Route::put('/cart/update',          [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/remove/{id}',  [CartController::class, 'remove'])->name('cart.remove');

        Route::post('/ongkir/check',        [OngkirController::class, 'check'])->name('ongkir.check');

        Route::get('/checkout',             [ProductCheckoutController::class, 'index'])->name('checkout');
        Route::post('/checkout',            [ProductCheckoutController::class, 'store'])->name('checkout.store');

        Route::get('/pesanan',              [ProductOrderController::class, 'index'])->name('pesanan.index');
        Route::get('/order/{kode}',         [ProductOrderController::class, 'show'])->name('order.show');
        Route::get('/order/{kode}/invoice', [ProductOrderController::class, 'invoice'])->name('order.invoice');

        Route::get('/order/{kode}/payment',         [ProductPaymentController::class, 'index'])->name('order.payment');
        Route::post('/order/{kode}/payment/token',  [ProductPaymentController::class, 'token'])->name('order.payment.token');
        Route::post('/order/{kode}/payment/finish', [ProductPaymentController::class, 'finish'])->name('order.payment.finish');

        Route::post('/produk/{slug}/review', [ProductReviewController::class, 'store'])->name('produk.review');
    });
});
```

---

## SUB-STEP 2.2.2 — Katalog Produk (Public Pages)

### `ShopController.php`

```php
<?php
namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\{Product, ProductCategory, ProductCollection};

class ShopController extends Controller
{
    public function index()
    {
        $featuredProducts   = Product::where('is_featured', true)->where('status', 'active')
                                     ->with('primaryImage')->limit(8)->get();
        $categories         = ProductCategory::whereNull('parent_id')->orderBy('sort_order')->get();
        $collections        = ProductCollection::orderBy('sort_order')->get();
        $latestProducts     = Product::where('status', 'active')->latest()->with('primaryImage')->limit(12)->get();

        return view('shop.index', compact('featuredProducts', 'categories', 'collections', 'latestProducts'));
    }

    public function kategori(string $slug)
    {
        $category = ProductCategory::where('slug', $slug)->firstOrFail();

        $query = Product::where('id_product_category', $category->id_product_category)
                        ->where('status', 'active')
                        ->with('primaryImage');

        // Filter
        if (request('skin_type'))    $query->where('skin_type', request('skin_type'));
        if (request('skin_concern')) $query->where('skin_concern', 'like', '%' . request('skin_concern') . '%');
        if (request('min_price'))    $query->where('harga', '>=', request('min_price'));
        if (request('max_price'))    $query->where('harga', '<=', request('max_price'));

        // Sort
        $query->when(request('sort', 'terbaru'), fn($q, $sort) => match($sort) {
            'terlaris'   => $q->orderByDesc('total_sold'),
            'harga_asc'  => $q->orderBy('harga'),
            'harga_desc' => $q->orderByDesc('harga'),
            'rating'     => $q->orderByDesc('rating'),
            default      => $q->latest(),
        });

        $products   = $query->paginate(20)->withQueryString();
        $categories = ProductCategory::orderBy('sort_order')->get();

        return view('shop.kategori', compact('category', 'products', 'categories'));
    }

    public function koleksi(string $slug)
    {
        $collection = ProductCollection::where('slug', $slug)->firstOrFail();
        $products   = Product::where('id_collection', $collection->id_collection)
                             ->where('status', 'active')
                             ->with('primaryImage')
                             ->paginate(20);

        return view('shop.koleksi', compact('collection', 'products'));
    }

    public function show(string $slug)
    {
        $product  = Product::where('slug', $slug)->where('status', 'active')
                           ->with(['images', 'category', 'collection'])
                           ->firstOrFail();

        // Review + filter (PRD 4.3.7): Semua / Bintang 5-1 / Dengan Foto
        $reviewQuery = \App\Models\ProductReview::where('id_product', $product->id_product)->with('user');
        if ($f = request('review_filter')) {
            if ($f === 'with_photo') {
                $reviewQuery->whereNotNull('foto_urls');
            } elseif (is_numeric($f)) {
                $reviewQuery->where('rating', (int) $f);
            }
        }
        $reviews = $reviewQuery->latest()->paginate(10)->withQueryString();

        // Breakdown bintang (jumlah review per rating 1-5)
        $ratingBreakdown = \App\Models\ProductReview::where('id_product', $product->id_product)
                              ->selectRaw('rating, COUNT(*) as total')
                              ->groupBy('rating')->pluck('total', 'rating');

        $related  = Product::where('id_product_category', $product->id_product_category)
                           ->where('id_product', '!=', $product->id_product)
                           ->where('status', 'active')
                           ->with('primaryImage')
                           ->limit(4)->get();

        $sameCollection = $product->id_collection
            ? Product::where('id_collection', $product->id_collection)
                     ->where('id_product', '!=', $product->id_product)
                     ->with('primaryImage')->limit(4)->get()
            : collect();

        $isWishlisted = auth()->check()
            ? \App\Models\Wishlist::where('id_user', auth()->user()->id_user)
                                  ->where('id_product', $product->id_product)->exists()
            : false;

        return view('shop.produk-detail', compact(
            'product', 'reviews', 'ratingBreakdown', 'related', 'sameCollection', 'isWishlisted'
        ));
    }

    public function search()
    {
        $query    = request('q');
        $products = Product::where('status', 'active')
                           ->where(fn($q) => $q->where('nama', 'like', "%{$query}%")
                                              ->orWhere('deskripsi', 'like', "%{$query}%")
                                              ->orWhere('key_ingredients', 'like', "%{$query}%"))
                           ->with('primaryImage')
                           ->paginate(20);

        return view('shop.cari', compact('products', 'query'));
    }
}
```

### Views yang perlu dibuat:
- `resources/views/shop/index.blade.php` — hero banner, featured, kategori, koleksi
- `resources/views/shop/kategori.blade.php` — grid + sidebar filter + sort
- `resources/views/shop/koleksi.blade.php` — grid produk per koleksi
- `resources/views/shop/produk-detail.blade.php` — detail lengkap (gallery, ingredients, review)
- `resources/views/shop/cari.blade.php` — hasil pencarian

**Gunakan HTML dari design reference:** `docs_v2/design/j2_product_detail_midnight_renewal_serum_1/code.html` dan `_2/code.html` sebagai acuan layout produk detail.

---

## SUB-STEP 2.2.3 — Skincare Finder

### `SkincarefinderController.php`

```php
public function index()
{
    return view('shop.skincare-finder');
}

public function result(Request $request)
{
    $data = $request->validate([
        'skin_type'    => 'required|in:oily,dry,combination,sensitive,normal',
        'skin_concern' => 'required|string',
        'looking_for'  => 'required|string',
    ]);

    // Simpan profil jika user login
    if (auth()->check()) {
        \App\Models\UserSkincareProfile::updateOrCreate(
            ['id_user' => auth()->user()->id_user],
            ['skin_type' => $data['skin_type'], 'skin_concerns' => $data['skin_concern']]
        );
    }

    // Query rekomendasi produk
    $products = Product::where('status', 'active')
        ->where(fn($q) => $q->where('skin_type', $data['skin_type'])
                            ->orWhere('skin_type', 'all'))
        ->where('skin_concern', 'like', '%' . explode(',', $data['skin_concern'])[0] . '%')
        ->with('primaryImage')
        ->limit(8)->get();

    return view('shop.skincare-finder-result', compact('products', 'data'));
}
```

**View:** `resources/views/shop/skincare-finder.blade.php` — wizard 3 step (Step 1: skin type, Step 2: skin concern, Step 3: looking for)

---

## SUB-STEP 2.2.4 — Wishlist

### `WishlistController.php`

```php
public function index()
{
    $items = \App\Models\Wishlist::where('id_user', auth()->user()->id_user)
                                  ->with('product.primaryImage')
                                  ->get();
    return view('shop.wishlist', compact('items'));
}

public function toggle(Request $request)
{
    $request->validate(['id_product' => 'required|exists:products,id_product']);

    $existing = \App\Models\Wishlist::where('id_user', auth()->user()->id_user)
                                     ->where('id_product', $request->id_product)
                                     ->first();
    if ($existing) {
        $existing->delete();
        $wishlisted = false;
    } else {
        \App\Models\Wishlist::create(['id_user' => auth()->user()->id_user, 'id_product' => $request->id_product]);
        $wishlisted = true;
    }

    return response()->json(['wishlisted' => $wishlisted]);
}
```

### Perilaku tambahan di view `shop/wishlist.blade.php` (PRD 4.3.3)

**1. Pindahkan dari wishlist ke cart** — reuse endpoint `shop.cart.add`, lalu hapus dari wishlist via `shop.wishlist.toggle`. Tidak perlu route baru:

```html
{{-- Tombol "Pindah ke Keranjang" di tiap item wishlist --}}
<button class="btn btn-sm btn-primary move-to-cart" data-product="{{ $item->id_product }}">
    🛒 Pindah ke Keranjang
</button>

<script>
document.querySelectorAll('.move-to-cart').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.product;
        // 1) Tambah ke cart
        await fetch('{{ route('shop.cart.add') }}', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
            body: JSON.stringify({ id_product: id, qty: 1 })
        });
        // 2) Hapus dari wishlist
        await fetch('{{ route('shop.wishlist.toggle') }}', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
            body: JSON.stringify({ id_product: id })
        });
        btn.closest('.wishlist-item').remove(); // optimistic UI
    });
});
</script>
```

**2. Share wishlist via link** — wishlist publik read-only per user. Tambah route opsional:

```php
// routes/web.php (public, tidak perlu auth)
Route::get('/shop/wishlist/{user}/share', [WishlistController::class, 'share'])->name('shop.wishlist.share');
```

```php
// WishlistController
public function share(\App\Models\User $user)
{
    $items = \App\Models\Wishlist::where('id_user', $user->id_user)
                                  ->with('product.primaryImage')->get();
    return view('shop.wishlist-share', compact('items', 'user')); // view read-only
}
```

Tombol share di `shop/wishlist.blade.php`:
```html
<button onclick="navigator.clipboard.writeText('{{ route('shop.wishlist.share', auth()->user()->id_user) }}')"
        class="btn btn-sm btn-outline">🔗 Bagikan Wishlist</button>
```

---

## SUB-STEP 2.2.5 — Cart

### `CartController.php`

```php
public function index()
{
    $items      = \App\Models\Cart::where('id_user', auth()->user()->id_user)
                                   ->with('product.primaryImage')->get();
    $subtotal   = $items->sum(fn($i) => $i->product->harga * $i->qty);
    $threshold  = config('ongkir.free_ongkir_threshold', 500000);

    return view('shop.cart', compact('items', 'subtotal', 'threshold'));
}

public function add(Request $request)
{
    $request->validate(['id_product' => 'required|exists:products,id_product', 'qty' => 'integer|min:1']);

    $cart = \App\Models\Cart::where('id_user', auth()->user()->id_user)
                             ->where('id_product', $request->id_product)->first();
    if ($cart) {
        $cart->increment('qty', $request->qty ?? 1);
    } else {
        \App\Models\Cart::create([
            'id_user' => auth()->user()->id_user,
            'id_product' => $request->id_product,
            'qty' => $request->qty ?? 1,
        ]);
    }

    return response()->json(['cart_count' => \App\Models\Cart::where('id_user', auth()->user()->id_user)->sum('qty')]);
}

public function update(Request $request)
{
    $request->validate(['id_cart' => 'required', 'qty' => 'required|integer|min:1']);

    \App\Models\Cart::where('id_cart', $request->id_cart)
                    ->where('id_user', auth()->user()->id_user)
                    ->update(['qty' => $request->qty]);

    return response()->json(['success' => true]);
}

public function remove(int $id)
{
    \App\Models\Cart::where('id_cart', $id)
                    ->where('id_user', auth()->user()->id_user)
                    ->delete();

    return back()->with('success', 'Produk dihapus dari keranjang.');
}
```

**Design reference cart:** `docs_v2/design/j3_shopping_bag_2/code.html`

---

## SUB-STEP 2.2.6 — OngkirController

### `OngkirController.php`

```php
public function check(Request $request)
{
    $request->validate([
        'destination' => 'required|string',
        'weight'      => 'required|integer|min:100',
    ]);

    $cacheKey = "ongkir_{$request->destination}_{$request->weight}";

    $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, 
        config('ongkir.cache_ttl_minutes', 60) * 60,
        function () use ($request) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(config('ongkir.timeout_seconds', 5))
                    ->withHeaders(['x-api-co-id' => config('ongkir.api_key')])
                    ->post(config('ongkir.base_url') . '/expedition/cost', [
                        'origin'      => config('ongkir.origin_city'),
                        'destination' => $request->destination,
                        'weight'      => $request->weight,
                        'courier'     => implode(',', config('ongkir.couriers')),
                    ]);

                return $response->json();
            } catch (\Exception $e) {
                return ['status' => 'error', 'message' => 'Gagal mengecek ongkir. Coba lagi.'];
            }
        }
    );

    return response()->json($result);
}

// GET /shop/regional/cities?q=sura  — autocomplete kota tujuan (PRD Section 11.3.2)
// Dipakai di form tambah/edit alamat (user_addresses.kota_id & provinsi_id)
public function cities(Request $request)
{
    $q = $request->query('q', '');

    // Cache daftar kota 24 jam (data regional jarang berubah)
    $cities = \Illuminate\Support\Facades\Cache::remember("regional_cities_{$q}", 86400, function () use ($q) {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(config('ongkir.timeout_seconds', 5))
                ->withHeaders(['x-api-co-id' => config('ongkir.api_key')])
                ->get(config('ongkir.base_url') . '/regional/cities', ['search' => $q]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            return [];
        }
    });

    return response()->json($cities);
}
```

> **Route tambahan** (daftarkan di group `shop` auth):
> ```php
> Route::get('/regional/cities', [OngkirController::class, 'cities'])->name('regional.cities');
> ```
> Form tambah alamat memakai endpoint ini untuk dropdown searchable kota → simpan
> `kota`, `kota_id`, `provinsi`, `provinsi_id` ke `user_addresses`. Saat cek ongkir,
> kirim `kota` (atau `kota_id`) sebagai `destination`.

---

## SUB-STEP 2.2.7 — Checkout + Alamat + Promo

### `ProductCheckoutController.php`

```php
public function index()
{
    $user       = auth()->user();
    $cartItems  = \App\Models\Cart::where('id_user', $user->id_user)->with('product')->get();

    if ($cartItems->isEmpty()) return redirect()->route('shop.cart')->with('error', 'Keranjang kosong.');

    $addresses  = \App\Models\UserAddress::where('id_user', $user->id_user)->get();
    $subtotal   = $cartItems->sum(fn($i) => $i->product->harga * $i->qty);
    $totalBerat = max(100, $cartItems->sum(fn($i) => ($i->product->berat_gram ?? 200) * $i->qty));

    return view('shop.checkout', compact('cartItems', 'addresses', 'subtotal', 'totalBerat'));
}

public function store(Request $request)
{
    $request->validate([
        'id_address'    => 'required|exists:user_addresses,id_address',
        'kurir'         => 'required|string',
        'layanan_kirim' => 'required|string',
        'biaya_kirim'   => 'required|numeric|min:0',
        'estimasi_tiba' => 'nullable|string',
    ]);

    $user       = auth()->user();
    $cartItems  = \App\Models\Cart::where('id_user', $user->id_user)->with('product')->get();

    if ($cartItems->isEmpty()) return redirect()->route('shop.cart');

    $subtotal     = $cartItems->sum(fn($i) => $i->product->harga * $i->qty);
    $biayaKirim   = $request->biaya_kirim;
    $totalDiskon  = 0;
    $poinDigunakan = 0;
    $potonganPoin  = 0;

    // Free ongkir jika subtotal >= threshold
    if ($subtotal >= config('ongkir.free_ongkir_threshold')) {
        $biayaKirim = 0;
    }

    // Apply promo (placeholder — reuse Promo model V1 jika ada request promo_code)
    $idPromo = null;
    if ($request->promo_code) {
        $promo = \App\Models\Promo::where('kode', $request->promo_code)->where('is_active', true)->first();
        if ($promo) {
            $idPromo     = $promo->id_promo;
            $totalDiskon = $promo->tipe === 'persen'
                ? $subtotal * ($promo->nilai / 100)
                : $promo->nilai;
        }
    }

    // Apply poin (Phase 3 — placeholder dulu)
    // $poinDigunakan dan $potonganPoin = 0 untuk sementara

    $grandTotal = $subtotal + $biayaKirim - $totalDiskon - $potonganPoin;

    // Generate kode order unik: VYG-S-XXXXXX
    do {
        $kodeOrder = 'VYG-S-' . strtoupper(\Illuminate\Support\Str::random(6));
    } while (\App\Models\ProductOrder::where('kode_order', $kodeOrder)->exists());

    $order = \App\Models\ProductOrder::create([
        'id_user'        => $user->id_user,
        'id_address'     => $request->id_address,
        'id_promo'       => $idPromo,
        'kode_order'     => $kodeOrder,
        'subtotal'       => $subtotal,
        'biaya_kirim'    => $biayaKirim,
        'total_diskon'   => $totalDiskon,
        'poin_digunakan' => $poinDigunakan,
        'potongan_poin'  => $potonganPoin,
        'grand_total'    => $grandTotal,
        'kurir'          => $request->kurir,
        'layanan_kirim'  => $request->layanan_kirim,
        'estimasi_tiba'  => $request->estimasi_tiba,
        'status'         => 'pending',
    ]);

    // Buat order items
    foreach ($cartItems as $item) {
        \App\Models\ProductOrderItem::create([
            'id_product_order' => $order->id_product_order,
            'id_product'       => $item->id_product,
            'nama_produk'      => $item->product->nama,
            'qty'              => $item->qty,
            'harga_satuan'     => $item->product->harga,
            'berat_gram'       => $item->product->berat_gram ?? 200,
            'subtotal'         => $item->product->harga * $item->qty,
        ]);
    }

    // Buat pembayaran record (pending)
    \App\Models\ProductPembayaran::create([
        'id_product_order' => $order->id_product_order,
        'id_user'          => $user->id_user,
        'jumlah'           => $grandTotal,
        'status'           => 'pending',
    ]);

    // Kosongkan cart
    \App\Models\Cart::where('id_user', $user->id_user)->delete();

    return redirect()->route('shop.order.payment', $order->kode_order);
}
```

---

## SUB-STEP 2.2.8 — Payment Midtrans

### `ProductPaymentController.php`

Extend flow dari V1 `PaymentController`. Midtrans Snap sudah pernah diintegrasikan di V1.

```php
public function token(Request $request, string $kode)
{
    $order = \App\Models\ProductOrder::where('kode_order', $kode)
                                      ->where('id_user', auth()->user()->id_user)
                                      ->with('user', 'items')
                                      ->firstOrFail();

    // Buat Snap token via Midtrans SDK (reuse dari V1)
    \Midtrans\Config::$serverKey    = config('midtrans.server_key');
    \Midtrans\Config::$isProduction = config('midtrans.is_production');
    \Midtrans\Config::$isSanitized  = true;
    \Midtrans\Config::$is3ds        = true;

    $params = [
        'transaction_details' => [
            'order_id'     => 'SHOP-' . $order->kode_order,
            'gross_amount' => (int) $order->grand_total,
        ],
        'customer_details' => [
            'first_name' => $order->user->first_name,
            'last_name'  => $order->user->last_name,
            'email'      => $order->user->email,
        ],
        'item_details' => $order->items->map(fn($item) => [
            'id'       => $item->id_product,
            'price'    => (int) $item->harga_satuan,
            'quantity' => $item->qty,
            'name'     => $item->nama_produk,
        ])->toArray(),
    ];

    $snapToken = \Midtrans\Snap::getSnapToken($params);

    // Simpan snap token
    $order->pembayaran()->update(['snap_token' => $snapToken]);

    return response()->json(['snap_token' => $snapToken]);
}

public function finish(Request $request, string $kode)
{
    // Handle Midtrans callback/finish — update status order + pembayaran
    $order = \App\Models\ProductOrder::where('kode_order', $kode)->firstOrFail();

    if ($request->transaction_status === 'settlement' || $request->transaction_status === 'capture') {
        $order->update(['status' => 'paid']);
        $order->pembayaran()->update([
            'midtrans_transaction_id' => $request->transaction_id,
            'metode'                  => $request->payment_type,
            'status'                  => 'success',
            'paid_at'                 => now(),
        ]);

        // Kurangi stok
        foreach ($order->items as $item) {
            \App\Models\Product::where('id_product', $item->id_product)
                               ->decrement('stok', $item->qty);
            \App\Models\Product::where('id_product', $item->id_product)
                               ->increment('total_sold', $item->qty);
        }
    }

    return redirect()->route('shop.order.show', $order->kode_order);
}
```

---

## SUB-STEP 2.2.9 — Riwayat Pesanan + Review

### `ProductOrderController.php`

```php
public function index()
{
    $orders = \App\Models\ProductOrder::where('id_user', auth()->user()->id_user)
                                       ->with('items.product.primaryImage')
                                       ->latest()->paginate(10);
    return view('shop.pesanan-list', compact('orders'));
}

public function show(string $kode)
{
    $order = \App\Models\ProductOrder::where('kode_order', $kode)
                                      ->where('id_user', auth()->user()->id_user)
                                      ->with(['items.product', 'address', 'pembayaran'])
                                      ->firstOrFail();
    return view('shop.pesanan-detail', compact('order'));
}

public function invoice(string $kode)
{
    $order = \App\Models\ProductOrder::where('kode_order', $kode)
                                      ->where('id_user', auth()->user()->id_user)
                                      ->with(['items.product', 'address', 'user', 'pembayaran'])
                                      ->firstOrFail();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice-produk', compact('order'))
                                       ->setPaper('a4', 'portrait');

    return $pdf->download("VIYGO-Shop-Invoice-{$kode}.pdf");
}
```

### `ProductReviewController.php`

```php
public function store(Request $request, string $slug)
{
    $product = \App\Models\Product::where('slug', $slug)->firstOrFail();

    $request->validate([
        'id_product_order' => 'required|exists:product_orders,id_product_order',
        'rating'           => 'required|integer|min:1|max:5',
        'judul'            => 'nullable|string|max:255',
        'komentar'         => 'nullable|string|max:2000',
        'foto'             => 'nullable|array|max:3',
        'foto.*'           => 'image|max:2048',
    ]);

    // Verifikasi order milik user dan status delivered/completed
    $order = \App\Models\ProductOrder::where('id_product_order', $request->id_product_order)
                                      ->where('id_user', auth()->user()->id_user)
                                      ->whereIn('status', ['delivered', 'completed'])
                                      ->firstOrFail();

    // Cek belum review untuk produk ini di order ini
    $exists = \App\Models\ProductReview::where('id_user', auth()->user()->id_user)
                                        ->where('id_product', $product->id_product)
                                        ->where('id_product_order', $order->id_product_order)
                                        ->exists();
    if ($exists) return back()->with('error', 'Anda sudah me-review produk ini.');

    // Handle foto upload
    $fotoUrls = [];
    if ($request->hasFile('foto')) {
        foreach ($request->file('foto') as $foto) {
            $path = $foto->store('reviews', 'public');
            $fotoUrls[] = $path;
        }
    }

    \App\Models\ProductReview::create([
        'id_user'          => auth()->user()->id_user,
        'id_product'       => $product->id_product,
        'id_product_order' => $order->id_product_order,
        'rating'           => $request->rating,
        'judul'            => $request->judul,
        'komentar'         => $request->komentar,
        'foto_urls'        => $fotoUrls ?: null,
    ]);

    // Update rating produk
    $avgRating = \App\Models\ProductReview::where('id_product', $product->id_product)->avg('rating');
    $product->update([
        'rating'       => round($avgRating, 2),
        'total_review' => \App\Models\ProductReview::where('id_product', $product->id_product)->count(),
    ]);

    return back()->with('success', 'Review berhasil dikirim!');
}
```

---

## SUB-STEP 2.2.10 — Views

Buat semua views di `resources/views/shop/`. Gunakan design HTML dari `docs_v2/design/` sebagai referensi:

| View | Design Reference |
|------|-----------------|
| `shop/index.blade.php` | `m_j1_skincare_shop_landing/code.html` |
| `shop/produk-detail.blade.php` | `j2_product_detail_midnight_renewal_serum_1/code.html` + `_2/` |
| `shop/cart.blade.php` | `j3_shopping_bag_2/code.html` |
| `shop/pesanan-detail.blade.php` | `j4_order_confirmation_2/code.html` |
| `shop/checkout.blade.php` | (lihat Section 11.5 di PRD untuk wireframe ongkir picker) |

**Layout grid produk (mobile-first):**
```html
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach($products as $product)
    <!-- product card -->
    @endforeach
</div>
```

**Filter sidebar (mobile: bottom sheet):**
```html
<!-- Desktop: sidebar tetap -->
<aside class="hidden lg:block w-64 shrink-0">
    <!-- filter form -->
</aside>

<!-- Mobile: bottom sheet trigger -->
<button class="lg:hidden" id="filterBtn">Filter & Sort</button>
<div class="lg:hidden fixed inset-0 z-50 hidden" id="filterSheet">
    <!-- filter form dalam drawer -->
</div>
```

---

## ADMIN STORE — Filament Resources (Paralel)

> 🔴 **Filament v5** (lihat [CATATAN-LINGKUNGAN §5](CATATAN-LINGKUNGAN.md)): `form(Schema $form): Schema`,
> actions di `Filament\Actions\*`. **Tiru** `app/Filament/Resources/ServiceResource.php` (CRUD penuh) &
> `app/Filament/Resources/OrderResource.php` (read+update saja, untuk ProductOrderResource).

Buat Filament resources di `app/Filament/Store/Resources/` untuk Admin Store panel:
- `ProductResource` — CRUD produk + upload gambar
- `ProductCategoryResource` — CRUD kategori
- `ProductCollectionResource` — CRUD koleksi
- `ProductOrderResource` — view + update status/resi
- `ProductReviewResource` — moderasi review (hide/show)

Panel di `/admin/store` (Filament panel terpisah — buat `StorePanel` di `app/Providers/Filament/StorePanelProvider.php`).

---

## VERIFIKASI AKHIR

```
1. GET /shop → produk tampil dari seed data fresh.com
2. GET /shop/produk/{slug} → detail produk: gallery, ingredients, cara pakai, review
3. POST /shop/wishlist/toggle → toggle wishlist, ikon ♥ update
4. POST /shop/cart/add → produk masuk cart, badge count navbar update
5. GET /shop/cart → cart tampil dengan subtotal + free ongkir progress bar
6. POST /shop/ongkir/check → hasil ongkir dari api.co.id (atau mock jika belum ada API key)
7. GET /shop/checkout → form checkout dengan alamat + pilih ongkir
8. POST /shop/checkout → order dibuat, redirect ke payment page
9. Midtrans Snap terbuka → selesaikan payment → redirect ke order detail
10. GET /shop/pesanan → list pesanan tampil
11. POST /shop/produk/{slug}/review → review berhasil tersimpan (status order harus delivered)
12. GET /admin/store → Admin Store login → dashboard, produk, pesanan semua akses sesuai permission
```

Setelah selesai, lanjutkan ke Phase 3 (bisa paralel: **[phase-3a-lookbook.md](phase-3a-lookbook.md)**, **[phase-3b-empty-return.md](phase-3b-empty-return.md)**, **[phase-3c-community.md](phase-3c-community.md)**).
