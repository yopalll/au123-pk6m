# VIYGO — Progress Tracker v2
> **Update terakhir:** 13 Mei 2026 (update kedua)  
> **Branch aktif:** `feature/polish-round`  
> **Berdasarkan:** Audit teknis + tes runtime menyeluruh + fix panel navigation (Claude Code)

---

## Progres Keseluruhan: **~78%** (siap production)

> Angka ini mengukur **kesiapan production**, bukan completeness fitur.  
> Fitur sudah 95%+ selesai secara kode, tapi beberapa blocker runtime (Midtrans, email, tests) menarik angka turun.

| Fase | Area | Status | % |
|------|------|--------|---|
| | Infrastruktur & Setup | ✅ SELESAI | 100% |
| | Database Schema (29 migrasi) | ✅ SELESAI | 100% |
| | Data Scraping & Seeding | ✅ SELESAI + Slug Backfill | 100% |
| | Model Eloquent (13+2 model) | ✅ SELESAI | 100% |
| | Backend Controllers (13 controllers) | ✅ SELESAI | 100% |
| | Public Frontend (semua route 200) | ✅ SELESAI (bug fix: mitra, gift-card) | 100% |
| | Auth — Login/Register/2FA | ✅ SELESAI | 95% |
| | Booking Flow (wizard 3-step) | ✅ SELESAI (tapi payment belum) | 80% |
| | Payment (Midtrans Snap) | ❌ KODE SIAP, KEY BELUM DISET | 30% |
| | Dashboard User | ✅ SELESAI | 90% |
| | Dashboard Salon Owner (Filament /owner) | ✅ SELESAI (fix: ViewAction BUG-13) | 100% |
| | Admin Panel (Filament /admin) | ✅ SELESAI (fix: ViewAction BUG-13) | 100% |
| | Review & Rating System | ✅ SELESAI | 95% |
| | Email Notifications | ❌ MAIL_MAILER=log only | 20% |
| | Automated Tests | ⚠️ 21 PANEL TESTS (admin+owner) | 20% |
| | Keamanan Production | ⚠️ APP_DEBUG=true | 60% |

---

## Apa yang Sudah Dikerjakan (Sebelum Update Ini)

### ✅ SELESAI (s/d 3 Mei 2026 — Cleanup Round)

1. **Infrastruktur** — Laravel 13, Livewire Flux, TailwindCSS v4, Vite build, Git
2. **Database** — 29 migrasi, semua berjalan. Pivot tables: `salon_kategori`, `salon_sub_kategori`, `kategori_sub_kategori`, `user_favourites`
3. **Data** — 6.379 salon UK, 190.594 services, 50.492 images, 8.180 staff, 7.183 kategori (via Go scraper)
4. **Models** — 13 model Eloquent + `SubKategori`, `MitraApplication`. SoftDeletes pada semua model utama
5. **Controllers** — 13 controller: Home, Search, Kategori (+ showSub), Salon, Booking, Akun, Review, Payment, Mitra, GiftCard, Lookbook, TreatmentFiles, Static
6. **Routing** — 50+ route terdaftar, named routes lengkap
7. **Frontend Public** — Home, Search, Kategori, SubKategori, Salon detail, Booking wizard, Konfirmasi, Akun (5 halaman), Reward, Gift Card, Lookbook, Treatment Files, Mitra
8. **Auth** — Fortify: login, register, 2FA, email verification, settings
9. **Booking Wizard** — 3 step, slot dinamis `BookingSlotService`, anti race-condition, "Any staff" auto-pick
10. **Payment Midtrans** — Kode integrasi Snap selesai, webhook + SHA512 verify, idempotent token
11. **Review System** — Form review, `ReviewObserver` recompute rating, moderasi admin
12. **Favourites** — Toggle heart icon, `/akun/favorit` listing
13. **Owner Panel** — `/owner` Filament: statistik, orders, services, staff, schedule, gallery, edit profil
14. **Admin Panel** — `/admin` Filament: semua CRUD + relation managers + `MitraApplicationResource`
15. **Static Pages** — About, Careers, Press, Help, Contact, Privacy, Terms, Cookies
16. **Role Middleware** — `CheckRole` middleware, `/akun/*` → `role:customer`, owner/admin via `canAccessPanel()`
17. **SubKategori System** — model, pivot `salon_sub_kategori`, `kategori_sub_kategori`, route `/sub-kategori/{slug}`
18. **Bug fixes** — BUG-01,03,04,05,06,08,09,10 (lihat `README-BUG-AUDIT.md`)
19. **Form backends** — Mitra apply, Contact form, Newsletter subscribe

