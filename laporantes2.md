# VIYGO — Laporan Progress & Roadmap ke Completion
**Tanggal:** 13 Mei 2026  
**Branch:** `feature/polish-round`  
**Dibuat oleh:** Claude Sonnet 4.6 (via Claude Code)

---

## RINGKASAN: SEBERAPA JAUH KITA SUDAH SAMPAI?

```
Progress Keseluruhan: ████████████████████░░░░░  78%
                      (kesiapan production, bukan feature completeness)

Feature completeness: ██████████████████████████  95%+
Production readiness: ████████████████████░░░░░  78%
```

Selisih 17% antara feature completeness dan production readiness disebabkan oleh:
- Payment gateway belum dikonfigurasi (revenue engine mati)
- Email belum dikirim ke user nyata
- Security setting masih development mode
- Test coverage masih minimal

---

## PROGRESS PER AREA (Detail)

| # | Area | % Selesai | Status | Blocker |
|---|------|-----------|--------|---------|
| 1 | Infrastruktur & Setup | **100%** | ✅ Done | — |
| 2 | Database Schema (29 migrasi) | **100%** | ✅ Done | — |
| 3 | Data Seeding (6.379 salon UK) | **100%** | ✅ Done | — |
| 4 | Model Eloquent (15 model) | **100%** | ✅ Done | — |
| 5 | Backend Controllers (13 ctrl) | **100%** | ✅ Done | — |
| 6 | Public Frontend (semua route) | **100%** | ✅ Done | — |
| 7 | Auth (Login/Register/2FA) | **95%** | ✅ Done | Email verify log-only |
| 8 | Booking Flow (wizard 3-step) | **80%** | ⚠️ Partial | Payment belum aktif |
| 9 | Payment (Midtrans Snap) | **30%** | ❌ Blocked | Server Key belum diset |
| 10 | Dashboard Customer | **90%** | ✅ Done | Cancel confirmed belum ada |
| 11 | Owner Panel (/owner) | **100%** | ✅ Fixed | BUG-13 sudah diperbaiki |
| 12 | Admin Panel (/admin) | **100%** | ✅ Fixed | BUG-13 sudah diperbaiki |
| 13 | Review & Rating System | **95%** | ✅ Done | Bergantung order `success` |
| 14 | Email Notifications | **20%** | ❌ Blocked | MAIL_MAILER=log |
| 15 | Automated Tests | **20%** | ⚠️ Partial | 21 panel tests ada, booking/auth belum |
| 16 | Keamanan Production | **60%** | ⚠️ Partial | APP_DEBUG=true |

---

## APA YANG SUDAH SELESAI ✅

### Infrastruktur & Backend (100%)
- Laravel 13 + Filament v5 + Livewire v3 + TailwindCSS v4 + Vite
- 29 migrasi database, semua berjalan
- 15 model Eloquent dengan SoftDeletes dan relasi lengkap
- 13 controller dengan 50+ route bernama
- Role middleware: `CheckRole`, `canAccessPanel()` per panel

### Public Frontend (100%)
- Homepage, Search, Kategori, SubKategori, Salon detail
- Booking wizard 3-step dengan slot dinamis (`BookingSlotService`)
- Dashboard customer: booking history, favourites, profil, reward
- Static pages: About, Careers, Press, Help, Contact, Privacy, Terms

### Panel Admin & Owner (100%) — *baru diperbaiki BUG-13*
- `/admin`: CRUD semua entitas + moderasi review + Mitra Applications
- `/owner`: statistik, orders, services, staff, jadwal, galeri, profil salon

### Data
- 6.379 salon UK, 190.594 services, 50.492 images, 8.180 staff
- Slug backfill selesai (semua salon punya slug SEO-friendly)

---

## APA YANG HARUS DISELESAIKAN — MENUJU PRODUCTION

### TAHAP 1: LAUNCH MINIMUM (estimasi: 1–2 hari kerja)
> Setelah tahap ini, app bisa demo + terima booking real

#### 1A. Aktifkan Payment Gateway
```
File: .env
MIDTRANS_SERVER_KEY=SB-Mid-server-XXXXXXXX
MIDTRANS_CLIENT_KEY=SB-Mid-client-XXXXXXXX
MIDTRANS_PRODUCTION=false  ← sandbox dulu
```
- Daftar akun di dashboard.midtrans.com → ambil Sandbox keys
- Test manual: booking → Midtrans Snap popup → bayar test → cek order status berubah ke `confirmed`
- Test webhook: pastikan `/payment/webhook` menerima notifikasi Midtrans dengan benar

