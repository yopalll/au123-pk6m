# Phase 4 — Polish, Testing, & Dokumentasi
## Steps 4.1–4.6

> **Prerequisite:** Phase 3A + 3B + 3C SEMUA selesai  
> **Design Reference:** Semua screen di `docs_v2/design/` — final polish pass  
> **Verifikasi:** LCP < 2.5s, php artisan test semua pass, cross-module flows bekerja

---

## STEP 4.1 — Integrasi Cross-Module

### 4.1.1 Poin ↔ Checkout (Phase 3B ke 2B)

Pastikan `ProductCheckoutController::store()` sudah menggunakan `PointService::spendPoints()`.
Tampilkan saldo poin di halaman checkout dengan input berapa poin yang digunakan:

```html
{{-- Di checkout view, setelah ringkasan pesanan --}}
@auth
@php $userPoint = auth()->user()->points; @endphp
@if($userPoint && $userPoint->saldo > 0)
<div class="bg-amber-50 rounded-xl p-4 mt-4">
    <p class="font-medium text-sm">💰 Gunakan Poin (Saldo: {{ $userPoint->saldo }} poin)</p>
    <p class="text-xs text-gray-500 mb-2">1 poin = Rp 1.000 potongan</p>
    <div class="flex items-center gap-3">
        <input type="number" name="poin_digunakan" min="0" max="{{ $userPoint->saldo }}"
               class="input input-sm w-24"
               value="0"
               oninput="updatePointDiscount(this.value)">
        <span class="text-sm text-green-600" id="pointDiscount">Potongan: Rp 0</span>
    </div>
</div>
@endif
@endauth
```

### 4.1.2 Tier ↔ Free Ongkir

Update `OngkirController::check()` dan `ProductCheckoutController::store()`:

```php
// Cek apakah user dapat free ongkir dari tier
$freeOngkir = false;
$userPoint  = \App\Models\UserPoint::where('id_user', auth()->user()->id_user)->first();

if ($userPoint) {
    if ($userPoint->tier === 'gold') {
        $freeOngkir = true; // unlimited
    } elseif ($userPoint->tier === 'silver') {
        // 1x free ongkir per bulan — cek apakah sudah dipakai bulan ini
        $usedThisMonth = \App\Models\ProductOrder::where('id_user', auth()->user()->id_user)
            ->where('biaya_kirim', 0)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->exists();
        $freeOngkir = !$usedThisMonth;
    }
}

if ($freeOngkir || $subtotal >= config('ongkir.free_ongkir_threshold')) {
    $biayaKirim = 0;
}
```

### 4.1.3 Tier ↔ Konten Eksklusif

Sudah diimplementasikan di `ExclusiveContentController`. Verifikasi: naik tier → langsung akses konten baru.

### 4.1.4 Wishlist ↔ Lookbook

Di `lookbook/show.blade.php`, tambahkan tombol wishlist di setiap product tag:

```html
{{-- Di product tag popup --}}
<button class="wishlist-btn text-xs mt-1 {{ $isWishlisted ? 'text-red-500' : 'text-gray-400' }}"
        data-product="{{ $item->product->id_product }}">
    {{ $isWishlisted ? '❤️' : '♡' }} Wishlist
</button>
```

### 4.1.5 Forum ↔ Produk

Di halaman thread detail, product tags yang ter-link ke shop sudah diimplementasikan via `taggedProducts`.
Verifikasi: klik nama produk di thread → redirect ke `/shop/produk/{slug}`.

### 4.1.6 Badge ↔ Empty Return (Eco Warrior)

Sudah diimplementasikan di `ForumController::checkBadges()`. Panggil juga dari `EmptyReturnController` saat user submit (setelah approved):

```php
// Di PointService::creditFromEmptyReturn(), setelah update tier:
// Trigger badge check
\App\Http\Controllers\Forum\ForumController::checkBadgesStatic($emptyReturn->id_user);
```

Atau jadikan `checkBadges()` sebagai static method / pindahkan ke `BadgeService`.

---

## STEP 4.2 — Admin Store Dashboard

### Buat Dashboard Widget di Filament Admin Store Panel

File: `app/Filament/Store/Widgets/`

**Widget yang perlu dibuat:**
1. `StatsOverviewWidget` — total produk aktif, pesanan hari ini (+ nilai), pending orders, pending empty returns
2. `SalesTrendWidget` — grafik penjualan 7 hari terakhir (LineChartWidget)
3. `LowStockWidget` — tabel produk stok < 10

