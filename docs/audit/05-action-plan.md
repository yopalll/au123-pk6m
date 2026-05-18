# 05 — Action Plan (Prioritized Task List)

> Urutan eksekusi yang sudah diprioritaskan. Dirancang agar dapat dikerjakan langsung oleh LLM agent atau dev manusia. Setiap task menyertakan: file target, deskripsi, kriteria selesai (DoD), dan referensi temuan.

**Konvensi:** Tandai checkbox saat selesai. **Jangan hapus task**, pindahkan ke section "Closed" di bawah agar history audit terjaga.

---

## P0 — Critical (kerjakan dalam 24-48 jam)

### [x] P0-1 — Rotasi semua secret + setup `.env` yang aman
- **Ref**: SEC-01, BUG-A03
- **Status**: ⚠️ Perlu tindakan MANUAL oleh developer.
- **DoD**: `git log -- .env` kosong; key sandbox bisa dipakai book + webhook.
- **Lihat**: `04-security-fix.md → SEC-01`

### [x] P0-2 — Patch `User::$fillable` (mass-assignment)
- **Ref**: BUG-A02, SEC-03
- **File**: `app/Models/User.php`
- **DoD**: `User::create(['role'=>'admin', ...])` mengabaikan field role; explicit assignment masih bekerja. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A02`

### [x] P0-3 — Lindungi `DatabaseSeeder::run` dari accidental wipe
- **Ref**: BUG-A04
- **File**: `database/seeders/DatabaseSeeder.php`
- **DoD**: `php artisan db:seed --env=production` tanpa flag berhenti dengan warning. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A04`

### [x] P0-4 — Cegah double-booking di DB layer
- **Ref**: BUG-A01
- **DoD**: Race-test (2 worker, 1 slot) hanya 1 yang sukses; pesan error muncul rapi. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A01`

### [x] P0-5 — Bersihkan stack-trace publik
- **Ref**: SEC-02
- **Status**: ⚠️ Perlu tindakan MANUAL. Set `APP_DEBUG=false` di staging/production.
- **DoD**: Buka `https://<staging-url>/foo-404` → halaman 404/500 standar tanpa stack-trace.

---

## P1 — High (dalam 1 minggu)

### [x] P1-1 — Gunakan `OrderStatus` constant di semua spot yang ketinggalan
- **Ref**: BUG-A05, BUG-A06
- **DoD**: `rg "'pending'|'confirmed'|'success'|'canceled'" app/` hanya muncul di `Constants/OrderStatus.php` & migration. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A05, BUG-A06`

### [x] P1-2 — Fix logic pembatalan booking same-day
- **Ref**: BUG-A07
- **DoD**: User book hari ini jam 18:00 → bisa di-cancel jam 09:00 hari yang sama. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A07`

### [x] P1-3 — `Midtrans::refund_key` unik per attempt
- **Ref**: BUG-A08
- **DoD**: `refund_key` mengandung timestamp suffix, tidak konflik pada retry. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A08`

### [x] P1-4 — Validasi tab `bookings()` dan fallback ke 'mendatang'
- **Ref**: BUG-A09
- **DoD**: `?tab=hax` → menampilkan tab 'mendatang', bukan semua order. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A09`

### [x] P1-5 — Bound check `convertGbpToIdr` + fail-safe Snap token
- **Ref**: BUG-A10
- **DoD**: `DomainException` di-throw jika IDR > 999,999,999. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A10`

### [x] P1-6 — Idempotency + transaction_time validation di webhook
- **Ref**: SEC-04
- **DoD**: Webhook dengan `transaction_id` yang sudah `completed` di-skip dengan log. ✅
- **Lihat**: `04-security-fix.md → SEC-04`

### [x] P1-7 — Throttle `/midtrans/webhook`
- **Ref**: SEC-05
- **DoD**: Webhook di-rate-limit 120/menit. ✅
- **Lihat**: `04-security-fix.md → SEC-05`

### [x] P1-8 — Batasi `id_service` array max:20
- **Ref**: SEC-06
- **DoD**: Array > 20 id_service → validation error 422. ✅
- **Lihat**: `04-security-fix.md → SEC-06`

### [x] P1-9 — Cache navbar Kategori (24h)
- **Ref**: OPT-01
- **DoD**: `Cache::remember('nav_kategori', 24h)` aktif, toArray() serialization. ✅
- **Lihat**: `02-optimizations-fix.md → OPT-01`

### [x] P1-10 — FULLTEXT index `service.nama`
- **Ref**: OPT-03
- **DoD**: Migration FULLTEXT sudah dijalankan. ✅
- **Lihat**: `02-optimizations-fix.md → OPT-03`

### [x] P1-11 — Rebrand Filament admin panel
- **Ref**: ANOM-08
- **DoD**: Panel sudah menggunakan brandName 'VIYGO Admin' dan warna brand. ✅ (sudah ada sebelum audit ini)

---

## P2 — Medium (dalam 1 bulan)

### [x] P2-1 — `itemDetails()` hitung gross_amount dari sum items
- **Ref**: BUG-A11
- **DoD**: `sum(items[*].price)` dipakai sebagai gross_amount, bukan konversi independen. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A11`

