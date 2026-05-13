# VIYGO — Laporan Tes & Kelayakan Deployment
**Tanggal audit:** 13 Mei 2026  
**Branch:** `feature/polish-round`  
**Auditor:** Claude Sonnet 4.6 (via Claude Code)

---

## 1. RINGKASAN EKSEKUTIF

Project VIYGO adalah klon Treatwell.co.uk berbasis Laravel 13 + Filament v5 + Tailwind v4.  
Setelah pengujian menyeluruh, project ini **layak demo/staging** namun **belum siap production** karena beberapa blocker yang diuraikan di bawah.

| Kategori | Status |
|----------|--------|
| Infrastruktur | ✅ Lengkap |
| Database & Migrasi | ✅ Semua 29 migrasi berjalan |
| Public Frontend | ✅ Semua route 200 OK (setelah bug fix) |
| Auth & Role | ✅ Login/Register/2FA tersedia |
| Booking Flow | ⚠️ Berjalan teknis, tapi Midtrans belum dikonfigurasi |
| Payment | ❌ MIDTRANS_SERVER_KEY tidak diset → 503 |
| Email | ⚠️ Log-only (MAIL_MAILER=log), tidak kirim email nyata |
| Admin Panel | ✅ Filament `/admin` berjalan |
| Owner Panel | ✅ Filament `/owner` berjalan |
| Unit/Feature Tests | ❌ Belum ada test otomatis |

---

## 2. HASIL TES HTTP (Semua Route Publik)

Diuji menggunakan `curl` ke `http://localhost:8002`:

| Route | Method | Status | Keterangan |
|-------|--------|--------|------------|
| `/` | GET | **200** | Homepage berjalan, 8 salon top-rated tampil |
| `/cari` | GET | **200** | Search + filter berjalan |
| `/kategori/hair` | GET | **200** | Pivot `salon_kategori` terbaca |
| `/sub-kategori/ladies-haircuts` | GET | **200** | Pivot `salon_sub_kategori` terbaca |
| `/salon/{slug}` | GET | **200** | Detail salon + Leaflet map berjalan |
| `/salon/{id}` | GET | **200** | Fallback ID berjalan |
| `/gift-card` | GET | **200** | *(setelah bug fix)* |
| `/lookbook` | GET | **200** | |
| `/treatment-files` | GET | **200** | |
| `/mitra` | GET | **200** | *(setelah bug fix)* |
| `/about`, `/careers`, `/press` | GET | **200** | Static pages |
| `/help`, `/contact` | GET | **200** | |
| `/privacy`, `/terms`, `/cookies` | GET | **200** | |
| `/login`, `/register` | GET | **200** | Fortify Auth |
| `/admin` | GET | **302→login** | Filament admin redirect normal |
| `/owner` | GET | **302→login** | Filament owner redirect normal |
| `/akun/*` | GET | **302→login** | Auth gate berjalan |
| `/salon/{slug}/booking` | GET | **302→login** | Auth gate berjalan |

---

## 3. BUG DITEMUKAN & DIPERBAIKI

### BUG-KRITIS-01: PHP Syntax Error di `mitra/index.blade.php` dan `gift-card/index.blade.php`
- **Deskripsi:** Karakter apostrof (kontraksi bahasa Inggris seperti `We'll`, `don't`, `today's`) diescaping dengan `''` (gaya SQL) di dalam string PHP — ini bukan syntax PHP yang valid. PHP membutuhkan `\'` atau menggunakan double-quote `"..."`.
- **Dampak:** Route `/mitra` dan `/gift-card` selalu return HTTP 500.
- **File terdampak:** `resources/views/mitra/index.blade.php` (7 baris), `resources/views/gift-card/index.blade.php` (5 baris)
- **Status:** ✅ **Diperbaiki** — semua string dialihkan ke double-quote delimiter.