**Dampak jika tidak dikerjakan:** Seluruh alur booking terhenti di step pembayaran. Revenue = 0.

#### 1B. Aktifkan Email (SMTP)
```
File: .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io   ← atau Mailgun/SES
MAIL_PORT=587
MAIL_USERNAME=xxxxx
MAIL_PASSWORD=xxxxx
MAIL_FROM_ADDRESS=noreply@viygo.com
MAIL_FROM_NAME="VIYGO"
```
- Test: isi form Contact → cek email masuk ke inbox
- Test: apply mitra → cek notifikasi ke admin
- Minimal untuk launch: Mailtrap (gratis, untuk testing)

**Dampak jika tidak dikerjakan:** Konfirmasi booking, notifikasi, dan form tidak kirim email nyata.

#### 1C. Security Production Settings
```
File: .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://viygo.com      ← ganti sesuai domain
APP_NAME=VIYGO                 ← saat ini masih "Laravel"
SESSION_DRIVER=database
```
**Dampak jika tidak dikerjakan:** Stack trace PHP bocor ke publik jika ada error.

---

### TAHAP 2: FLOW BISNIS LENGKAP (estimasi: 3–5 hari kerja)
> Setelah tahap ini, semua alur customer/owner/admin berjalan penuh

#### 2A. Email Konfirmasi Booking
**File yang perlu dibuat:**
- `app/Mail/BookingConfirmationMail.php` (Mailable class)
- `resources/views/emails/booking-confirmation.blade.php` (template)

**File yang perlu diubah:**
- `app/Http/Controllers/BookingController.php` method `store()` — tambah `Mail::to($user)->send(new BookingConfirmationMail($order))`

**Isi email:** nama salon, service, tanggal, jam, kode order, link `/akun/bookings`

#### 2B. "Mark as Completed" di Owner Panel
**File yang perlu diubah:**
- `app/Filament/Owner/Resources/OrderResource.php` — tambah action `mark_completed`

```php
\Filament\Actions\Action::make('mark_completed')
    ->label('Mark as Completed')
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->requiresConfirmation()
    ->action(fn (Order $r) => $r->update(['status' => 'success']))
    ->visible(fn (Order $r) => $r->status === 'confirmed')
```

**Kenapa penting:** Saat order jadi `success`, customer bisa leave review, revenue terhitung di statistik owner.

#### 2C. Cancel Booking untuk Order `confirmed` (Sudah Bayar)
**File yang perlu diubah:**
- `app/Http/Controllers/BookingController.php` method `batal()`

Saat ini hanya bisa cancel `pending`. Tambahkan:
- Boleh cancel `confirmed` jika `date_order > today + 1`
- Kirim notifikasi email ke owner
- (Opsional) Trigger Midtrans refund API

#### 2D. Reminder Booking H-1
**File yang perlu dibuat:**
- `app/Console/Commands/BookingReminder.php` — query order besok, kirim email
- Daftarkan di `routes/console.php`: `Schedule::command('bookings:remind')->dailyAt('09:00')`

---

### TAHAP 3: TEST COVERAGE (estimasi: 2–3 hari kerja)
> Setelah tahap ini, regresi bisa dideteksi otomatis

**21 test sudah ada** (panel admin + owner). Yang masih perlu dibuat:

#### 3A. Feature Tests Customer Flow
```
tests/Feature/BookingFlowTest.php
- test_guest_cannot_book()
- test_authenticated_user_can_complete_booking()
- test_booking_creates_order_record()
- test_payment_token_endpoint_returns_503_without_midtrans_key()
```

#### 3B. Feature Tests Auth & Role
```
tests/Feature/AuthRoleTest.php
- test_customer_cannot_access_admin_panel()
- test_customer_cannot_access_owner_panel()
- test_owner_only_sees_own_salon()
- test_review_only_allowed_after_success_order()
```

#### 3C. Unit Tests
```
tests/Unit/BookingSlotServiceTest.php
- test_slots_generated_from_salon_hours_when_no_schedule()
- test_booked_slot_is_excluded()
```

---

### TAHAP 4: ENHANCEMENT FUNGSIONAL (estimasi: 1 minggu)
> Fitur tambahan yang meningkatkan kualitas UX

#### 4A. Promo/Diskon di Booking Checkout
Tabel `user_promo` sudah ada. Yang perlu dikerjakan:
- Tambah input promo code di booking wizard step 3
- Validasi: promo milik user, belum expired, belum dipakai
- Update `BookingController::store()`: isi `id_promo` + `total_diskon`
- Mark promo `is_used = true` setelah booking selesai

