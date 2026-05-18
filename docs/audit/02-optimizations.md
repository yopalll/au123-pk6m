# 02 — Optimization Recommendations

> Rekomendasi optimalisasi performa, struktur, query, dan DX. Bukan bug, tapi *should-fix* untuk skala produksi.
> Prioritas: 🟠 High · 🟡 Medium · 🟢 Low

---

## 🟠 OPT-01 — `viygo-navbar` me-query Kategori + sub_kategori setiap request

**File**: [`resources/views/components/viygo-navbar.blade.php:18-21`](../resources/views/components/viygo-navbar.blade.php#L18)

```php
$navKategori = Kategori::active()
    ->with(['subKategori' => fn ($q) => $q->where('is_active', true)])
    ->orderBy('urutan')
    ->get();
```

### Masalah
Navbar di-render di **setiap halaman** publik. Query ringan (~7 row + 42 sub), tapi tetap ~5ms × N halaman. Komentar di file menolak `Cache::remember` karena unserialize failure di Windows.

### Solusi
Pakai `Cache::store('array')` (request-cached) plus tag-based cache invalidation:
```php
$navKategori = Cache::remember(
    'nav_kategori',
    now()->addHours(24),
    fn () => Kategori::active()
        ->with(['subKategori' => fn ($q) => $q->where('is_active', true)])
        ->orderBy('urutan')
        ->get()
        ->toArray()        // ← serialize ke array, hindari unserialize Eloquent collection
);

// Convert balik ke object di template
$navKategori = collect($navKategori)->map(fn($k) => (object) $k);
```
Atau pakai **View Composer** + cache plain array. Invalidate saat Kategori/SubKategori di-save (di Observer).

---

## 🟠 OPT-02 — `BookingSlotService` bisa N+1 saat `staff_service` filter

**File**: [`app/Services/BookingSlotService.php:85-93`](../app/Services/BookingSlotService.php#L85)

```php
$serviceIdsWithPivot = array_values(array_filter(
    $serviceIds,
    fn ($id) => $this->serviceHasStaffPivot((int) $id),
));
foreach ($serviceIdsWithPivot as $sid) {
    $staffQuery->whereHas('services', fn (Builder $q) => $q->where('service.id_service', $sid));
}
```

### Masalah
`serviceHasStaffPivot` di-cache per-request, OK. Tapi `whereHas` di-loop → setiap service tambah subquery EXISTS. Untuk 5 service = 5 subquery.

### Solusi
Tarik satu query: pilih staff yang punya **semua** service yang dipinta.
```php
if (! empty($serviceIdsWithPivot)) {
    $staffQuery->whereHas('services', fn ($q) => $q
        ->whereIn('service.id_service', $serviceIdsWithPivot)
        , '=', count($serviceIdsWithPivot));  // ← cocokkan jumlah
}
```
Hanya 1 EXISTS dengan COUNT, lebih cepat.

---

## 🟡 OPT-03 — Search controller: `whereHas('services', LIKE %q%)` tanpa FULLTEXT index

**File**: [`app/Http/Controllers/SearchController.php:21-23`](../app/Http/Controllers/SearchController.php#L21)

Pada 190K rows `service`, `LIKE '%haircut%'` = full table scan setiap pencarian. Pada list 5,767 salon × 190K services = lambat.

### Solusi
1. Tambah FULLTEXT index pada `service.nama` (MyISAM/InnoDB Mysql 8 OK):
   ```php
   Schema::table('service', fn($t) => $t->fullText('nama'));
   ```
2. Ganti predicate:
   ```php
   $q->whereHas('services', fn ($s) => $s->whereFullText(['nama'], $q));
   ```
3. Untuk multi-token query (e.g. "blow dry"), gunakan boolean mode.

---

## 🟡 OPT-04 — `HomeController::index` tidak cache featured salons

**File**: [`app/Http/Controllers/HomeController.php`](../app/Http/Controllers/HomeController.php)

Homepage di-hit oleh setiap pengunjung anonim. Data salon top-8 berubah tiap beberapa jam paling cepat.

### Solusi
```php
public function index()
{
    $salons = Cache::remember('home.featured_salons', now()->addMinutes(30), fn() =>
        Salon::active()
            ->with(['kota','services.kategori','primaryImage'])
            ->withCount('reviews')
            ->orderByDesc('rating')
            ->take(8)
            ->get()
    );
    $categories = Cache::remember('home.categories', now()->addHours(24), fn() =>
        Kategori::active()->orderBy('name')->take(8)->get()
    );
    return view('home', compact('salons','categories'));
}
```

---

## 🟡 OPT-05 — Booking-create blade me-loop services di server, lalu kirim ke Alpine sebagai event JS

**File**: [`resources/views/booking/create.blade.php:58-89`](../resources/views/booking/create.blade.php#L58)

`@foreach($salon->services->where('status','active') as $svc)` — kirim seluruh service inline. Untuk salon besar (200+ service), HTML balloon up dan first-paint lambat.

### Solusi
1. Paginate / chunk services per kategori (collapsible group).
2. Atau load services via Alpine `fetch()` dari endpoint JSON dengan pagination/search.

---

## 🟡 OPT-06 — `Order::with(['details.service','salon.kota','review'])` di booking list

**File**: [`app/Http/Controllers/AkunController.php:39`](../app/Http/Controllers/AkunController.php#L39)

OK, sudah pakai eager-loading. Tapi `details.staff` tidak ikut → akan N+1 saat blade mengakses `$detail->staff->name`. Saat ini blade tidak menampilkan staff, tapi tambahkan saat scope berubah.

### Saran (pre-emptive)
```php
->with(['salon.kota', 'details.service', 'details.staff', 'review'])
```

---

## 🟡 OPT-07 — `Salon` table tidak punya composite index untuk listing publik

**File**: [`database/migrations/2026_04_12_000004_create_salon_table.php`](../database/migrations/2026_04_12_000004_create_salon_table.php)

Listing sort by `rating DESC` / `total_review DESC` di-filter `status='active'`. Saat ini index single-column `(status)` dan `(rating)`. Composite akan lebih efisien:
```php
$table->index(['status','rating']);
$table->index(['status','total_review']);
```

---

## 🟡 OPT-08 — `Order` PK `id_order` tidak punya index pada `status` + `date_order`

**File**: [`database/migrations/2026_04_12_000008_create_order_table.php`](../database/migrations/2026_04_12_000008_create_order_table.php)

`CompleteBookings::handle()` jalanin `where('status','confirmed')->whereDate('date_order','<',today())`. Tanpa index → full scan. Pada 50K+ order = lambat.

### Solusi
```php
Schema::table('order', fn($t) => $t->index(['status','date_order']));
```

---

## 🟢 OPT-09 — Leaflet di-load via CDN tanpa fallback

**File**: [`resources/views/layouts/public.blade.php:16-19, 77-79`](../resources/views/layouts/public.blade.php#L16)

Jika `unpkg.com` down, semua halaman public broken. Tidak ada `onerror` fallback.

### Solusi
1. Self-host `leaflet.css` + `leaflet.js` di `public/vendor/leaflet/`.
2. Atau tambah fallback inline JS:
   ```html
   <script>window.L || document.write('<script src="/vendor/leaflet/leaflet.js">\x3C/script>')</script>
   ```

---

## 🟢 OPT-10 — `clean_md.php`, `test-json.php`, `test_panel_routes.php` di root

File top-level ini terlihat seperti script ad-hoc developer. Mereka:
- Tidak diakses oleh autoloader Composer.
- Tidak ada test coverage.
- Bisa **dieksekusi web-side** kalau owasp config salah (mis. di shared hosting).

### Solusi
Pindahkan ke `database/scripts/` dan pastikan `public/` tidak meng-include-nya. Tambahkan ke `.gitignore` jika murni dev-tool.

---

## 🟢 OPT-11 — Dataset JSON 5,767 salon di `database/data/*.json` di-commit ke repo

Berat utk repo, lambat di clone. Pertimbangkan:
- Move ke Git LFS, atau
- Host di S3 + script downloader (`php artisan viygo:fetch-seed-data`).

---

## 🟢 OPT-12 — Penggunaan `auth()->user()` berulang di view & controller

Idiom `auth()->user()->favourites()` setiap baris re-resolve guard. Cheap, tapi multiplier per render. Cache di view layout:
```blade
@auth @php($user = auth()->user()) @endauth
```
Atau di controller pass `$user` ke view sebagai variable.