### BUG-KRITIS-02: Semua Slug Salon NULL (sudah ada fix sebelumnya tapi seeder belum dijalankan)
- **Deskripsi:** Kolom `salon.slug` untuk seluruh 6.379 salon bernilai NULL, meskipun `SalonSlugBackfillSeeder` sudah tersedia. URL salon di frontend menggunakan ID integer sebagai fallback (`/salon/6378`) karena logic `$salon->slug ?? $salon->id_salon` di templates.
- **Dampak:** URL tidak SEO-friendly. Route model binding berbasis slug tidak berjalan.
- **Status:** ✅ **Diperbaiki** — `php artisan db:seed --class=SalonSlugBackfillSeeder` dijalankan. 6.379 slug unik berhasil di-assign (contoh: `hair-by-ayesha-london`, `021-barbers`).

---

## 4. ALUR BISNIS YANG BELUM BERJALAN PENUH

### 4.1 PAYMENT (BLOCKER KRITIKAL)
- **Status:** ❌ Tidak berfungsi
- **Masalah:** `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` tidak diset di `.env`. Saat user mencoba bayar, endpoint `/booking/{kode}/payment/token` mengembalikan HTTP 503 dengan pesan:
  ```
  "Midtrans is not configured. Set MIDTRANS_SERVER_KEY in .env."
  ```
- **Dampak:** Seluruh booking flow terhenti di step pembayaran. Order dibuat tapi tidak bisa diselesaikan.
- **Solusi:** Daftarkan akun Midtrans Sandbox → isi `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` di `.env`.

### 4.2 EMAIL NOTIFICATIONS (TIDAK ADA)
- **Status:** ⚠️ Log-only
- **Masalah:** `MAIL_MAILER=log` di `.env`. Semua `Mail::raw(...)` di `StaticController`, `MitraController`, dan `PaymentController` hanya ditulis ke `storage/logs/laravel.log` — tidak dikirim ke inbox nyata.
- **Terdampak:**
  - Konfirmasi booking ke customer → tidak terkirim
  - Notifikasi aplikasi mitra ke tim → tidak terkirim
  - Contact form ke support → tidak terkirim
- **Solusi:** Set SMTP/Mailgun/SES di `.env` untuk production.

### 4.3 BOOKING FLOW — STAFF SCHEDULE KOSONG
- **Status:** ⚠️ Berjalan dengan fallback, kurang akurat
- **Masalah:** Tabel `staff_schedule` memiliki 0 record. `BookingSlotService` memiliki fallback: jika staff tidak punya schedule, dianggap mengikuti jam buka salon.
- **Dampak:** Slot booking tampil dan bisa dipilih (fallback berjalan), tapi tidak mencerminkan jadwal kerja staf yang sebenarnya.
- **Solusi:** Jalankan seeder jadwal staf, atau owner mengisi jadwal via Owner Dashboard.

### 4.4 ORDER STATUS SETELAH PEMBAYARAN — FLOW BELUM TERUJI
- **Status:** ⚠️ Belum ada data real
- **Masalah:** Tabel `order`, `review`, dan `pembayaran` masih 0 record. Karena Midtrans belum dikonfigurasi, tidak ada booking yang selesai sampai tahap `confirmed` atau `success`.
- **Dampak yang belum terverifikasi:**
  - Tab "Completed" di `/akun/bookings` (status `success`) → tidak ada data
  - Fitur "Leave a Review" → tidak bisa dicoba
  - Owner menerima order → tidak bisa diverifikasi
  - Webhook Midtrans → belum diuji dengan data nyata

### 4.5 PROMO / DISKON BELUM TERINTEGRASI KE BOOKING
- **Status:** ⚠️ UI ada, backend booking tidak memakai promo
- **Masalah:** `BookingController::store()` selalu mengisi `id_promo = null` dan `total_diskon = 0`. Tabel `user_promo` ada tapi tidak digunakan di flow booking.
- **Dampak:** User tidak bisa memakai promo/voucher saat booking.

### 4.6 CANCEL BOOKING HANYA UNTUK STATUS `pending`
- **Status:** ⚠️ Logika terbatas
- **Masalah:** `BookingController::batal()` hanya bisa cancel order dengan status `pending`. Order yang sudah `confirmed` (sudah dibayar) tidak bisa dibatalkan oleh user.
- **Dampak:** User yang sudah bayar tidak bisa cancel via UI — harus minta admin.
- **Rekomendasi:** Tambahkan refund logic atau izinkan cancel untuk `confirmed` jika H-X masih jauh.

