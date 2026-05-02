# VIYGO — Progress Tracker: Treatwell.co.uk Clone

> **Update terakhir:** 1 Mei 2026 — 23:00 WIB
> **Status branch aktif:** `go-fresh` (sync dengan remote)
> **Referensi:** [treatwell.co.uk](https://www.treatwell.co.uk)

---

## Ringkasan Progress Keseluruhan

| Fase | Area | Status | Estimasi % |
|------|------|--------|-----------|
| | Infrastruktur & Setup | **SELESAI** | 100% |
| | Database Schema | **SELESAI** | 100% |
| | Data Scraping & Seeding | **SELESAI** | 95% |
| | Model Eloquent (13 model) | **SELESAI** | 100% |
| | Backend / Controllers (10 controllers) | **SELESAI** | 100% |
| | Frontend — Landing Page | **SELESAI** | 90% |
| | Frontend — Search & Filter | **SELESAI** | 85% |
| | Frontend — Halaman Salon (+ Leaflet minimap) | **SELESAI** | 90% |
| | Frontend — Booking Flow (3-step wizard, dynamic slots) | **SELESAI** | 95% |
| | Auth — Login/Register/2FA (Fortify) | **SELESAI** | 95% |
| | Dashboard User (Akun) | **SELESAI** | 70% |
| | Dashboard Salon Owner | **SELESAI** | 90% |
| | Admin Panel | **SELESAI** | 100% |
| | Review & Rating (form + observer aggregate) | **SELESAI** | 90% |
| | Payment Flow (Midtrans Snap Sandbox) | **SELESAI** | 90% |

**Estimasi progress total: ~96%** *(updated 3 Mei 2026 setelah Bug Audit fix + TUGAS 7 (Dummy Pages Polish) selesai)*

---

## SUDAH SELESAI

### 1. Infrastruktur Proyek
- [x] Laravel 13 + Livewire Flux starter kit terinstal
- [x] Konfigurasi `composer.json`, `package.json`, `vite.config.js`
- [x] `.env` dikonfigurasi (`DB_DATABASE=viygo-go`)
- [x] Git repository aktif, branch `go-fresh` sync dengan GitHub
- [x] TailwindCSS v4 terintegrasi via Vite
- [x] **Leaflet 1.9.4** (CDN) untuk minimap interaktif

### 2. Database Schema (Migrasi)
- [x] `kota` — master data kota
- [x] `kategori` — kategori layanan (sudah ada `slug`)
- [x] `users` — customer, salon owner, admin (+ 2FA, softDelete)
- [x] `salon` — profil salon (koordinat, jam buka, rating, soft delete) + **`slug` (unique)**
- [x] `promo` — promo & diskon
- [x] `service` — layanan salon
- [x] `staff` — karyawan salon
- [x] `order` — transaksi booking
- [x] `review` — ulasan pelanggan
- [x] `salon_images` — galeri foto (`is_primary`, `image_url`)
- [x] `staff_schedule` — jadwal kerja staf
- [x] `staff_service` — pivot staf ↔ layanan
- [x] `user_promo` — pivot promo
- [x] `order_detail` — detail layanan + **`catatan` (text nullable)**
- [x] `pembayaran` — record pembayaran
- [x] `sessions` — session table

### 3. Data Scraping & Seeding
- [x] Go scraper bersifat concurrent
- [x] Ekstraksi layanan via JSON-LD `hasOfferCatalog`
- [x] Data Hair, Face, Nails, Body sudah terscrape
- [x] JSON validator (`database/scripts/validate_json.php`)
- [x] Seluruh seeder idempotent (aman re-run)
- [x] **`SalonSlugBackfillSeeder`** untuk backfill 5.767 slug

**Data aktual di database (verified):**
| Tabel | Records |
|-------|---------|
| users | 8.752 |
| kota | 1.709 |
| kategori | 7.183 |
| salon | 8.750 |
| service | 190.594 |
| staff | 7.568 |
| salon_images | 50.492 |

### 4. Model Eloquent 
13/13 model dengan relasi lengkap dan SoftDeletes pada model utama.

### 5. Auth Scaffold (Livewire Fortify)
- [x] `/login`, `/register`, `/settings`, `/settings/security` (2FA)

### 6. Admin Panel (Filament v5.6)
- [x] Otentikasi `FilamentUser` (hanya `role=admin` & `is_active=true`)
- [x] Navigation Groups: Marketplace, Transactions, Users
- [x] 7 Resources: Salon, Kategori, Kota, Service, Order, Review, Promo
- [x] Relasi Manager: Salon -> Services, Staff, Images. Order -> OrderDetails.
- [x] Widgets: StatsOverview, LatestOrders
- [x] Optimasi arsitektur dasar

---

## 🆕 PHASE 6 — PUBLIC FRONTEND INTEGRATION (1 Mei 2026)

Diintegrasikan dari folder `/update/` ke dalam aplikasi utama. Semua UI sudah dialihbahasakan ke Bahasa Inggris (data UK), mata uang £ GBP, peta Leaflet menggantikan placeholder statis.

### A. Migrasi Database (3 file)
- [x] `2026_05_01_000001_add_slug_to_salon_table.php`
- [x] `2026_05_01_000002_add_catatan_to_order_detail_table.php`
- [x] `2026_05_01_000003_add_unique_index_to_salon_slug.php`

### B. Controllers (10 file di `app/Http/Controllers/`)
- [x] `HomeController` — 8 salon top-rated + 8 kategori
- [x] `SearchController` — query patched (gunakan `nama_kota`), `harga-terendah` via `withMin`
- [x] `KategoriController` — patched: `withMin('services as min_harga', 'harga')`
- [x] `SalonController` — slug + id_salon fallback
- [x] `BookingController` — `store()` di-rewrite untuk schema `OrderDetail` yang ada (`harga_at_order`, `subtotal`, `start_time`, `end_time`, `catatan`)
- [x] `AkunController` — `updatePengaturan` validasi `first_name + last_name + email`
- [x] `GiftCardController`, `LookbookController`, `TreatmentFilesController`, `MitraController` (stubs)

### C. Routes
`routes/web.php` di-rewrite. 18 named routes terdaftar.

### D. Layout & Components (5 + 1 baru)
- [x] `layouts/public.blade.php` — Tailwind v4 + Leaflet CDN
- [x] `components/viygo-logo.blade.php` — Alpine.js cross-fade
- [x] `components/viygo-navbar.blade.php` — 2-row Treatwell-style
- [x] `components/viygo-footer.blade.php`
- [x] `components/salon-card.blade.php` — list & grid layout
- [x] `components/leaflet-map.blade.php` — **BARU**, reusable Leaflet component

### E. Page Views (14 file)
Semua dialihbahasakan ke Bahasa Inggris:
- [x] `home.blade.php` — hero, stats UK, kategori, salon populer, CTA
- [x] `cari/index.blade.php` — pencarian + Leaflet multi-marker map
- [x] `kategori/show.blade.php` — kategori + Leaflet multi-marker map
- [x] `salon/show.blade.php` — detail + **Leaflet single-marker map**
- [x] `booking/create.blade.php` — wizard 3-step
- [x] `booking/konfirmasi.blade.php` — success page
- [x] `akun/index.blade.php` — dashboard akun
- [x] `akun/bookings.blade.php` — tabs Upcoming/Completed/Cancelled
- [x] `akun/favorit.blade.php`
- [x] `akun/pengaturan.blade.php` — bind ke `first_name + last_name + email`
- [x] `akun/reward.blade.php` — VIYGO Rewards
- [x] `gift-card/index.blade.php` — nominal £25/£50/£100/£200
- [x] `lookbook/index.blade.php`
- [x] `treatment-files/index.blade.php`
- [x] `mitra/index.blade.php` — Salon partner sign-up

### F. Dokumentasi
- [x] `README.md` — refresh, English-friendly, daftar route publik
- [x] `progress.md` — file ini
- [x] `INTEGRATION_GUIDE.md` — banner COMPLETED + bagian deviations
- [x] `PROGRESS_REPORT.md` — log per-fase yang dapat dilanjut agent lain
- [x] `LAPORAN_PROYEK.md` — laporan kerja final
- [x] `readme-admin.md` — dokumentasi mendalam arsitektur Filament

---

## YANG MASIH PERLU DIKERJAKAN

### PRIORITAS 1 — Dashboard Salon Owner  ✅ SELESAI 2 Mei 2026
- [x] Statistik salon (total booking hari ini/bulan ini, pending, revenue, average rating)
- [x] Manajemen layanan (CRUD `service` — top-level + relation manager)
- [x] Manajemen staf (CRUD `staff` + `staff_schedule` relation manager)
- [x] Daftar order masuk (status update: confirm / mark success / cancel)
- [x] Manajemen galeri foto (`salon_images` — top-level resource + relation manager, "Make Primary")
- [x] Edit profil salon (terbatas — fields safety-locked: status, slug, rating)
- [x] Filament Panel kedua (`OwnerPanelProvider`) di `/owner`, scoped per `id_user`

### PRIORITAS 2 — Static Pages (Footer Links)  ✅ SELESAI 2 Mei 2026
- [x] Halaman statis: About Us, Careers, Blog (→ /treatment-files), Press
- [x] Halaman bantuan: Help Centre, Contact Us (dengan email support@viygo.com / help@viygo.com)
- [x] Halaman legal: Privacy Policy, Terms & Conditions, Cookie Policy
- [x] Binding icon social media (FB, IG, Tiktok) via `config/viygo.php`
- [x] `README-GAMBAR-STATIS.md` manifest untuk AI agent generasi gambar

### PRIORITAS 3 — Middleware & Role-Based Access  ✅ SELESAI 2 Mei 2026
- [x] Middleware `App\Http\Middleware\CheckRole` (alias: `role`)
- [x] Didaftarkan di `bootstrap/app.php`
- [x] `/akun/*` di-gate dengan `role:customer`
- [x] Navbar branch berdasarkan `auth()->user()->role` (customer→/akun, salon_owner→/owner, admin→/admin)

### PRIORITAS 4 — Payment Flow  ✅ SELESAI 2 Mei 2026
- [x] Integrasi Midtrans Payment Gateway Snap (Sandbox API) via `midtrans/midtrans-php` ^2.6
- [x] Halaman pembayaran terpisah `/booking/{kode}/payment` dengan Snap.pay() pop-up
- [x] Webhook Midtrans di `/midtrans/webhook` (CSRF di-bypass, signature SHA512 diverifikasi)
- [x] Update `pembayaran` setelah notif: `id_transaksi`, `snap_token`, `raw_response`, status (`completed`/`pending`/`failed`)
- [x] Order transition `pending → confirmed` saat status `capture` / `settlement`
- [x] `PaymentController::createSnapToken` idempotent (re-issue token jika user reload)

### PRIORITAS 5 — Booking yang Lebih Pintar  ✅ SELESAI 2 Mei 2026
- [x] `App\Services\BookingSlotService` — server-side slot generator
- [x] Cek slot availability berdasarkan `staff_schedule` (per `hari` weekday)
- [x] Cek double-booking via overlap query `OrderDetail.start_time/end_time` (status ≠ canceled)
- [x] Pilih staff dinamis (dropdown "Any staff" + per-staff option)
- [x] Endpoint JSON `/salon/{slug}/booking/slots` untuk dynamic fetch saat user pilih tanggal/staff
- [x] Re-verify availability di `BookingController::store` (anti-race condition)
- [x] Auto-pick staff jika user pilih "Any staff" (`pickStaffForSlot`)

### PRIORITAS 6 — Review System  ✅ SELESAI 2 Mei 2026
- [x] Form ulasan setelah booking `success` (`/akun/bookings/{kode}/review`)
- [x] Display review badge ("★ x/5 reviewed") di tab Completed `/akun/bookings`
- [x] Validasi: `whereDoesntHave('review')` + `status = success` + `id_user = auth()->id()` (404 di luar itu)
- [x] `ReviewObserver` recompute `salon.rating` + `salon.total_review` (`saveQuietly`) saat review create/update/delete (termasuk admin moderation toggle `is_visible`)
- [x] `SalonController` filter `is_visible = true` saat eager-load reviews ke halaman publik salon

### PRIORITAS 7 — Fitur Tambahan
- [x] Wishlist / Favorit salon (`user_favourites` pivot terbuat di TUGAS 8)
- [ ] Notifikasi email (booking konfirmasi, reminder H-1)
- [ ] Sistem referral
- [ ] Multi-bahasa (EN/ID toggle)
- [x] Dummy header pages (Gift Card / Lookbook / Treatment Files / Mitra) — polished 3 Mei 2026

### TUGAS 7 — Header Dummy Pages Polish  ✅ SELESAI 3 Mei 2026
- [x] `/gift-card` — hero gradient, 3-step flow, value picker (custom amount), 6 USPs, 6-item FAQ accordion, CTA footer
- [x] `/lookbook` — sticky category filter, editorial featured, 12-item masonry grid (per-look salon + price + duration), featured stylists block, CTA
- [x] `/treatment-files` — search bar, category tags, hero feature + 3 secondary, 9-article grid, topics index (8 topics with article counts), newsletter signup
- [x] `/mitra` — multi-stat hero, 3-step "live in 10 min", 9-benefit grid, 3-tier pricing card (free/7%/2.9%), 3 testimonials with growth stats, 6-item FAQ, full application form

### Bug Audit Fixes (3 Mei 2026)  ✅
- [x] BUG-01: `order.status` enum extended → `('pending','confirmed','success','canceled')` so Midtrans webhook can transition properly
- [x] BUG-03: `AkunController::bookings` Upcoming tab now matches both `pending` & `confirmed`; "Pay now" link added for unpaid
- [x] BUG-04: Owner monthly revenue widget includes `confirmed` (paid) orders, not just `success`
- [x] BUG-05: `order_detail.status` standardised on `canceled` (was `cancelled`)
- [x] BUG-06: Owner schedule form uses canonical capitalized day keys (`Monday` not `monday`)

---

## Checklist File (Status 1 Mei 2026)

### Models `app/Models/`
```
[x] User.php ← Updated: PK, relasi, SoftDeletes, accessor
[x] Kota.php ← Updated: accessor `nama`
[x] Kategori.php ← Aktif
[x] Salon.php ← Updated: `slug` fillable + getRouteKeyName
[x] Promo.php
[x] Service.php
[x] Staff.php
[x] Order.php
[x] Review.php
[x] SalonImage.php ← Updated: accessor `url`
[x] StaffSchedule.php
[x] OrderDetail.php
[x] Pembayaran.php
```

### Controllers `app/Http/Controllers/`
```
[x] Controller.php (base)
[x] HomeController.php
[x] SearchController.php
[x] KategoriController.php
[x] SalonController.php
[x] BookingController.php
[x] AkunController.php
[x] GiftCardController.php
[x] LookbookController.php
[x] TreatmentFilesController.php
[x] MitraController.php
[x] ReviewController.php ← Updated: create + store, recomputes salon aggregates via Observer
[x] PaymentController.php ← BARU: Midtrans Snap (createSnapToken, show, webhook)
```

### Routes `routes/web.php`
```
[x] / → HomeController@index
[x] /cari → SearchController@index
[x] /kategori/{slug} → KategoriController@show
[x] /salon/{slug} → SalonController@show
[x] /salon/{slug}/booking → BookingController@create / store (auth)
[x] /booking/{kode}/konfirmasi → BookingController@konfirmasi (auth)
[x] /booking/{kode}/batal → BookingController@batal (auth)
[x] /akun, /akun/bookings, /akun/favorit, /akun/pengaturan, /akun/reward (auth)
[x] /akun/bookings/{kode}/review (GET form + POST submit, role:customer)
[x] /salon/{slug}/booking/slots (GET JSON, dynamic slot generator)
[x] /booking/{kode}/payment (GET Snap host page) + /payment/token (POST)
[x] /midtrans/webhook (POST, CSRF-exempted, SHA512 signature-verified)
[x] /gift-card, /lookbook, /treatment-files, /mitra
[x] /dashboard (Flux)
```

### Views `resources/views/`
```
[x] layouts/public.blade.php ← BARU + Leaflet CDN
[x] layouts/app.blade.php ← Flux layout (untouched)
[x] welcome.blade.php ← Tetap ada (tidak di-route)
[x] components/viygo-logo
[x] components/viygo-navbar
[x] components/viygo-footer
[x] components/salon-card
[x] components/leaflet-map ← BARU
[x] home, cari, kategori, salon, booking, akun (×5), gift-card, lookbook, treatment-files, mitra
[x] pages/auth/* (Fortify)
```

---

## Testing Checklist
- [x] Manual relation test via tinker (sebelum integrasi)
- [ ] Unit test: model relasi
- [ ] Feature test: Booking flow end-to-end
- [ ] Feature test: Search & filter salon
- [ ] Feature test: Auth (login, register, 2FA)
- [ ] Feature test: Role permission (customer ≠ owner ≠ admin)

---

## Known Issues / Catatan Teknis

1. **Welcome page lambat (`welcome.blade.php`)** — tidak dihapus, hanya tidak di-route. `/` sekarang dilayani `HomeController@index`.
2. **`/update/` folder** — sengaja TIDAK dihapus untuk traceability. Kontennya sudah usang setelah 1 Mei 2026.
3. ~~**Booking slot statis** — saat ini grid 14 slot waktu (09:00–16:30) tanpa cek availability.~~ → ✅ Selesai (2 Mei 2026): `BookingSlotService` menghasilkan slot dinamis berdasarkan `salon.opening_time/closing_time`, `staff_schedule`, dan overlap `OrderDetail`. Step default 30 menit.
4. ~~**Middleware `role`** belum ada — owner & admin pages belum dapat dibatasi.~~ → ✅ Selesai (2 Mei 2026): `App\Http\Middleware\CheckRole` terdaftar sebagai alias `role`. Owner dan admin sekarang dilindungi via `canAccessPanel()` di User model (Filament).
5. **`staff_schedule`** masih kosong (0 record) — perlu seeder. (Catatan: `BookingSlotService` punya fallback "ikut jam buka salon" kalau staff belum punya schedule, jadi booking tetap jalan tanpa seeder.)
6. **`order`, `review`, `pembayaran`** masih 0 record — wajar karena fitur baru terintegrasi.
7. **Mata uang £ GBP** dipakai di seluruh UI karena data berasal dari Treatwell UK (5.767 salon UK). Konversi ke IDR dilakukan jika kelak perlu pasar Indonesia.
8. **Leaflet CDN dependency** — saat ini Leaflet 1.9.4 di-load dari `unpkg.com`. Jika offline atau CDN down, peta tidak muncul (graceful: komponen menampilkan "No map available"). dapat di-vendor via npm jika perlu.
9. **OrderDetail field mapping** — `BookingController` menggunakan `harga_at_order` & `subtotal` (skema existing) bukan `harga`/`qty` yang ada di `INTEGRATION_GUIDE.md`. Lihat bagian "Deviations" di guide.
10. **`SalonImage.url` & `Kota.nama`** adalah accessor, bukan kolom database — query SQL harus menggunakan `image_url` dan `nama_kota`.

---

## Referensi Halaman Treatwell yang Sudah Diduplikat

| Halaman Treatwell | URL Contoh | Status |
|-------------------|-----------|--------|
| Landing Page | treatwell.co.uk | `/` |
| Search Results | treatwell.co.uk/place/london/ | `/cari` |
| Salon Detail | treatwell.co.uk/place/[salon]/ | `/salon/{slug}` |
| Booking Wizard | treatwell.co.uk/book/[salon]/ | `/salon/{slug}/booking` |
| User Account | treatwell.co.uk/account/ | `/akun` |
| Login/Register | treatwell.co.uk/account/login/ | `/login` (Fortify) |
| Gift Card | treatwell.co.uk/gift-card/ | `/gift-card` |
| Lookbook / Inspiration | treatwell.co.uk/lookbook/ | `/lookbook` |
| Treatment Files / Blog | treatwell.co.uk/treatment-files/ | `/treatment-files` |
| Partner Sign-up | treatwell.co.uk/work-with-us/ | `/mitra` |
| Salon Dashboard | (owner portal) | `/owner` (Filament) |
| Admin Panel | (admin) | `/admin` (Filament) |