> 🔴 **Filament v5** (lihat [CATATAN-LINGKUNGAN §5](CATATAN-LINGKUNGAN.md)): tiru
> `app/Filament/Widgets/StatsOverview.php` — extends `StatsOverviewWidget`, pakai `...StatsOverviewWidget\Stat`.

```php
// app/Filament/Store/Widgets/StoreStatsOverview.php
namespace App\Filament\Store\Widgets;

use App\Models\{Product, ProductOrder, EmptyReturn};
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = now()->toDateString();
        return [
            Stat::make('Produk Aktif', Product::where('status','active')->count()),
            Stat::make('Pesanan Hari Ini', ProductOrder::whereDate('created_at', $today)->count())
                ->description('Rp ' . number_format(ProductOrder::whereDate('created_at', $today)->sum('grand_total'), 0, ',', '.'))
                ->color('success'),
            Stat::make('Pending Orders', ProductOrder::where('status','pending')->count())->color('warning'),
            Stat::make('Empty Return Pending', EmptyReturn::where('status','pending')->count())->color('danger'),
        ];
    }
}
```

Daftarkan widget di `StorePanelProvider` atau `AdminStoreDashboard`.

---

## STEP 4.3 — UI/UX Polish + Responsive Final

### Checklist per Modul

**Global:**
- [ ] TailwindCSS v4 breakpoints konsisten (`sm:`, `md:`, `lg:`, `xl:`)
- [ ] Font: Playfair Display untuk heading, Manrope untuk body (install dari Google Fonts atau Fontsource)
- [ ] Design system colors dari `DESIGN-SYSTEM.md` tersedia sebagai CSS custom properties

**Shop `/shop`:**
- [ ] Product grid: 2 kolom mobile → 3 tablet → 4 desktop
- [ ] Filter sidebar → bottom sheet di mobile
- [ ] Cart → slide-in drawer dari kanan (desktop/tablet)
- [ ] Checkout → single column mobile → two-column desktop

**Lookbook `/lookbook`:**
- [ ] Grid: 1 kolom mobile → 2 tablet → 3 desktop
- [ ] Slide: swipe gesture mobile, keyboard nav desktop
- [ ] Product tags: tap mobile, hover desktop

**Empty Return `/empty-return`:**
- [ ] Landing page: stacked mobile → two-column hero desktop
- [ ] Upload foto: camera capture mobile → drag & drop desktop
- [ ] Points dashboard: card stack mobile → grid desktop

**Forum `/komunitas`:**
- [ ] Kategori: horizontal scroll chips mobile → grid + sidebar desktop
- [ ] Reply form: bottom fixed bar mobile → inline form desktop
- [ ] Rich text editor toolbar: simplified mobile

### Mobile-Specific UX

**Skeleton Loading:**
```html
<!-- Product card skeleton -->
<div class="animate-pulse">
    <div class="bg-gray-200 rounded-xl aspect-square mb-3"></div>
    <div class="h-3 bg-gray-200 rounded w-3/4 mb-2"></div>
    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
</div>
```

**Toast Notifications:**
```html
<!-- resources/views/components/toast.blade.php -->
@if(session('success'))
<div id="toast" class="fixed bottom-20 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-6 py-3 rounded-full shadow-xl z-50 text-sm">
    ✓ {{ session('success') }}
</div>
<script>setTimeout(() => document.getElementById('toast')?.remove(), 3000)</script>
@endif
```

**Infinite Scroll** (opsional — gunakan Livewire atau IntersectionObserver):
```js
const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) loadMore();
});
observer.observe(document.getElementById('loadMore'));
```

### Image Optimization

Di semua `<img>` tag produk:
```html
<img src="{{ $product->primaryImage?->image_url }}"
     srcset="{{ $product->primaryImage?->image_url }}?w=300 300w,
             {{ $product->primaryImage?->image_url }}?w=600 600w"
     sizes="(max-width: 640px) 150px, (max-width: 1024px) 200px, 300px"
     loading="lazy"
     alt="{{ $product->nama }}"
     class="w-full h-full object-cover">
```

---

## STEP 4.4 — Testing

### Feature Tests yang Wajib Ada

```bash
php artisan make:test Shop/CartTest
php artisan make:test Shop/CheckoutTest
php artisan make:test Shop/OngkirTest
php artisan make:test Shop/ReviewTest
php artisan make:test EmptyReturn/EmptyReturnApprovalTest
php artisan make:test Forum/ForumThreadTest
php artisan make:test Booking/InvoiceTest
```

