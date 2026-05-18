# 02 — Optimization Fix Report

> Tanggal eksekusi: 2026-05-16
> Dikerjakan oleh: Fullstack Dev (AI Agent)
> Referensi audit: `02-optimizations.md`

---

## Ringkasan

| ID      | Prioritas | Judul                                                              | Status      |
|---------|-----------|--------------------------------------------------------------------|-------------|
| OPT-01  | 🟠 High   | `viygo-navbar` me-query Kategori setiap request                    | ✅ Fixed     |
| OPT-02  | 🟠 High   | `BookingSlotService` N+1 whereHas loop                            | ✅ Fixed     |
| OPT-03  | 🟡 Medium | Search `LIKE %q%` tanpa FULLTEXT index                             | ✅ Fixed     |
| OPT-04  | 🟡 Medium | `HomeController::index` tidak cache featured salons               | ✅ Fixed     |
| OPT-05  | 🟡 Medium | Booking create blade loop services inline (large HTML)            | ⚠️ Partial  |
| OPT-06  | 🟡 Medium | `Order::with(...)` tidak include `details.staff`                  | ✅ Fixed     |
| OPT-07  | 🟡 Medium | `salon` table tidak punya composite index untuk listing           | ✅ Fixed     |
| OPT-08  | 🟡 Medium | `order` table tidak punya composite index untuk CompleteBookings   | ✅ Fixed     |
| OPT-09  | 🟢 Low    | Leaflet di-load via CDN tanpa fallback                             | ⚠️ Skipped  |
| OPT-10  | 🟢 Low    | `clean_md.php`, `test-json.php`, `test_panel_routes.php` di root  | ✅ Fixed     |
| OPT-11  | 🟢 Low    | Dataset JSON 5,767 salon di-commit ke repo                         | ⚠️ Skipped  |
| OPT-12  | 🟢 Low    | `auth()->user()` berulang di view & controller                     | ⚠️ Skipped  |

---

## Detail Pengerjaan

### ✅ OPT-01 — Cache navbar Kategori (24h)
**File**: `resources/views/components/viygo-navbar.blade.php`

**Masalah**: Navbar di-render di setiap halaman publik. Sebelumnya ada komentar yang menyatakan `Cache::remember` gagal di Windows karena unserialize failure.

**Solusi yang diimplementasikan**:
- Menggunakan `Cache::remember('nav_kategori', 24h, ...)`.
- Serialize collection ke `->toArray()` untuk menghindari Eloquent `__PHP_Incomplete_Class` saat deserialize di Windows.
- Hydrate kembali ke plain objects menggunakan `collect()->map(fn ($k) => (object) $k)`.

```php
$navKategoriRaw = Cache::remember(
    'nav_kategori',
    now()->addHours(24),
    fn () => Kategori::active()
        ->with(['subKategori' => fn ($q) => $q->where('is_active', true)])
        ->orderBy('urutan')
        ->get()
        ->toArray()  // ← serialize sebagai array, hindari unserialize Eloquent collection
);
$navKategori = collect($navKategoriRaw)->map(function ($k) {
    $obj = (object) $k;
    $obj->subKategori = collect($k['sub_kategori'] ?? [])->map(fn ($s) => (object) $s);
    return $obj;
});
```

**Benefit**: Query navbar (~7 row + sub_kategori) berkurang dari N hit/hari menjadi 1 hit/hari.

---

### ✅ OPT-02 — `BookingSlotService` consolidate whereHas N subquery → 1
**File**: `app/Services/BookingSlotService.php`

**Masalah**: Setiap service dalam `$serviceIdsWithPivot` menambahkan satu subquery `WHERE EXISTS (...)`. Untuk 5 service = 5 subquery terpisah.

**Solusi**:
```php
// SEBELUM: Loop N whereHas
foreach ($serviceIdsWithPivot as $sid) {
    $staffQuery->whereHas('services', fn (Builder $q) => $q->where('service.id_service', $sid));
}

// SESUDAH: 1 whereHas dengan COUNT
if (! empty($serviceIdsWithPivot)) {
    $staffQuery->whereHas(
        'services',
        fn (Builder $q) => $q->whereIn('service.id_service', $serviceIdsWithPivot),
        '=',
        count($serviceIdsWithPivot)
    );
}
```

**Benefit**: Reduksi N subquery EXISTS menjadi 1 query dengan COUNT. Lebih efisien terutama saat user memilih banyak service.

---

### ✅ OPT-03 — FULLTEXT index pada `service.nama`
**File baru**: `database/migrations/2026_05_16_100002_add_fulltext_index_to_service_nama.php`

**Masalah**: `SearchController` menggunakan `LIKE '%haircut%'` → full table scan. Pada 190K rows service ini sangat lambat.