### 4.7 GIFT CARD — BELUM ADA BACKEND
- **Status:** ❌ Frontend only
- **Masalah:** `/gift-card` adalah halaman marketing statis. Tidak ada model `GiftCard`, tidak ada endpoint pembelian, tidak ada redemption logic.

### 4.8 NOTIFIKASI EMAIL BOOKING KONFIRMASI & REMINDER H-1
- **Status:** ❌ Belum dibuat
- **Masalah:** Tercatat di progress.md sebagai TODO yang belum selesai. Tidak ada Mailable class, tidak ada queued job/scheduler untuk reminder.

### 4.9 SISTEM REFERRAL
- **Status:** ❌ Belum dibuat
- **Masalah:** Tercatat di progress.md sebagai TODO. Tidak ada tabel, tidak ada logic.

### 4.10 MULTI-BAHASA (EN/ID TOGGLE)
- **Status:** ❌ Belum dibuat
- **Masalah:** Seluruh UI bahasa Inggris (data UK). Tidak ada lang files atau toggle bahasa.

---

## 5. ANALISIS ALUR BISNIS PER ROLE

### 5.1 CUSTOMER (User)
| Alur | Status | Catatan |
|------|--------|---------|
| Register & Login | ✅ | Fortify, email verification |
| 2FA Setup | ✅ | `/settings/security` |
| Browse salon by kategori | ✅ | 7 kategori, 300+ sub-kategori |
| Search salon | ✅ | Query + filter kota |
| Lihat halaman salon | ✅ | Detail, gallery, map, services, reviews |
| Booking step 1–3 | ✅ | Service → Date/Time → Konfirmasi |
| Pembayaran Midtrans | ❌ | Server key belum diset |
| Lihat booking history | ✅ | Tab Upcoming/Completed/Cancelled |
| Cancel booking | ⚠️ | Hanya `pending`, bukan `confirmed` |
| Leave review | ✅ | Tersedia di tab Completed (tapi belum ada data) |
| Favorit salon | ✅ | Toggle, listed di `/akun/favorit` |
| Edit profil | ✅ | `/akun/pengaturan` |
| Lihat reward/promo | ⚠️ | UI ada, promo tidak bisa dipakai di booking |
| Beli gift card | ❌ | Frontend only, no backend |

### 5.2 SALON OWNER
| Alur | Status | Catatan |
|------|--------|---------|
| Login ke `/owner` panel | ✅ | Filament, scoped per salon |
| Lihat statistik (hari ini, bulan, rating) | ✅ | Widget `OwnerStatsOverview` |
| Lihat upcoming orders | ✅ | Widget + resource |
| Confirm / Mark success / Cancel order | ✅ | Di `OrderResource` |
| CRUD services | ✅ | ServiceResource + relation manager |
| CRUD staff | ✅ | StaffResource + SchedulesRelationManager |
| Atur jadwal staf | ✅ | Canonical day keys (Monday, Tuesday, ...) |
| Kelola galeri foto | ✅ | SalonImageResource + "Make Primary" |
| Edit profil salon | ✅ | Fields sensitif di-lock (status, slug, rating) |
| Receive payment notification | ⚠️ | Bergantung Midtrans webhook |
| Lihat review customer | ⚠️ | Admin yang moderasi `is_visible`, bukan owner |

### 5.3 ADMIN
| Alur | Status | Catatan |
|------|--------|---------|
| Login ke `/admin` | ✅ | `role=admin` + `is_active=true` |
| Dashboard stats + LatestOrders | ✅ | |
| CRUD Salon | ✅ | + Relations: Services, Staff, Images |
| CRUD Kategori | ✅ | |
| CRUD Kota | ✅ | Edit only (data scraping, jarang tambah manual) |
| CRUD Service | ✅ | |
| Lihat & Edit Order | ✅ | + OrderDetails relation |
| Moderasi Review | ✅ | Toggle `is_visible`, otomatis update salon.rating |
| Kelola Promo | ✅ | CRUD lengkap |
| Kelola User | ✅ | Create/Edit/Activate |
| Lihat Mitra Application | ✅ | ViewOnly + status update |

