# LAPORAN PROYEK — VIYGO Beauty & Wellness Marketplace

> **Tanggal laporan:** 1 Mei 2026
> **Branch:** `go-fresh`
> **Estimasi progress:** ~70%

---

## 1. Latar Belakang & Ringkasan Eksekutif

VIYGO adalah proyek **full-stack web application** yang mereplikasi fungsionalitas [Treatwell.co.uk](https://www.treatwell.co.uk) — platform booking salon kecantikan & wellness terbesar di Eropa. Proyek ini dibangun sebagai tugas akademik menggunakan **Laravel 13 + Livewire Flux v2 + TailwindCSS v4**.

### Data Foundation
- **8.750 salon** dari Treatwell UK (awalnya 5.767, bertambah setelah scraping tambahan)
- **190.594 services** (treatments)
- **7.568 staff** (stylists/therapists)
- **50.492 salon images** (gallery photos)
- **1.709 kota** (UK cities)
- **7.183 kategori** treatment

Data discrape menggunakan **Go scraper** concurrent dari Treatwell UK, kemudian di-seed ke database MySQL melalui Laravel seeders yang bersifat idempotent.

### Teknologi
| Layer | Stack |
|-------|-------|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Livewire Flux v2, TailwindCSS v4, Alpine.js |
| Maps | Leaflet 1.9.4 (OpenStreetMap, CDN) |
| Auth | Laravel Fortify (2FA-ready) |
| Database | MySQL (`viygo-go`) |
| Scraper | Go (Golang) |
| Build | Vite + npm |
| Testing | PestPHP v4 |

---

## 2. Daftar Perubahan Per Fase

### Phase 1 — Database (Completed)
- 3 migrasi baru untuk `salon.slug` dan `order_detail.catatan`
- Strategy 3-step: nullable column → backfill seeder → unique index
- `SalonSlugBackfillSeeder` — chunked backfill 8.750 slug unik, dedupe via appending `-{id_salon}`

### Phase 2 — Model Updates (Completed)
- `Salon.php` — `slug` ditambahkan ke `$fillable`, `getRouteKeyName()` mengembalikan `'slug'`
- `SalonImage.php` — accessor `url` sebagai alias `image_url`
- `Kota.php` — accessor `nama` sebagai alias `nama_kota`

### Phase 3 — Controllers (Completed)
10 controllers dibuat di `app/Http/Controllers/`:
- `HomeController` — 8 salon top-rated + 8 kategori populer
- `SearchController` — full-text search + filter by location/category/rating, sort by rating/price/newest
- `KategoriController` — browse salon per kategori, `withMin('services as min_harga', 'harga')`
- `SalonController` — slug + id_salon fallback, eager load services/staff/reviews/images
- `BookingController` — 3-step wizard + store logic (maps to `OrderDetail` schema)
- `AkunController` — dashboard, bookings (3 tabs), favorit, pengaturan, reward
- `GiftCardController`, `LookbookController`, `TreatmentFilesController`, `MitraController` — stubs

### Phase 4 — Routes (Completed)
`routes/web.php` ditulis ulang. 18 named routes terdaftar:
- **Public (8):** `/`, `/cari`, `/kategori/{slug}`, `/salon/{slug}`, `/gift-card`, `/lookbook`, `/treatment-files`, `/mitra`
- **Auth-protected (10):** booking flow (create/store/konfirmasi/batal), akun (index/bookings/favorit/pengaturan/reward), dashboard

### Phase 5.1 — Public Layout (Completed)
`layouts/public.blade.php`:
- Leaflet 1.9.4 CSS + JS via CDN (SRI integrity hashes)
- Title template: `{{ $title ?? 'VIYGO' }} — Beauty & Wellness Marketplace`
- Z-index rules agar Leaflet tetap di bawah sticky navbar
- Renders `<x-viygo-navbar>` dan `<x-viygo-footer>`

### Phase 5.2 — Components (Completed)
5 Blade components di `resources/views/components/`:
| Component | Fitur |
|-----------|-------|
| `viygo-logo` | Alpine.js cross-fade antara 2 logo images, text fallback via `onerror` |
| `viygo-navbar` | 2-row Treatwell-style, search bar, auth links, category navigation |
| `viygo-footer` | Footer lengkap dengan treatment links ke `/cari?q=...` |
| `salon-card` | List & grid layout, `£` formatting, Kota accessor, favourite button |
| `leaflet-map` | **BARU** — reusable component: multi/single marker, popup, fitBounds, deferred init |

### Phase 5.3 — Page Views (Completed)
14 halaman dengan UI bahasa Inggris + mata uang £ GBP:

| Halaman | File | Fitur Utama |
|---------|------|-------------|
| Homepage | `home.blade.php` | Hero, UK stats, kategori emojis, salon populer, CTA |
| Search | `cari/index.blade.php` | Leaflet multi-marker map (sidebar), sort chips, empty states |
| Category | `kategori/show.blade.php` | Leaflet map, sort dropdown, filtered services |
| Salon Detail | `salon/show.blade.php` | Leaflet single-marker, staff section, reviews, gallery |
| Booking | `booking/create.blade.php` | 3-step Alpine.js wizard, English months/days, currency £ |
| Confirmation | `booking/konfirmasi.blade.php` | Success page with order details |
| Account | `akun/index.blade.php` | Dashboard tiles (Bookings, Personal Info, etc.) |
| Bookings | `akun/bookings.blade.php` | Tabs: Upcoming / Completed / Cancelled |
| Favourites | `akun/favorit.blade.php` | Empty state English |
| Settings | `akun/pengaturan.blade.php` | Binds to `first_name + last_name + email`, `@method('PUT')` |
| Rewards | `akun/reward.blade.php` | VIYGO Rewards with progress bar |
| Gift Card | `gift-card/index.blade.php` | Nominals £25 / £50 / £100 / £200 |
| Lookbook | `lookbook/index.blade.php` | Style inspiration gallery |
| Treatment Files | `treatment-files/index.blade.php` | Beauty articles |
| Mitra | `mitra/index.blade.php` | Partner sign-up form with `$kotas` dropdown |

### Phase 6 — Documentation (Completed)
- `README.md` — refreshed, English/UK-friendly, 18 routes documented
- `progress.md` — updated to ~70%, all Phase 6 items checked
- `INTEGRATION_GUIDE.md` — ✅ COMPLETED banner + Deviations section
- `PROGRESS_REPORT.md` — per-phase log for agent hand-off
- `LAPORAN_PROYEK.md` — file ini

---

## 3. Tabel File Ditambahkan vs Dimodifikasi

### File Baru (Created)

| Path | Kategori |
|------|----------|
| `database/migrations/2026_05_01_000001_add_slug_to_salon_table.php` | Migration |
| `database/migrations/2026_05_01_000002_add_catatan_to_order_detail_table.php` | Migration |
| `database/migrations/2026_05_01_000003_add_unique_index_to_salon_slug.php` | Migration |
| `database/seeders/SalonSlugBackfillSeeder.php` | Seeder |
| `app/Http/Controllers/HomeController.php` | Controller |
| `app/Http/Controllers/SearchController.php` | Controller |
| `app/Http/Controllers/KategoriController.php` | Controller |
| `app/Http/Controllers/SalonController.php` | Controller |
| `app/Http/Controllers/BookingController.php` | Controller |
| `app/Http/Controllers/AkunController.php` | Controller |
| `app/Http/Controllers/GiftCardController.php` | Controller |
| `app/Http/Controllers/LookbookController.php` | Controller |
| `app/Http/Controllers/TreatmentFilesController.php` | Controller |
| `app/Http/Controllers/MitraController.php` | Controller |
| `resources/views/layouts/public.blade.php` | Layout |
| `resources/views/components/viygo-logo.blade.php` | Component |
| `resources/views/components/viygo-navbar.blade.php` | Component |
| `resources/views/components/viygo-footer.blade.php` | Component |
| `resources/views/components/salon-card.blade.php` | Component |
| `resources/views/components/leaflet-map.blade.php` | Component |
| `resources/views/home.blade.php` | View |
| `resources/views/cari/index.blade.php` | View |
| `resources/views/kategori/show.blade.php` | View |
| `resources/views/salon/show.blade.php` | View |
| `resources/views/booking/create.blade.php` | View |
| `resources/views/booking/konfirmasi.blade.php` | View |
| `resources/views/akun/index.blade.php` | View |
| `resources/views/akun/bookings.blade.php` | View |
| `resources/views/akun/favorit.blade.php` | View |
| `resources/views/akun/pengaturan.blade.php` | View |
| `resources/views/akun/reward.blade.php` | View |
| `resources/views/gift-card/index.blade.php` | View |
| `resources/views/lookbook/index.blade.php` | View |
| `resources/views/treatment-files/index.blade.php` | View |
| `resources/views/mitra/index.blade.php` | View |

### File Dimodifikasi (Updated)

| Path | Perubahan |
|------|-----------|
| `app/Models/Salon.php` | `slug` di `$fillable`, `getRouteKeyName()` |
| `app/Models/SalonImage.php` | Accessor `url` → `image_url` |
| `app/Models/Kota.php` | Accessor `nama` → `nama_kota` |
| `routes/web.php` | Ditulis ulang: 18 named routes |
| `README.md` | Refreshed to English, routes table, Leaflet in stack |
| `progress.md` | Updated to ~70%, Phase 6 section added |
| `INTEGRATION_GUIDE.md` | ✅ banner + Deviations table |

---

## 4. Keputusan Teknis & Alasannya

| Keputusan | Alasan |
|-----------|--------|
| **3-step slug migration** (nullable → backfill → unique) | 8.750 baris harus di-backfill sebelum unique constraint bisa diterapkan tanpa crash |
| **Accessor `Kota.nama` dan `SalonImage.url`** (bukan rename kolom) | Migrasi existing pakai `nama_kota` dan `image_url`; accessor menjembatani tanpa alter schema |
| **Leaflet via CDN** (bukan npm package) | Lebih cepat setup, tidak perlu build step tambahan; graceful fallback jika CDN down |
| **UI full English** (bukan bilingual) | Data berasal dari Treatwell UK (salon UK, harga £), bilingual akan membingungkan |
| **Currency £ GBP** (bukan Rp IDR) | Konsisten dengan sumber data UK |
| **Search-based navigation** (`/cari?q=hair`) bukan kategori slug | DB punya 7.183 kategori granular Treatwell; tidak ada slug "rambut" yang match |
| **`BookingController` pakai `harga_at_order/subtotal`** | Field `harga/qty` dari guide tidak ada di migration existing; adaptasi ke schema real |
| **`welcome.blade.php` tetap di disk** | Route `/` sudah dialihkan ke `HomeController@index`; file dibiarkan untuk reference |
| **`/update/` folder tetap ada** | Untuk traceability — konten sudah usang setelah integrasi |

---

## 5. Verifikasi & QA Checklist

### Database
- [x] `php artisan migrate:status` — 22 migrasi, semua RAN
- [x] `Salon::whereNotNull('slug')->count()` = 8.750 (semua salon punya slug)
- [x] `Schema::hasColumn('order_detail', 'catatan')` = true
- [x] `Salon::first()->slug` returns `'novoblanc-london'`

### Routes
- [ ] `php artisan route:list` — confirm 18+ named routes
- [ ] Browser smoke test: `/`, `/cari?q=hair`, `/salon/{slug}`

### Views
- [ ] All 14 page views render without errors
- [ ] Leaflet maps load on search, category, and salon detail pages
- [ ] Booking wizard step navigation works (Alpine.js)
- [ ] Account pages accessible after login

---

## 6. Sisa Pekerjaan / Known Limitations

### Belum Diimplementasi
1. **Salon Owner Dashboard** — Statistik, CRUD service/staff, galeri foto, order management
2. **Admin Panel** — Manage salons, categories, promos, review moderation
3. **CheckRole Middleware** — `app/Http/Middleware/CheckRole.php` belum dibuat; role-based access belum ditegakkan
4. **Payment Flow** — Belum ada integrasi payment gateway; pembayaran diasumsikan "in-salon"
5. **Smart Booking** — Slot waktu statis 09:00–16:30 tanpa cek `staff_schedule` atau double-booking
6. **Review Submission** — Model & DB ready, tapi form belum ada; `ReviewController` belum dibuat

### Known Issues
1. `staff_schedule` tabel kosong (0 record) — booking time-slot logic return semua slot available
2. `order`, `review`, `pembayaran` tabel kosong — normal karena fitur baru
3. Leaflet CDN dependency — jika offline, peta tidak muncul (ada fallback "No map available")
4. `SalonImage.url` dan `Kota.nama` adalah accessor — SQL query harus tetap pakai `image_url` dan `nama_kota`
5. Logo images (`logo1.jpeg`, `logo2.jpeg`) mungkin belum ada di `public/images/` — `viygo-logo` component memiliki text fallback

### Test Coverage
- Unit test dan feature test belum ditulis
- Perlu: model relations, booking flow E2E, search/filter, auth, role permissions

---

## 7. Apendix

### A. Daftar Route Final (18 named routes)

```
Public:
  GET  /                        → home
  GET  /cari                    → cari
  GET  /kategori/{slug}         → kategori.show
  GET  /salon/{slug}            → salon.show
  GET  /gift-card               → gift-card
  GET  /lookbook                → lookbook
  GET  /treatment-files         → treatment-files
  GET  /mitra                   → mitra

Auth-protected:
  GET  /salon/{slug}/booking    → booking.create
  POST /salon/{slug}/booking    → booking.store
  GET  /booking/{kode}/konfirmasi → booking.konfirmasi
  POST /booking/{kode}/batal    → booking.batal
  GET  /akun                    → akun.index
  GET  /akun/bookings           → akun.bookings
  GET  /akun/favorit            → akun.favorit
  GET  /akun/pengaturan         → akun.pengaturan
  PUT  /akun/pengaturan         → akun.pengaturan.update
  GET  /akun/reward             → akun.reward
  GET  /dashboard               → dashboard
```

### B. Komponen Baru

| Component | Props | Fitur |
|-----------|-------|-------|
| `<x-leaflet-map>` | `id`, `height`, `center`, `zoom`, `markers`, `single`, `class` | OpenStreetMap tiles, marker popups, fitBounds, invalidateSize, deferred init |
| `<x-viygo-logo>` | — | Alpine.js cross-fade, `onerror` text fallback |
| `<x-viygo-navbar>` | — | 2-row, search bar, auth links, category links to `/cari?q=...` |
| `<x-viygo-footer>` | — | Treatment links, company info |
| `<x-salon-card>` | `$salon`, `$layout` | List/grid layout, £ formatting, favourite button |

### C. Contoh Penggunaan `<x-leaflet-map>`

**Multi-marker (search/category page):**
```blade
<x-leaflet-map
    height="100%"
    :markers="$mapMarkers"
/>
```

**Single marker (salon detail):**
```blade
<x-leaflet-map
    id="map-salon-{{ $salon->id_salon }}"
    height="280px"
    :center="[(float) $salon->latitude, (float) $salon->longitude]"
    :zoom="15"
    :markers="[[
        'lat'   => (float) $salon->latitude,
        'lng'   => (float) $salon->longitude,
        'title' => $salon->nama_salon,
        'url'   => '',
    ]]"
    single
/>
```

---

*Dokumen ini dibuat sebagai laporan akhir fase integrasi frontend VIYGO — 1 Mei 2026.*
