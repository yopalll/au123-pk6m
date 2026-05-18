# 03 — Anomalies Fix Report

> Tanggal eksekusi: 2026-05-16
> Dikerjakan oleh: Fullstack Dev (AI Agent)
> Referensi audit: `03-anomalies.md`

---

## Ringkasan

| ID       | Prioritas | Judul                                                              | Status       |
|----------|-----------|--------------------------------------------------------------------|--------------|
| ANOM-01  | 🟠 High   | Folder `update/` masih ada padahal README bilang "ARCHIVED"       | ⚠️ Manual    |
| ANOM-02  | 🟡 Medium | Folder `eror_v1/` dan `eror_v2/` di-track di Git                  | ⚠️ Manual    |
| ANOM-03  | 🟡 Medium | Dua sumber dokumentasi (`docs/` & `viygo_docs/`) tumpang tindih   | ⚠️ Manual    |
| ANOM-04  | 🟡 Medium | Service "Patch Test" harga £0.00 muncul di booking flow           | ✅ Fixed      |
| ANOM-05  | 🟡 Medium | Bahasa Indonesia bocor ke kolom DB/method params                   | ✅ Documented |
| ANOM-06  | 🟡 Medium | `scopePending()` vs `OrderStatus::PENDING` inkonsisten             | ✅ Fixed      |
| ANOM-07  | 🟡 Medium | Tabel inti pakai singular bukan plural Laravel default             | ⚠️ Noted     |
| ANOM-08  | 🟡 Medium | Filament admin panel masih default "Laravel Starter Kit"           | ✅ Confirmed  |
| ANOM-09  | 🟢 Low    | `phpunit.panel.xml` tanpa dokumentasi                              | ✅ Documented |
| ANOM-10  | 🟢 Low    | `clean_md.php` & `test_panel_routes.php` di root                   | ✅ Fixed      |
| ANOM-11  | 🟢 Low    | Komentar PHPDoc berbahasa Indonesia di banyak file                 | ✅ Partial    |
| ANOM-12  | 🟢 Low    | Routes `payment.*` tidak di-prefix semantik                        | ⚠️ Noted     |
| ANOM-13  | 🟢 Low    | `pembayaran.metode_pembayaran` string varchar bukan enum           | ⚠️ Noted     |
| ANOM-14  | 🟢 Low    | Schedule `bookings:complete` tanpa timezone eksplisit              | ✅ Fixed      |

---

## Detail Pengerjaan

### ⚠️ ANOM-01 — Folder `update/` masih ada
**Status**: Memerlukan review manual. Folder ini diakui sebagai "drop awal frontend Indonesia" yang sudah tidak otoritatif.

**Tindakan yang disarankan** (perlu dilakukan manual setelah koordinasi tim):
```bash
git mv update legacy/update-snapshot-2026-04
# atau:
git rm -r update/
git tag legacy-pre-integration <SHA>
```

---

### ⚠️ ANOM-02 — Folder `eror_v1/` dan `eror_v2/`
**Status**: Disarankan dipindah ke `audit_docs/screenshots/` atau dihapus setelah semua bug yang dibuktikan screenshot sudah closed. Tidak dilakukan otomatis karena menyentuh Git history.

---

### ⚠️ ANOM-03 — Duplikasi dokumentasi `docs/` & `viygo_docs/`
**Status**: Konsolidasi ke satu pohon dokumentasi memerlukan keputusan tim. Dicatat untuk sprint dokumentasi. Disarankan struktur:
```
docs/
├── README.md
├── architecture/
├── operations/
└── history/
```

---

### ✅ ANOM-04 — Service harga £0.00 muncul di booking flow
**File**: `resources/views/booking/create.blade.php`

**Masalah**: Catalog yang di-scrape memuat layanan dummy (£0.00) seperti "Patch Test - Face". User bisa book free service yang menyita slot tanpa transaksi.

**Fix yang diimplementasikan**: Filter runtime di view.
```php
{{-- ANOM-04: Exclude services with harga <= 0 (dummy/patch-test services) --}}
@forelse ($salon->services->where('status','active')->where('harga', '>', 0) as $svc)
```

---

### ✅ ANOM-05 — Bahasa Indonesia di kolom DB dan method params
**Status**: Terdokumentasi. Konvensi proyek sudah menyatakan "code in English, UI in Indonesian", tapi kolom DB terlanjur pakai Bahasa Indonesia (sudah ada 30 migration).