---

## Bug yang Ditemukan & Diperbaiki Saat Audit Ini (13 Mei 2026)

### ✅ BUG-11: Syntax Error di `mitra/index.blade.php` + `gift-card/index.blade.php`
- PHP string dengan apostrof di-escape `''` (gaya SQL) → seharusnya `\'` atau double-quote
- Dampak: Route `/mitra` dan `/gift-card` return 500
- Fix: Semua string bermasalah dialihkan ke double-quote delimiter (12 baris diperbaiki)

### ✅ BUG-12: Slug Salon Semua NULL (SalonSlugBackfillSeeder Belum Dijalankan)
- 6.379 salon tidak punya slug → URL pakai ID integer sebagai fallback
- Fix: `php artisan db:seed --class=SalonSlugBackfillSeeder` → 6.379 slug unik terisi

### ✅ BUG-13 (KRITIS): Panel Admin & Owner — Semua Resource Page Return 500
- **Root cause:** Di Filament v5, namespace `Filament\Tables\Actions` TIDAK memiliki action class (hanya `HeaderActionsPosition.php`). Semua action class (`ViewAction`, `EditAction`, `DeleteAction`, `BulkActionGroup`, `BulkAction`, `CreateAction`, `ForceDeleteAction`, dll.) dipindah ke namespace `Filament\Actions`.
- **Dampak:** Semua halaman resource di panel `/admin` dan `/owner` return HTTP 500 karena `Class "Filament\Tables\Actions\ViewAction" not found`. Navigasi ke halaman apapun di panel gagal.
- **File terdampak:** 21 file resource (admin + owner + relation managers)
- **Fix:** Replace `Tables\Actions\*` → `\Filament\Actions\*` di semua 21 file resource
- **Tambahan:** Tambah `'Partnerships'` ke `navigationGroups()` di `AdminPanelProvider` untuk `MitraApplicationResource`
- **Verifikasi:** 21 automated tests pass — 12 AdminPanelTest + 9 OwnerPanelTest ✅

---

## Yang Masih Harus Dikerjakan

### PRIORITAS 0 — BLOCKER PRODUCTION (Harus Selesai Sebelum Launch)

#### P0-A: Konfigurasi Midtrans Sandbox/Production
- [ ] Daftar akun Midtrans → dapatkan Server Key + Client Key
- [ ] Isi `.env`: `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`
- [ ] Test end-to-end: booking → payment → konfirmasi → webhook
- [ ] Set `MIDTRANS_PRODUCTION=true` untuk live

#### P0-B: Konfigurasi Email (SMTP)
- [ ] Pilih provider: Mailgun / SES / Mailtrap
- [ ] Isi `.env`: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- [ ] Test: contact form, mitra apply, booking confirmation

#### P0-C: Keamanan Production
- [ ] Set `APP_DEBUG=false` di `.env`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_URL=https://domain-produksi.com`
- [ ] Pastikan `SESSION_DRIVER=database` dan cache driver dikonfigurasi

---

### PRIORITAS 1 — FUNGSIONALITAS UTAMA YANG BELUM LENGKAP

#### P1-A: Email Konfirmasi Booking (untuk Customer)
- [ ] Buat `BookingConfirmationMail` (Mailable class)
- [ ] Kirim di `BookingController::store()` setelah order dibuat
- [ ] Template: nama salon, service, tanggal, jam, kode order