#### 4B. Order Completion Scheduler (alternatif dari 2B)
Jika owner tidak manual mark, tambah Artisan command:
- `bookings:complete` — auto-complete order yang `date_order + end_time` sudah lewat 2 jam

#### 4C. Staff Schedule Data
Tabel `staff_schedule` masih kosong (0 record). Booking slot saat ini pakai jam buka salon sebagai fallback.
- Opsi: buat `StaffScheduleSeeder` dengan template jadwal standar (Mon–Sat, 09:00–18:00)
- Owner sudah bisa isi via Owner Panel → Staff → Schedules (UI sudah ada)

---

### TAHAP 5: FITUR GROWTH (estimasi: 2 minggu)
> Fitur untuk scale dan akuisisi user

#### 5A. Gift Card Backend
Frontend `/gift-card` sudah ada (marketing page). Backend perlu:
- Model `GiftCard` + migrasi tabel `gift_cards`
- Endpoint pembelian via Midtrans
- Validasi dan redemption saat booking checkout
- Admin UI untuk list dan manage gift cards

#### 5B. SEO
- Sitemap generator (`spatie/laravel-sitemap`) untuk 6.379 halaman salon
- Meta tags dinamis (`<title>`, `<meta description>`, `<og:image>`) per salon
- JSON-LD structured data untuk salon + services (schema.org)

#### 5C. Multi-Bahasa (EN/ID)
- `lang/id/*.php` untuk semua string UI
- Middleware `SetLocale` + toggle di navbar
- Prioritas: home, salon detail, booking wizard, akun

#### 5D. Performance
- Leaflet via npm (tidak CDN — CDN bisa down)
- Redis cache untuk query kategori dan kota
- Queue untuk email jobs

---

## ROADMAP VISUAL

```
SEKARANG (78%)
     │
     ▼
TAHAP 1 — 1-2 hari  → Set .env (Midtrans + SMTP + APP_DEBUG=false)
     │                  → 85% production ready
     ▼
TAHAP 2 — 3-5 hari  → Email konfirmasi + Mark Completed + Cancel confirmed
     │                  → 90% production ready  ← LAYAK LAUNCH
     ▼
TAHAP 3 — 2-3 hari  → Test coverage (booking, auth, role)
     │                  → 93% production ready
     ▼
TAHAP 4 — 1 minggu  → Promo integration + staff schedule + completion flow
     │                  → 96% production ready
     ▼
TAHAP 5 — 2 minggu  → Gift card + SEO + multi-lang + performance
                        → 100% production ready
```

---

## CHECKLIST SEBELUM LAUNCH (Minimum Viable)

```
□ MIDTRANS_SERVER_KEY diset di .env
□ MIDTRANS_CLIENT_KEY diset di .env
□ MAIL_MAILER=smtp (bukan log) dan konfigurasi SMTP valid
□ APP_DEBUG=false
□ APP_ENV=production
□ APP_NAME=VIYGO
□ APP_URL=https://domain-produksi.com
□ Test manual booking end-to-end berhasil
□ Test webhook Midtrans berhasil mengubah status order
□ Password default salon_owner (6.379 user dengan password "password") di-reset atau dinonaktifkan
□ php artisan key:generate (jika belum)
□ php artisan storage:link
□ php artisan config:cache && route:cache && view:cache
```

---

## RISIKO TEKNIS YANG PERLU DIPERHATIKAN

| Risiko | Level | Solusi |
|--------|-------|--------|
| 6.379 salon_owner punya password "password" | 🔴 KRITIS | Block login atau force reset sebelum launch |
| Leaflet CDN bisa down | 🟡 Medium | Pindah ke npm install |
| staff_schedule kosong | 🟡 Medium | Slot fallback berjalan, tapi tidak akurat |
| Mata uang GBP (data UK) | 🟡 Medium | Konversi ke IDR jika target pasar Indonesia |
| Queue belum pakai Redis | 🟢 Low | Sync queue cukup untuk early launch |
| APP_NAME masih "Laravel" | 🟢 Low | Ganti di .env sebelum launch |

---

## KESIMPULAN

**Status saat ini:** Aplikasi sudah fungsional secara teknis. Semua halaman bisa diakses, panel admin dan owner berjalan penuh (BUG-13 sudah diperbaiki), booking wizard berjalan. Yang memblokir production adalah konfigurasi external service (Midtrans, SMTP) dan beberapa pengaturan security.

**Estimasi total untuk production-ready:**
- Minimum viable launch: **1–2 hari** (hanya set .env)
- Full production launch: **2–3 minggu** (semua tahap)
- Feature-complete: **1 bulan** (termasuk gift card, SEO, multi-lang)