**Contoh test checkout:**
```php
// tests/Feature/Shop/CheckoutTest.php
public function test_checkout_creates_order_with_correct_kode_format()
{
    $user = User::factory()->create();
    $product = Product::factory()->create(['harga' => 100000, 'berat_gram' => 200, 'stok' => 10]);
    Cart::create(['id_user' => $user->id_user, 'id_product' => $product->id_product, 'qty' => 1]);
    $address = UserAddress::factory()->create(['id_user' => $user->id_user]);

    $this->actingAs($user)->post(route('shop.checkout.store'), [
        'id_address'    => $address->id_address,
        'kurir'         => 'jne',
        'layanan_kirim' => 'REG',
        'biaya_kirim'   => 15000,
        'estimasi_tiba' => '2-3 hari',
    ]);

    $order = ProductOrder::where('id_user', $user->id_user)->first();
    $this->assertNotNull($order);
    $this->assertStringStartsWith('VYG-S-', $order->kode_order);
    $this->assertEquals(115000, $order->grand_total);
}
```

**OngkirController test:**
```php
public function test_ongkir_check_returns_correct_response()
{
    Http::fake([
        'api.co.id/*' => Http::response([
            'status' => 'success',
            'data'   => [['courier' => 'jne', 'services' => [['service' => 'REG', 'cost' => 15000, 'etd' => '2-3 hari']]]],
        ], 200),
    ]);

    $this->actingAs(User::factory()->create())
         ->post(route('shop.ongkir.check'), ['destination' => 'Surabaya', 'weight' => 500])
         ->assertOk()
         ->assertJsonPath('status', 'success');
}

public function test_ongkir_timeout_returns_error_gracefully()
{
    Http::fake(['api.co.id/*' => fn() => throw new \Exception('Timeout')]);

    $this->actingAs(User::factory()->create())
         ->post(route('shop.ongkir.check'), ['destination' => 'Surabaya', 'weight' => 500])
         ->assertOk()
         ->assertJsonPath('status', 'error');
}
```

**Invoice PDF test:**
```php
public function test_booking_invoice_pdf_is_downloadable()
{
    $user  = User::factory()->create();
    $order = Order::factory()->create(['id_user' => $user->id_user]);

    $response = $this->actingAs($user)
                     ->get(route('akun.booking.invoice', $order->kode_order));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
}
```

**Authorization tests:**
```php
public function test_admin_store_can_access_store_panel()
{
    $admin = User::factory()->create(['role' => 'admin_store']);
    $this->actingAs($admin)->get('/admin/store')->assertOk();
}

public function test_customer_cannot_access_store_panel()
{
    $customer = User::factory()->create(['role' => 'customer']);
    $this->actingAs($customer)->get('/admin/store')->assertForbidden();
}
```

**Jalankan semua test:**
```bash
php artisan test
php artisan test --coverage --min=70
```

---

## STEP 4.5 — Performance & Security Audit

### Database Indexes

Tambahkan migration baru untuk composite indexes:

```php
// database/migrations/2026_06_xx_add_indexes_v2.php
public function up(): void
{
    // Products: sering di-query dengan filter status + category
    Schema::table('products', function (Blueprint $table) {
        $table->index(['status', 'is_featured']);
        $table->index(['id_product_category', 'status']);
        $table->index(['id_collection', 'status']);
    });

    // Product orders: sering di-query per user + status
    Schema::table('product_orders', function (Blueprint $table) {
        $table->index(['id_user', 'status']);
        $table->index(['id_user', 'created_at']);
    });

    // Forum threads: sering di-query per kategori + status
    Schema::table('forum_threads', function (Blueprint $table) {
        $table->index(['id_forum_category', 'status', 'is_pinned']);
    });

    // Carts: per user
    Schema::table('carts', function (Blueprint $table) {
        $table->index(['id_user']);
    });
}
```

### Rate Limiting

Di `app/Http/Kernel.php` atau `AppServiceProvider`:

```php
RateLimiter::for('ongkir', fn($request) => Limit::perMinute(10)->by($request->user()?->id_user));
RateLimiter::for('submit', fn($request) => Limit::perMinute(5)->by($request->user()?->id_user));
```

Apply di routes:
```php
Route::post('/shop/ongkir/check', ...)->middleware('throttle:ongkir');
Route::post('/empty-return/submit', ...)->middleware('throttle:submit');
Route::post('/komunitas/thread', ...)->middleware('throttle:submit');
```

### File Upload Validation