#### P1-B: Reminder Booking H-1
- [ ] Buat Artisan Command `bookings:remind`
- [ ] Daftarkan di `routes/console.php` atau `app/Console/Kernel.php`
- [ ] Jalankan via `schedule:run` (daily)

#### P1-C: Cancel Booking untuk Status `confirmed` (Sudah Bayar)
- [ ] Update `BookingController::batal()` — izinkan cancel `confirmed` dengan syarat:
  - Booking belum lewat (`date_order > today`)
  - Mungkin ada penalty/refund policy
- [ ] Tambahkan refund request ke Midtrans API (opsional)
- [ ] Notifikasi email ke owner saat booking dibatal

#### P1-D: Automated Tests (Minimal Feature Tests)
**Untuk Customer:**
- [ ] Test: register → login → booking → payment (mock Midtrans)
- [ ] Test: search & filter salon
- [ ] Test: review submission setelah order `success`

**Untuk Owner:**
- [ ] Test: owner hanya bisa akses salons miliknya
- [ ] Test: update order status

**Untuk Admin:**
- [ ] Test: hanya admin bisa akses `/admin`

---

### PRIORITAS 2 — PENINGKATAN FUNGSIONALITAS

#### P2-A: Integrasi Promo/Diskon ke Booking Flow
- [ ] Tambah field promo code di booking wizard (step 3 atau konfirmasi)
- [ ] Validate promo via `user_promo` pivot (is_used, expired_at)
- [ ] Update `BookingController::store()` → isi `id_promo` + `total_diskon`
- [ ] Mark promo as used saat booking selesai

**Dampak untuk User:** Bisa memakai voucher/promo saat checkout  
**Dampak untuk Admin:** Statistik penggunaan promo akurat

#### P2-B: Staff Schedule Seeder / Owner Workflow
- [ ] Owner isi jadwal staf via Owner Dashboard (sudah ada UI-nya)
- [ ] Atau: tambah `StaffScheduleSeeder` dengan data dummy untuk testing
- [ ] Verifikasi: slot booking lebih akurat setelah schedule diisi

#### P2-C: Order Completion Flow (pending → confirmed → success)
- [ ] Saat ini: Midtrans webhook mengubah `pending → confirmed`
- [ ] Yang belum ada: mekanisme ubah `confirmed → success` saat appointment selesai
- [ ] Opsi A: Owner klik "Mark as Completed" di owner panel ← **paling pragmatis**
- [ ] Opsi B: Artisan command `bookings:complete` (cek `date_order + end_time` sudah lewat)
- [ ] Saat order `success`: user bisa leave review, owner dapat revenue counted

---

### PRIORITAS 3 — FITUR LANJUTAN (Nice-to-Have)

#### P3-A: Gift Card Backend
- [ ] Model `GiftCard` + migrasi tabel `gift_cards` (code, nominal, is_used, id_user_pemilik)
- [ ] Endpoint pembelian gift card (integrasi Midtrans)
- [ ] Redemption: validasi kode saat booking checkout
- [ ] Admin UI untuk list gift cards

**Dampak untuk Customer:** Bisa beli + redeem gift card  
**Dampak untuk Business:** Revenue stream tambahan

#### P3-B: Notifikasi Real-time (opsional)
- [ ] Owner dapat notifikasi saat ada booking baru (Laravel Echo / Pusher)
- [ ] Customer dapat notifikasi saat booking dikonfirmasi owner

#### P3-C: Sistem Referral
- [ ] Tabel `referrals` (referrer, referred, reward, status)
- [ ] Unique referral link per user
- [ ] Reward otomatis ke referrer saat referred user selesai booking pertama

**Dampak untuk Customer:** Insentif mengajak teman  
**Dampak untuk Business:** Viral growth loop

#### P3-D: Multi-Bahasa (EN/ID Toggle)
- [ ] Laravel Localization files: `lang/en/*.php`, `lang/id/*.php`
- [ ] Middleware `SetLocale`
- [ ] Toggle di navbar
- [ ] Prioritaskan: home, salon detail, booking wizard, akun

---