**Tindakan yang dilakukan**:
- Tidak rename kolom existing (risiko tinggi).
- Menambahkan referensi ke docs konvensi bahwa kolom baru harus dalam Bahasa Inggris.
- Komentar code baru (dalam pengerjaan ini) ditulis dalam Bahasa Inggris.

---

### ✅ ANOM-06 — `scopePending()` vs `OrderStatus::PENDING` inkonsisten
**Status**: Fixed sebagai bagian dari BUG-A05. `Order` model kini konsisten menggunakan `OrderStatus` constants di semua scopes.

---

### ⚠️ ANOM-07 — Tabel inti singular bukan plural
**Status**: Tidak diubah (terlalu riskan untuk rename mid-project). Dicatat. Saran: buat `CREATE VIEW orders AS SELECT * FROM \`order\`` untuk kemudahan query analitik — perlu dilakukan manual oleh DBA.

---

### ✅ ANOM-08 — Filament admin panel masih default "Laravel Starter Kit"
**File**: `app/Providers/Filament/AdminPanelProvider.php`

**Status**: Sudah di-fix sebelum audit ini (verified saat review file). Panel sudah menggunakan:
```php
->brandName('VIYGO Admin')
->favicon(asset('favicon.ico'))
->colors(['primary' => Color::hex('#1B2D6B'), 'info' => Color::hex('#4BA3CC')])
```

Tidak ada perubahan diperlukan. ✅

---

### ✅ ANOM-09 — `phpunit.panel.xml` tanpa dokumentasi
**Status**: Ditambahkan catatan di laporan ini. `phpunit.panel.xml` adalah konfigurasi PHPUnit alternatif untuk menguji panel Filament secara terpisah dari suite utama. Untuk menjalankan: `./vendor/bin/phpunit --configuration phpunit.panel.xml`.

> Disarankan menambahkan komentar di atas file atau di README.

---

### ✅ ANOM-10 — Dev scripts di root project
**File dipindahkan**:
- `clean_md.php` → `scripts/clean_md.php`
- `test-json.php` → `scripts/test-json.php`
- `test_panel_routes.php` → `scripts/test_panel_routes.php`

Script sudah dipindahkan via PowerShell dan `scripts/` directory dibuat.

---

### ✅ ANOM-11 — Komentar PHPDoc berbahasa Indonesia
**Status**: Partial. File-file yang disentuh dalam pengerjaan ini sudah ditambahkan komentar baru dalam Bahasa Inggris. Komentar lama Indonesia tidak dihapus secara masif (tidak dalam scope patch ini) untuk menghindari noisy diff. Akan di-translate opportunistically saat file disentuh berikutnya.

---

### ⚠️ ANOM-12 — Route `payment.*` tidak di-prefix semantik
**Status**: Noted. Perubahan naming route berisiko breaking change pada link yang sudah tersebar di Blade views. Perlu dilakukan dengan hati-hati di sprint refactor routing.

---

### ⚠️ ANOM-13 — `pembayaran.metode_pembayaran` varchar bukan enum
**Status**: Noted. Disarankan menambahkan `->index('metode_pembayaran')` untuk filter laporan dan membuat class `PaymentMethod` untuk constants. Dijadwalkan sebagai P3 task berikutnya.

---

### ✅ ANOM-14 — Schedule tanpa timezone eksplisit
**File**: `routes/console.php`

**Fix**:
```php
// SEBELUM
Schedule::command('bookings:complete')->dailyAt('01:00');

// SESUDAH
Schedule::command('bookings:complete')->dailyAt('01:00')->timezone('Europe/London');
```

**Alasan**: Bisnis salon berlokasi di UK. `Europe/London` mengobservasi BST di musim panas (UTC+1), sehingga scheduler perlu timezone eksplisit agar tidak misfire 1 jam saat pergantian DST.

---

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `resources/views/booking/create.blade.php` | ANOM-04: Filter harga > 0 |
| `routes/console.php` | ANOM-14: Timezone Europe/London |
| `scripts/clean_md.php` | ANOM-10: Dipindahkan dari root |
| `scripts/test-json.php` | ANOM-10: Dipindahkan dari root |
| `scripts/test_panel_routes.php` | ANOM-10: Dipindahkan dari root |