Semua endpoint upload (review foto, empty return foto) harus validasi:
```php
'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048', // max 2MB per file
```

### Caching

```php
// Katalog produk: cache 15 menit
$products = Cache::remember("products.featured", 900, fn() => Product::where('is_featured', true)->with('primaryImage')->get());

// Ongkir: cache 1 jam (sudah di OngkirController)

// Lookbook: cache 15 menit
$lookbooks = Cache::remember("lookbooks.published", 900, fn() => Lookbook::where('is_published', true)->get());
```

### Queue untuk Email & PDF

Di `.env`:
```
QUEUE_CONNECTION=database
```

```bash
php artisan queue:table
php artisan migrate
```

Gunakan Job untuk PDF generation yang berat:
```php
// Dispatch job untuk kirim email konfirmasi pesanan
dispatch(new SendOrderConfirmationEmail($order));
```

---

## STEP 4.6 — Dokumentasi Final

### Update `README.md`

Tambahkan section setup V2:

```markdown
## Setup V2

### Prerequisites
- PHP 8.2+, Laravel 12, MySQL 8
- Go 1.21+ (untuk scraper fresh.com)
- Node.js (untuk asset build)

### Environment Variables (tambahan V2)
```
API_CO_ID_KEY=          # api.co.id API key untuk cek ongkir
ONGKIR_ORIGIN_CITY="Jakarta Selatan"
```

### Migration & Seeder
```bash
php artisan migrate          # 1 ALTER + 28 tabel baru
php artisan db:seed --class=AdminStoreSeeder
php artisan db:seed --class=ForumCategorySeeder

# Scrape data produk fresh.com (perlu Chrome + Go)
cd scripts/scraper && go run fresh_scraper.go && cd ../..
php artisan db:seed --class=FreshProductSeeder
```

### Admin Store Panel
- URL: `/admin/store`
- Email: `admin.store@viygo.id`
- Password: `ViygoStore2026!` **(WAJIB diganti setelah deploy)**
```

---

## CHECKLIST FINAL PHASE 4

```
[ ] 4.1  Cross-module flows:
    [ ]   Poin dari empty return bisa digunakan di checkout
    [ ]   Tier Silver/Gold → free ongkir sesuai ketentuan
    [ ]   Tier → konten eksklusif terbuka sesuai tier
    [ ]   Wishlist toggle di lookbook product tags
    [ ]   Forum thread → klik produk tag → ke /shop/produk
    [ ]   Eco Warrior badge ter-assign setelah 5 approved returns
    [ ]   Top Reviewer badge setelah 10 product reviews

[ ] 4.2  Admin Store Dashboard:
    [ ]   StatsOverviewWidget: total produk, pesanan hari ini, pending orders, pending returns
    [ ]   SalesTrendWidget: grafik 7 hari terakhir
    [ ]   LowStockWidget: produk stok < 10

[ ] 4.3  UI/UX Polish:
    [ ]   Semua halaman responsive: 375px / 768px / 1280px
    [ ]   Mobile bottom tab bar berfungsi
    [ ]   Skeleton loading di product list + thread list
    [ ]   Toast notification di cart add, review submit, empty return submit
    [ ]   Font Playfair Display + Manrope terpasang
    [ ]   Image lazy loading di semua product images

[ ] 4.4  Testing:
    [ ]   php artisan test → semua pass
    [ ]   CartTest, CheckoutTest, OngkirTest (termasuk timeout), ReviewTest
    [ ]   EmptyReturn approval + poin credit test
    [ ]   Forum thread + reply + like test
    [ ]   Invoice PDF downloadable test
    [ ]   Admin Store authorization test

[ ] 4.5  Performance & Security:
    [ ]   Composite indexes di migration
    [ ]   Rate limiting: ongkir, submit, checkout
    [ ]   File upload validation: mimes + max size
    [ ]   HTMLPurifier di forum konten
    [ ]   Cache: produk featured, lookbook, ongkir result
    [ ]   TTFB < 500ms, LCP < 2.5s desktop (cek di Chrome DevTools)
    [ ]   DB queries per page < 15 (gunakan Laravel Debugbar di dev)

[ ] 4.6  Dokumentasi:
    [ ]   README.md di-update dengan setup guide V2
    [ ]   .env.example up-to-date dengan semua env vars V2
    [ ]   Password admin store sudah diganti (setelah deploy)
```

---

## DONE 🎉

Jika semua checklist di atas hijau → VIYGO V2 siap untuk QA final dan deploy.