### PRIORITAS 4 — POLISH & OPTIMIZATION

#### P4-A: SEO
- [ ] Sitemap generator (spatie/laravel-sitemap)
- [ ] Meta tags dinamis per salon page
- [ ] Structured data (JSON-LD) untuk salon + services

#### P4-B: Performance
- [ ] Vendor Leaflet via npm (tidak lagi CDN)
- [ ] Implement Redis cache untuk query kategori, kota
- [ ] Implement queue untuk email jobs
- [ ] Pagination salon di homepage (saat ini ambil 8 hard-coded)

#### P4-C: Image Handling
- [ ] Upload foto salon via Owner Dashboard (saat ini hanya simpan URL)
- [ ] Storage S3 atau lokal dengan Laravel Storage
- [ ] Image optimization (WebP, lazy loading)

---

## State Database (13 Mei 2026)

| Tabel | Records | Catatan |
|-------|---------|---------|
| users | 6.381 | 1 admin, 6.379 owner (auto-created), 1 customer |
| kota | 1.709 | Data UK |
| kategori | 7.183 (7 aktif) | Hair, Nails, Massage, Face, Body, Hair Removal, Men's |
| sub_kategori | 300+ aktif | |
| salon | 6.379 | Semua punya slug sekarang (setelah backfill) |
| service | 190.594 | |
| staff | 8.180 | |
| salon_images | 50.492 | |
| staff_schedule | 0 | Perlu diisi agar slot lebih akurat |
| order | 0 | Belum ada transaksi (Midtrans belum dikonfigurasi) |
| review | 0 | Bergantung order selesai |
| pembayaran | 0 | Bergantung Midtrans |
| mitra_applications | 0 | Form sudah bisa submit |
| salon_kategori | 13.229 | Pivot scraper |
| salon_sub_kategori | 33.621 | Pivot scraper |

---

## Rencana Sprint Selanjutnya

### Sprint 1 — Launch Preparation (1–2 hari)
1. Set Midtrans Sandbox keys di `.env`
2. Set SMTP config di `.env`
3. Set `APP_DEBUG=false`
4. Test end-to-end: register → booking → payment → konfirmasi
5. Test Owner panel: terima booking → confirm

### Sprint 2 — Core Flow Completion (3–5 hari)
1. Buat `BookingConfirmationMail` + kirim di booking store
2. Tambah "Mark as Completed" di Owner panel → trigger review eligibility
3. Fix cancel flow untuk order `confirmed`
4. Basic feature tests (minimal: booking + auth + role)

### Sprint 3 — Enhancement (1 minggu)
1. Promo/diskon integration ke booking
2. Reminder H-1 scheduler
3. Staff schedule seeder / owner onboarding
4. Leaflet vendor via npm

### Sprint 4 — Growth Features (2 minggu)
1. Gift Card backend
2. Sistem referral
3. Multi-bahasa EN/ID
4. SEO (sitemap, meta tags, JSON-LD)

---

## Known Issues & Technical Notes

1. **`staff_schedule` kosong** — Booking slot menggunakan jam buka salon sebagai fallback. Fungsional tapi tidak akurat.
2. **Mata uang £ GBP** — Data dari Treatwell UK. Konversi ke IDR diperlukan jika menyasar pasar Indonesia.
3. **Leaflet CDN** — Jika CDN down, peta tidak muncul. Solusi: vendor via npm.
4. **`user_promo`** pivot tidak dipakai di booking flow — promo UI ada di `/akun/reward` tapi tidak bisa di-redeem.
5. **Order `confirmed` tidak bisa di-cancel user** — Hanya admin/owner yang bisa update status.
6. **`SalonImage.url` & `Kota.nama`** adalah accessor, bukan kolom DB — query SQL pakai `image_url` dan `nama_kota`.
7. **6.379 user salon_owner** — Auto-created saat seeding, masing-masing linked ke 1 salon. Password default `password` (HARUS diubah sebelum production).
8. **APP_NAME=Laravel** di `.env` — Ganti ke `VIYGO` sebelum production.
