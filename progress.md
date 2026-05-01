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
| | Frontend — Booking Flow (3-step wizard) | **SELESAI** | 75% |
| | Auth — Login/Register/2FA (Fortify) | **SELESAI** | 95% |
| | Dashboard User (Akun) | **SELESAI** | 70% |
| | Dashboard Salon Owner | **BELUM** | 0% |
| | Admin Panel | **BELUM** | 0% |
| | Review & Rating (data kosong, model siap) | **PARSIAL** | 30% |
| | Payment Flow | **BELUM** | 0% |

**Estimasi progress total: ~70%**

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
- [x] `salon` — profil salon (koordinat, jam buka, rating, soft delete) + **`slug` (unique)** ← BARU 1 Mei 2026
- [x] `promo` — promo & diskon
- [x] `service` — layanan salon
- [x] `staff` — karyawan salon
- [x] `order` — transaksi booking
- [x] `review` — ulasan pelanggan
- [x] `salon_images` — galeri foto (`is_primary`, `image_url`)
- [x] `staff_schedule` — jadwal kerja staf
- [x] `staff_service` — pivot staf ↔ layanan
- [x] `user_promo` — pivot promo
- [x] `order_detail` — detail layanan + **`catatan` (text nullable)** ← BARU 1 Mei 2026
- [x] `pembayaran` — record pembayaran
- [x] `sessions` — session table

### 3. Data Scraping & Seeding
- [x] Go scraper bersifat concurrent
- [x] Ekstraksi layanan via JSON-LD `hasOfferCatalog`
- [x] Data Hair, Face, Nails, Body sudah terscrape
- [x] JSON validator (`database/scripts/validate_json.php`)
- [x] Seluruh seeder idempotent (aman re-run)
- [x] **`SalonSlugBackfillSeeder`** untuk backfill 5.767 slug ← BARU 1 Mei 2026

**Data aktual di database (verified):**
| Tabel | Records |
|-------|---------|
| users | 5.769 |
| kota | 1.709 |
| kategori | 7.183 |
| salon | 5.767 (semua punya slug unik) |
| service | 190.594 |
| staff | 7.568 |
| salon_images | 50.492 |

### 4. Model Eloquent 
13/13 model dengan relasi lengkap dan SoftDeletes pada model utama.
**Update 1 Mei 2026:**
- `Salon` — `slug` ditambahkan ke `$fillable`, `getRouteKeyName()` mengembalikan `slug`
- `SalonImage` — accessor `url` → alias untuk `image_url`
- `Kota` — accessor `nama` → alias untuk `nama_kota`

### 5. Auth Scaffold (Livewire Fortify)
- [x] `/login`, `/register`, `/settings`, `/settings/security` (2FA)

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

---

## YANG MASIH PERLU DIKERJAKAN

### PRIORITAS 1 — Dashboard Salon Owner
- [ ] Statistik salon (total booking hari ini/bulan ini)
- [ ] Manajemen layanan (CRUD `service`)
- [ ] Manajemen staf (CRUD `staff` + `staff_schedule`)
- [ ] Daftar order masuk (update status)
- [ ] Manajemen galeri foto (`salon_images`)
- [ ] Edit profil salon

### PRIORITAS 2 — Admin Panel
- [ ] Manajemen semua salon (approve/reject `status = active`)
- [ ] Manajemen kategori & kota
- [ ] Manajemen promo global
- [ ] Laporan & statistik platform
- [ ] Moderasi review (`is_visible`)

### PRIORITAS 3 — Middleware & Role-Based Access
- [ ] Middleware `CheckRole` untuk `salon_owner` dan `admin`
- [ ] Daftarkan di `bootstrap/app.php`

### PRIORITAS 4 — Payment Flow
- [ ] Integrasi Stripe / payment gateway UK-friendly
- [ ] Update `pembayaran` setelah booking confirm
- [ ] Halaman pembayaran terpisah (saat ini pembayaran "in-salon")

### PRIORITAS 5 — Booking yang Lebih Pintar
- [ ] Cek slot availability berdasarkan `staff_schedule` (saat ini slot statis 09:00–16:30)
- [ ] Cek double-booking via query `OrderDetail`
- [ ] Pilih staff (saat ini default ke `null`)

### PRIORITAS 6 — Review System
- [ ] Form ulasan setelah booking `success`
- [ ] Display review user di profil mereka
- [ ] Validasi: hanya user dengan order `success` yang dapat review

### PRIORITAS 7 — Fitur Tambahan (Nice to Have)
- [ ] Wishlist / Favorit salon (perlu tabel pivot `user_favourites`)
- [ ] Notifikasi email (booking konfirmasi, reminder H-1)
- [ ] Sistem referral (UI sudah ada di akun.index)
- [ ] Multi-bahasa (EN/ID toggle) — saat ini fixed EN

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
[ ] OrderController.php (PENDING — payment integration)
[ ] ReviewController.php (PENDING — review submission)
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
3. **Booking slot statis** — saat ini grid 14 slot waktu (09:00–16:30) tanpa cek availability. Perlu integrasi `staff_schedule` + cek `OrderDetail` overlap.
4. **Middleware `role`** belum ada — owner & admin pages belum dapat dibatasi.
5. **`staff_schedule`** masih kosong (0 record) — perlu seeder.
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
| Salon Dashboard | (owner portal) | ⬜ TBD |
| Admin Panel | (admin) | ⬜ TBD |
