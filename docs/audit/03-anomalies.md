# 03 — Anomalies & Code-smell

> Hal-hal yang membuat onboarding / maintenance susah, tapi tidak meledak. Sebaiknya dibersihkan agar repo terasa "ringan".

---

## 🟠 ANOM-01 — Folder `update/` di root masih ada padahal README bilang "ARCHIVED"

**Path**: `update/app/`, `update/resources/`, `update/routes/`

`docs/PROJECT-ANALYSIS.md` & `README.md` mengakui folder `update/` adalah drop awal frontend Indonesia, "tidak otoritatif". Tapi masih tertinggal di repo. Risiko:
- Developer baru bingung mana sumber yang valid.
- Autoload Composer **tidak** memasukkan `update/app/`, tapi jika ada migration/route di sana, bisa fatal.

### Tindakan
```bash
git mv update legacy/update-snapshot-2026-04
# atau langsung:
git rm -r update/
```
Tambahkan tag git jika perlu histori: `git tag legacy-pre-integration <SHA>`.

---

## 🟡 ANOM-02 — Folder `eror_v1/` dan `eror_v2/` ikut di-track

Screenshot bug bukan asset produksi. Pindah ke `audit_docs/screenshots/` atau hapus setelah bug ditutup.

---

## 🟡 ANOM-03 — Dua sumber dokumentasi (`docs/` dan `viygo_docs/`) dengan isi tumpang tindih

- `docs/AGENT_GUIDE.md` ↔ `viygo_docs/guides/AGENT_GUIDE.md` — duplikat.
- `docs/INTEGRATION_GUIDE.md` ↔ `viygo_docs/guides/INTEGRATION_GUIDE.md`.
- `docs/PROJECT-ANALYSIS.md` standalone.
- `viygo_docs/reports/LAPORAN_PROYEK.md` standalone.
- `viygo_docs/bugs/REPORT-PHASE-{1..4}.md` standalone.

### Tindakan
Konsolidasi ke satu pohon, mis. `docs/` saja:
```
docs/
├── README.md          (index)
├── architecture/      (AGENT_GUIDE, eloquent-models, INTEGRATION_GUIDE)
├── operations/        (BRANCHING-STRATEGY, deploy, runbooks)
└── history/           (LAPORAN_PROYEK, REPORT-PHASE-1..4)
```
`audit_docs/` (folder ini) dipertahankan sebagai *external auditor pack* yang bisa di-archive bersama setiap audit run.

---

## 🟡 ANOM-04 — Service "Patch Test" dengan harga £0.00 / £0.01 muncul di booking flow

**Bukti**: `eror_v2/Screenshot 2026-05-13 222833.png`

Catalog yang di-scrape memuat layanan dummy (£0.00) seperti "Patch Test - Face". User awam bisa klik "Select" dan men-book free service yang menyita slot tanpa transaksi.

### Tindakan
Filter di seeder atau di runtime:
```php
$salon->services->where('status','active')->where('harga','>',0)
```
Atau tandai service dummy dengan flag `is_addon=true` agar tidak muncul standalone (hanya addon).

---

## 🟡 ANOM-05 — Bahasa Indonesia bocor ke kolom DB/Service (mix code-language)

Contoh: `harga`, `durasi`, `nama`, `kota`, `kategori`, `pembayaran`, `tanggal`, `waktu` di kolom DB & params method (`$data['tanggal']`, `$data['waktu']`).

Codebase mengaku "code in English, UI in Indonesian" (README/Project Analysis), tapi nyatanya kolom DB & FK pakai bahasa Indonesia. Ini *technical debt* — terlanjur ada di 30 migration. Tidak perlu diperbaiki sekarang, tapi:

### Tindakan
- Dokumentasikan secara eksplisit di `docs/architecture/CONVENTIONS.md`.
- Jangan tambah kolom baru pakai bahasa Indonesia.

---

## 🟡 ANOM-06 — `Order` punya `scopePending()` *dan* `OrderStatus::PENDING`, tapi kontroler kadang pakai keduanya berseberangan

Lihat BUG-A05. Selama duplikasi ada, pendatang baru akan bingung mana yang canonical.

---

## 🟡 ANOM-07 — Tabel inti pakai singular (`salon`, `order`, `service`, `staff`, `kategori`) — bukan Laravel default plural

Filament & beberapa relasi Eloquent harus override `$table`. Ini sah, tapi:
- Meningkatkan beban kognitif.
- `order` adalah **reserved word** di MySQL → setiap query manual harus backtick. Memang sudah di-handle Eloquent, tapi raw queries gampang error.

### Tindakan
Buat alias view (`CREATE VIEW orders AS SELECT * FROM \`order\``) untuk legibility query analytic. Atau rename — risiko tinggi, butuh data migration.

---

## 🟡 ANOM-08 — Filament admin panel masih default "Laravel Starter Kit"

**Bukti**: `eror_v1/Screenshot 2026-05-13 211813.png` — sidebar bertuliskan "Laravel Starter Kit", logo Laravel.

### Tindakan
Update `app/Providers/Filament/AdminPanelProvider.php`:
```php
->brandName('VIYGO Admin')
->brandLogo(asset('viygo-logo.svg'))
->favicon(asset('favicon.ico'))
->colors(['primary' => Color::Blue])
```

---

## 🟢 ANOM-09 — `phpunit.panel.xml` di root tanpa dokumentasi

Tidak ada penjelasan kapan dipakai vs `phpunit.xml`. Tambahkan komentar di README atau hapus.

---

## 🟢 ANOM-10 — `clean_md.php` & `test_panel_routes.php` di root project

Script developer ad-hoc. Lihat OPT-10. Pindahkan ke `scripts/` atau hapus.

---

## 🟢 ANOM-11 — Komentar PHPDoc berbahasa Indonesia di banyak file

Contoh: `// Susun order_detail berurutan: service-2 mulai setelah service-1 selesai, dst.` (BookingController:150).

Konvensi proyek menyatakan "code di English, UI di Indonesian". Komentar code adalah bagian dari code. Translate ke English ketika menyentuh file.

---

## 🟢 ANOM-12 — `routes/web.php` tidak grup `auth` middleware untuk `payment.*`

**File**: `routes/web.php:70-72`

`/booking/{kode}/payment` dilindungi auth ✓. Tapi `PaymentController::show` juga menerima kode order yang sudah `confirmed/success` → return 404 via `resolvePendingOrder`. OK, tapi penamaan route `booking.payment.*` bisa dipindah ke prefix `payment.*` agar lebih jelas semantically.

---

## 🟢 ANOM-13 — `pembayaran` table: kolom `metode_pembayaran` string varchar, bukan enum

Migration: `string('metode_pembayaran')`. Nilainya selalu fix dari Midtrans (`gopay`, `bank_transfer`, `credit_card`, `qris`, dll). Tidak ada validasi.

### Tindakan
Buat constant kelas `PaymentMethod` atau enum kolom. Minimal kasih `->index('metode_pembayaran')` untuk filter laporan.

---

## 🟢 ANOM-14 — `Schedule` di `routes/console.php` di-define tanpa timezone eksplisit

```php
Schedule::command('bookings:complete')->dailyAt('01:00');
```
Default = `config('app.timezone')` (UTC). Bila bisnis di UK, set `->timezone('Europe/London')` agar konsisten dengan jam buka salon.