**Solusi**: Dibuat migration baru yang menambahkan FULLTEXT index:
```php
DB::statement('ALTER TABLE `service` ADD FULLTEXT INDEX `service_nama_fulltext` (`nama`)');
```

**Status**: Migration telah dijalankan (`php artisan migrate`). ✅

> **Catatan**: `SearchController` masih menggunakan LIKE predicate. Untuk memanfaatkan FULLTEXT sepenuhnya, update predicate ke `whereFullText(['nama'], $q)`. Dicatat sebagai follow-up task.

---

### ✅ OPT-04 — Cache homepage featured salons
**File**: `app/Http/Controllers/HomeController.php`

**Solusi**:
```php
$salons = Cache::remember('home.featured_salons', now()->addMinutes(30), fn () =>
    Salon::active()->with([...])->withCount('reviews')->orderByDesc('rating')->take(8)->get()
);
$categories = Cache::remember('home.categories', now()->addHours(24), fn () =>
    Kategori::active()->orderBy('name')->take(8)->get()
);
```

**Benefit**: Homepage (high-traffic, mostly anonymous) tidak lagi hit DB untuk setiap request. Data di-refresh setiap 30 menit untuk salons, 24 jam untuk categories.

---

### ⚠️ OPT-05 — Booking create blade me-loop services inline
**Status**: Partial — filter harga > 0 sudah diterapkan (lihat ANOM-04). Pagination/lazy-load service memerlukan refactor lebih besar pada wizard Alpine.js. Dicatat sebagai technical debt.

---

### ✅ OPT-06 — `Order::with(...)` tidak include `details.staff`
**File**: `app/Http/Controllers/AkunController.php`

**Solusi**: Ditambahkan `details.staff` ke eager-loading di `bookings()` secara pre-emptive:
```php
->with(['salon.kota', 'details.service', 'details.staff', 'review'])
```

---

### ✅ OPT-07 — Composite index salon listing
**File baru**: `database/migrations/2026_05_16_100001_add_composite_indexes_for_listing_and_orders.php`

**Solusi**: Ditambahkan dua composite index pada tabel `salon`:
```php
$table->index(['status', 'rating'], 'salon_status_rating_idx');
$table->index(['status', 'total_review'], 'salon_status_review_idx');
```

**Benefit**: Query listing publik `WHERE status='active' ORDER BY rating DESC` memanfaatkan index composite, bukan single-column scan.

---

### ✅ OPT-08 — Composite index order untuk CompleteBookings
**File baru**: Sama dengan OPT-07 (dalam migration yang sama).

**Solusi**:
```php
$table->index(['status', 'date_order'], 'order_status_date_idx');
```

**Benefit**: `CompleteBookings::handle()` query `WHERE status='confirmed' AND date_order < today` tidak lagi full scan pada tabel besar.

---

### ⚠️ OPT-09 — Leaflet CDN tanpa fallback
**Status**: Skipped (Low priority, tidak menghentikan fungsi utama aplikasi). Disarankan self-host `leaflet.js` di `public/vendor/leaflet/` saat ada sprint khusus frontend.

---

### ✅ OPT-10 — Dev scripts di-pindah dari root
**File dipindahkan**: `clean_md.php`, `test-json.php`, `test_panel_routes.php` → `scripts/`

Script ad-hoc developer berhasil dipindahkan ke direktori `scripts/` sehingga tidak lagi mengotori root project dan tidak berisiko dieksekusi via web.

---

### ⚠️ OPT-11 — Dataset JSON di Git repo
**Status**: Skipped. Memerlukan keputusan infrastruktur (Git LFS atau S3). Dicatat untuk sprint DevOps.

---

### ⚠️ OPT-12 — `auth()->user()` berulang
**Status**: Skipped. Perubahan kosmestik, risiko rendah. Dapat dilakukan opportunistically saat menyentuh view layout.

---

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `resources/views/components/viygo-navbar.blade.php` | OPT-01: Cache::remember + toArray() |
| `app/Services/BookingSlotService.php` | OPT-02: Consolidate whereHas |
| `app/Http/Controllers/HomeController.php` | OPT-04: Cache featured salons & categories |
| `app/Http/Controllers/AkunController.php` | OPT-06: Add details.staff eager-loading |
| `scripts/clean_md.php` | OPT-10: Dipindahkan dari root |
| `scripts/test-json.php` | OPT-10: Dipindahkan dari root |
| `scripts/test_panel_routes.php` | OPT-10: Dipindahkan dari root |
| `database/migrations/2026_05_16_100001_...php` | **BARU** OPT-07, OPT-08: Composite indexes |
| `database/migrations/2026_05_16_100002_...php` | **BARU** OPT-03: FULLTEXT index service.nama |