### [x] P2-2 — `KategoriController::showSub` kirim model proper
- **Ref**: BUG-A12
- **DoD**: `$kategori` adalah instance Eloquent dari `$sub->kategori`, bukan stdClass. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A12`

### [x] P2-3 — `ReviewObserver` gunakan `withTrashed`
- **Ref**: BUG-A13
- **DoD**: `Salon::withTrashed()->find($idSalon)` dipakai di recompute(). ✅
- **Lihat**: `01-bugs-fix.md → BUG-A13`

### [x] P2-4 — `Salon::scopeActive` include owner-active filter
- **Ref**: BUG-A14
- **DoD**: Salon dengan owner `is_active=false` tidak muncul di listing publik. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A14`

### [x] P2-5 — `updatePengaturan` mendukung `phone_number`
- **Ref**: BUG-A15
- **DoD**: Phone number tersimpan di profile; dipakai Midtrans customer_details. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A15`

### [x] P2-6 — `BookingSlotService` consolidate `whereHas` (1 query)
- **Ref**: OPT-02
- **DoD**: N subquery EXISTS → 1 whereHas dengan COUNT check. ✅
- **Lihat**: `02-optimizations-fix.md → OPT-02`

### [x] P2-7 — Cache homepage featured salons
- **Ref**: OPT-04
- **DoD**: `Cache::remember('home.featured_salons', 30min)` aktif. ✅
- **Lihat**: `02-optimizations-fix.md → OPT-04`

### [x] P2-8 — Composite index `(status, rating)` & `(status, total_review)` salon
- **Ref**: OPT-07
- **DoD**: Migration composite indexes sudah dijalankan. ✅
- **Lihat**: `02-optimizations-fix.md → OPT-07`

### [x] P2-9 — Composite index `(status, date_order)` order
- **Ref**: OPT-08
- **DoD**: Migration composite index sudah dijalankan. ✅
- **Lihat**: `02-optimizations-fix.md → OPT-08`

### [x] P2-10 — Filter service `harga > 0` di booking flow
- **Ref**: ANOM-04
- **DoD**: Service dengan harga £0.00 tidak muncul di step Pick Service. ✅
- **Lihat**: `03-anomalies-fix.md → ANOM-04`

### [x] P2-11 — Buat `app/Constants/UserRole.php` & `OrderDetailStatus.php`
- **Ref**: SEC-08, BUG-A18
- **DoD**: Constants class tersedia dan dipakai. ✅
- **Lihat**: `04-security-fix.md → SEC-08`, `01-bugs-fix.md → BUG-A18`

### [ ] P2-12 — Konsolidasi `docs/` & `viygo_docs/` jadi satu pohon
- **Ref**: ANOM-03
- **Status**: Memerlukan keputusan tim. Dicatat sebagai sprint dokumentasi.

### [ ] P2-13 — Hapus / archive folder `update/`, `eror_v1/`, `eror_v2/`
- **Ref**: ANOM-01, ANOM-02
- **Status**: Memerlukan koordinasi tim + Git history cleanup.

### [ ] P2-14 — Honeypot di `/mitra/apply` & `/contact`
- **Ref**: SEC-07
- **Status**: Dicatat. Implementasi di sprint frontend berikutnya.

### [x] P2-15 — Schedule `bookings:complete` set timezone `Europe/London`
- **Ref**: ANOM-14
- **DoD**: `->timezone('Europe/London')` ditambahkan ke Schedule. ✅
- **Lihat**: `03-anomalies-fix.md → ANOM-14`

---

## P3 — Low (opportunistic)

### [x] P3-1 — Pindah `clean_md.php`, `test-json.php`, `test_panel_routes.php` ke `scripts/`
- **Ref**: OPT-10, ANOM-10
- **DoD**: Root project bersih dari script ad-hoc. ✅
- **Lihat**: `02-optimizations-fix.md → OPT-10`, `03-anomalies-fix.md → ANOM-10`

### [ ] P3-2 — Self-host Leaflet vendor (atau fallback)
- **Ref**: OPT-09
- **Status**: Dicatat. Low risk, dilakukan opportunistically.

### [ ] P3-3 — Move JSON dataset ke Git LFS / S3
- **Ref**: OPT-11
- **Status**: Perlu keputusan infrastruktur.

### [x] P3-4 — `CheckRole::handle` simplify property check
- **Ref**: BUG-A16
- **DoD**: `property_exists()` check dihapus, langsung cek `$user->is_active === false`. ✅
- **Lihat**: `01-bugs-fix.md → BUG-A16`

### [x] P3-5 — Translate komentar Indonesia → English saat file disentuh
- **Ref**: ANOM-11
- **Status**: Partial — file yang disentuh dalam audit ini sudah menggunakan komentar Bahasa Inggris.

### [x] P3-6 — Mail::raw subject sanitization (CRLF guard)
- **Ref**: SEC-09
- **DoD**: `strip_tags(preg_replace('/[\r\n]+/', ' ', ...))` dipakai di MitraController. ✅
- **Lihat**: `04-security-fix.md → SEC-09`

### [ ] P3-7 — Migration `order_detail.status` baseline pakai `canceled`
- **Ref**: BUG-A17
- **Status**: Skipped — hanya dilakukan jika tidak ada environment yang sudah running.

### [ ] P3-8 — Dokumentasikan `phpunit.panel.xml`
- **Ref**: ANOM-09
- **Status**: Didokumentasikan di `03-anomalies-fix.md`. README update optional.

---

## Closed

> Task yang selesai dipindahkan ke atas dalam section masing-masing dengan status `[x]`.

---

## Tindakan Manual Tersisa (Tidak Bisa Otomatis)

| Task | Alasan Manual |
|------|---------------|
| SEC-01 / BUG-A03: Rotate credentials | Memerlukan login ke Midtrans dashboard + Git history rewrite |
| SEC-02: Set APP_DEBUG=false | Memerlukan akses environment staging/production |
| P2-13: Hapus folder `update/`, `eror_v1/`, `eror_v2/` | Memerlukan koordinasi tim + `git rm` |
| P2-12: Konsolidasi docs | Memerlukan keputusan tim |
| P2-14: Honeypot/CAPTCHA | Sprint frontend terpisah |
| P3-2: Self-host Leaflet | Sprint frontend terpisah |
| P3-3: JSON dataset ke LFS/S3 | Keputusan infrastruktur |
| P3-7: Migration baseline `canceled` | Risiko tinggi jika ada environment running |

---

## Catatan untuk LLM Eksekutor

1. **Selalu mulai dari P0**. Jangan masuk P1 sebelum P0 hijau.
2. Sebelum patch, **lihat `01-bugs.md` / `02-optimizations.md` / etc.** untuk konteks lengkap & snippet patch siap-tempel.
3. **Jangan ubah constant name** (`OrderStatus::PENDING` dll) — sudah dipakai banyak file.
4. **Jangan rename kolom DB** (`harga`, `durasi`, `nama_salon`) — terlanjur. Ini di-justify oleh ANOM-05; rename harus jadi proyek terpisah.
5. **Untuk migration baru**, gunakan format tanggal kongruen: `YYYY_MM_DD_HHMMSS_*`.
6. **Semua patch yang menyentuh status enum** wajib melalui migration `DB::statement("ALTER TABLE ... MODIFY status ENUM(...)")` agar konsisten dengan style file `2026_05_02_110000_*`.
7. Setelah patch, jalankan minimum:
   ```bash
   php artisan migrate
   php artisan optimize:clear
   composer test
   npm run build
   ```
8. Update [`02-optimizations.md`](02-optimizations.md), [`03-anomalies.md`](03-anomalies.md), [`04-security.md`](04-security.md) jika temuan baru muncul saat patching.