---

## 6. ANALISIS KEAMANAN

| Area | Status | Catatan |
|------|--------|---------|
| CSRF protection | ✅ | Aktif di semua form (kecuali webhook yang di-exempt) |
| SQL injection | ✅ | Eloquent ORM + parameterized queries |
| XSS | ✅ | Blade `{{ }}` auto-escape |
| Auth middleware | ✅ | `auth,verified` + `role:customer` |
| Filament access control | ✅ | `canAccessPanel()` per panel |
| Midtrans signature verification | ✅ | SHA512 double-check |
| Throttle rate limiting | ✅ | `/mitra/apply` (5/min), `/contact` (10/min), `/newsletter` (3/min) |
| SoftDeletes | ✅ | User, Salon, Service, Staff |
| APP_DEBUG=true | ⚠️ | Harus dimatikan sebelum production |
| Midtrans keys hardcoded | ✅ | Pakai env() dengan benar |
| Password hashing | ✅ | Bcrypt via `'hashed'` cast |
| Email verification | ✅ | Fortify MustVerifyEmail (dicomment di User tapi Fortify handle) |

---

## 7. ANALISIS PERFORMA & DEPLOYMENT READINESS

### Kesiapan Assets
- ✅ Vite production build sudah ada (`public/build/manifest.json`)
- ✅ TailwindCSS v4 compiled via Vite
- ⚠️ Leaflet diload via CDN (`unpkg.com`) — jika offline/CDN down, peta tidak muncul

### Database
- ✅ 29 migrasi semua berjalan
- ✅ Data scraping: 6.379 salon UK, 190.594 services, 50.492 salon images
- ✅ Slug backfill: 6.379 slug unik sudah di-assign
- ⚠️ staff_schedule kosong (0 record) — fallback aktif tapi tidak akurat
- ⚠️ order, review, pembayaran kosong (wajar, belum ada user nyata + Midtrans belum aktif)

### Konfigurasi yang WAJIB Diisi Sebelum Production
```env
# Wajib
MIDTRANS_SERVER_KEY=SB-Mid-server-...
MIDTRANS_CLIENT_KEY=SB-Mid-client-...

# Wajib untuk email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # atau Mailgun/SES
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...

# Wajib untuk production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://viygo.com

# Disarankan
SESSION_DRIVER=database
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## 8. KESIMPULAN & REKOMENDASI

### Kesiapan: **LAYAK STAGING / DEMO** — Belum Production

**Layak untuk:**
- Demo ke investor/stakeholder
- User testing terbatas (tanpa payment nyata)
- Testing internal staff

**Belum layak production karena:**
1. **Payment gateway tidak dikonfigurasi** — revenue engine tidak berjalan
2. **Email tidak terkirim** — konfirmasi booking tidak ada
3. **Tidak ada automated tests** — regresi sulit dideteksi
4. **APP_DEBUG=true** — error stack trace bisa bocor ke publik

### Prioritas Fix Sebelum Launch:
| Prioritas | Item | Estimasi |
|-----------|------|----------|
| P0 | Set Midtrans Sandbox keys di `.env` | 30 menit |
| P0 | Set SMTP config di `.env` | 30 menit |
| P0 | Set `APP_DEBUG=false` dan `APP_ENV=production` | 5 menit |
| P1 | Tambah cancel flow untuk order `confirmed` + refund | 1 hari |
| P1 | Notifikasi email booking konfirmasi (Mailable + Job) | 2 hari |
| P1 | Reminder H-1 via Scheduler | 1 hari |
| P2 | Feature test: Booking flow end-to-end | 3 hari |
| P2 | Feature test: Auth & Role | 1 hari |
| P3 | Promo/diskon integration ke `BookingController::store()` | 1 hari |
| P3 | Gift Card backend (model + purchase + redemption) | 3 hari |
| P4 | Multi-bahasa toggle (EN/ID) | 3 hari |
| P4 | Sistem referral | 2 hari |
