# 🏗️ VIYGO V2 — Dokumentasi Teknis (Architecture & Backend)

> **Versi dokumen:** 1.0
> **Status:** ✅ Set inti selesai — doc 00–08 lengkap (lihat [Session Log](#-session-log--laporan-per-sesi)). Living document: update saat keputusan terbuka (O1–O8 di doc 08 §5) difinalisasi.
> **Audiens:** AI agent / developer yang akan **mengimplementasikan VIYGO V2**.
> **Sumber kebenaran fungsional:** [`../PRD_VIYGO_V2.md`](../PRD_VIYGO_V2.md)
> **Sumber kebenaran teknis:** kode V1 di repo ini (`app/`, `database/`, `routes/`, `config/`).

---

## 0. Cara Memakai Dokumen Ini

Dokumen teknis ini **melengkapi PRD**, bukan menggantikannya.

- **PRD** menjawab *"apa & kenapa"* (fitur, user story, alur bisnis).
- **Dokumen teknis ini** menjawab *"bagaimana membangunnya di atas codebase V1 yang sudah ada"* (arsitektur, skema DB, model, service, integrasi, panel admin, security).

> [!IMPORTANT]
> Jika ada **konflik antara PRD dan dokumen teknis ini**, dokumen teknis **menang** untuk hal teknis — karena PRD ditulis dengan beberapa asumsi yang **tidak cocok dengan kode V1 sebenarnya** (lihat [§3 Koreksi Penting Terhadap PRD](#3-koreksi-penting-terhadap-prd) di bawah). Semua koreksi sudah diverifikasi langsung dari source code.

---

## 1. Urutan Baca (Reading Order)

| # | Dokumen | Isi | Status |
|---|---------|-----|--------|
| 00 | **`00-INDEX.md`** (file ini) | Peta dokumen, konvensi global, koreksi PRD, session log | ✅ |
| 01 | [`01-tech-stack-dan-baseline-v1.md`](01-tech-stack-dan-baseline-v1.md) | Tech stack persis + arsitektur V1 yang sudah jalan (auth, role, Filament, Midtrans, konvensi DB) | ✅ |
| 02 | [`02-arsitektur-overview-v2.md`](02-arsitektur-overview-v2.md) | Arsitektur V2 high-level: peta modul, layering, request lifecycle, titik integrasi antar-modul | ✅ |
| 03 | [`03-database-schema-migrations.md`](03-database-schema-migrations.md) | Spesifikasi 29 migration (1 ALTER + 28 CREATE), urutan, konvensi PK/FK, index | ✅ |
| 04 | [`04-eloquent-models.md`](04-eloquent-models.md) | 28 model V2 + update model `User`, Constants, relasi, cast, scope, observer | ✅ |
| 05 | [`05-backend-services-controllers.md`](05-backend-services-controllers.md) | Controller, service layer, state machine status, business logic | ✅ |
| 06 | [`06-integrasi.md`](06-integrasi.md) | Midtrans (flow produk), api.co.id ongkir, DomPDF, Go scraper + seeder | ✅ |
| 07 | [`07-admin-store-filament.md`](07-admin-store-filament.md) | StorePanelProvider, Filament Resources, widget, authorization | ✅ |
| 08 | [`08-security-nfr-testing.md`](08-security-nfr-testing.md) | Auth/authz, rate limit, cache, queue, strategi testing, checklist kesiapan | ✅ |

> Centang status di sini setiap kali sebuah dokumen selesai, supaya agent berikutnya tahu apa yang sudah/belum ada.

---

## 2. Konvensi Global (WAJIB diikuti semua modul V2)

Diturunkan dari kode V1. Detail lengkap di [`01-tech-stack-dan-baseline-v1.md`](01-tech-stack-dan-baseline-v1.md).

1. **Custom Primary Key.** Semua tabel domain V1 memakai PK custom (`id_user`, `id_salon`, …) dibuat via `$table->id('id_nama')`. Tabel V2 **mengikuti konvensi yang sama** (`id_product`, `id_cart`, …). Tabel pivot kecil boleh pakai `id` default.
2. **Foreign Key ke tabel V1 WAJIB menyebut nama kolom PK:**
   ```php
   $table->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
   ```
   Tanpa argumen kedua, Laravel mencari kolom `id` yang **tidak ada** → migration error.
3. **Role enum** dikelola lewat `App\Constants\UserRole` (single source of truth). V2 menambah `admin_store`.
4. **Akses panel Filament** dikontrol di `User::canAccessPanel()` (key by panel id). Panel baru `store` harus didaftarkan di sana.
5. **Uang.** ⚠️ Harga salon V1 disimpan dalam **GBP** lalu dikonversi ke IDR saat bayar (`exchange_rate` default 20000). **Produk V2 disimpan langsung dalam IDR** — jangan dikonversi lagi. Lihat [§3](#3-koreksi-penting-terhadap-prd).
6. **Kode pesanan** produk pakai prefix `VYG-S-` (S = Shop) agar tidak bentrok dengan booking salon (`VYG-`).
7. **Money column** V1 pakai `decimal(12,2)`. Pakai `decimal(12,2)` juga di V2 (PRD menulis `decimal(10,2)` — pakai 12,2 untuk konsisten & aman dari overflow rupiah).
8. **Webhook Midtrans** dikecualikan dari CSRF di `bootstrap/app.php`. Webhook produk baru juga harus ditambahkan ke daftar pengecualian.
9. **Middleware alias** yang tersedia: `role:` (`CheckRole`), `active` (`EnsureUserIsActive`). Route customer V2 pakai `['auth','verified','role:customer']`.

---

## 3. Koreksi Penting Terhadap PRD

> [!CAUTION]
> Hal-hal di bawah ini **berbeda dari yang ditulis PRD**. Sudah diverifikasi dari source code V1. Ikuti versi yang benar di sini.

| # | PRD menulis | Faktanya di kode V1 | Implikasi untuk V2 |
|---|-------------|---------------------|--------------------|
| C1 | "Filament **v3**" (§10.1) | `filament/filament: 5.6` (**Filament v5**) | Semua syntax Resource/Form/Table pakai **Filament v5** (schema components, `Schema $schema`, dst.), bukan v3. |
| C2 | — (tidak disebut) | `livewire/livewire: ^4.1` (**Livewire v4**) + Flux 2.13 | Komponen interaktif pakai Livewire v4 API. |
| C3 | Money `decimal(10,2)` | V1 pakai `decimal(12,2)` | Pakai `decimal(12,2)` untuk semua kolom uang V2. |
| C4 | Implisit harga IDR | Harga salon V1 dalam **GBP**, dikonversi via `convertGbpToIdr()` | Produk V2 dalam IDR murni. `ProductPaymentController` **tidak** memanggil konversi GBP→IDR (gross_amount = nilai rupiah apa adanya). |
| C5 | "reuse PaymentController V1" | `PaymentController` sangat terkopel ke `Order`/`Pembayaran` salon | Buat `ProductPaymentController` **terpisah** yang meniru pola (Snap token → finish verify → webhook + signature SHA512 + idempotency + `lockForUpdate`), bukan extend langsung. |
| C6 | `barryvdh/laravel-dompdf` "reuse" | **Belum terinstall** | Wajib `composer require barryvdh/laravel-dompdf` dulu (Phase 1). |
| C7 | Tabel `users` punya `name` di beberapa contoh | Kolom sebenarnya `first_name` + `last_name` (nullable) | Seeder & form pakai `first_name`/`last_name`. Ada accessor `full_name` & alias `name`. |
| C8 | `users.role` enum 4 nilai | Enum V1 hanya `['customer','salon_owner','admin']` | Migration ALTER enum **wajib** dijalankan pertama untuk menambah `admin_store`. |
| C9 | — | `User::$guarded = ['role','is_active','id_user']` (anti mass-assignment) | Set `role` di seeder via property assignment (`$u->role='admin_store'; $u->save()`), bukan mass-assign. |

---

## 4. Status Implementasi vs Dokumentasi

> [!NOTE]
> Dokumen ini adalah **spesifikasi untuk dibangun**, bukan deskripsi kode yang sudah ada. Per tanggal penulisan, **belum ada kode V2** di repo — folder produk/forum/lookbook/dst. belum dibuat. Semua yang dibahas di doc 03–08 adalah *yang harus ditambahkan*.

Verifikasi cepat keadaan repo saat lanjut sesi (jalankan untuk konfirmasi apakah sudah ada progress):
```bash
php artisan migrate:status          # cek migration V2 sudah jalan / belum
ls app/Models/Product.php           # ada = model produk sudah dibuat
ls app/Providers/Filament/StorePanelProvider.php
```

---

## 5. Glosarium Singkat

| Istilah | Arti |
|---------|------|
| **V1** | Kode salon-booking marketplace yang sudah ada & jalan di repo ini. |
| **V2** | Penambahan: E-commerce skincare, Lookbook, Empty Return, Community, Rincian Booking. |
| **Admin Store** | Role + panel Filament baru (`/admin/store`) khusus operasional e-commerce. |
| **Empty Return** | Program kembalikan botol kosong → poin → tier → konten eksklusif. |
| **Ongkir** | Ongkos kirim real-time via api.co.id. |

---

## 📋 Session Log — Laporan Per Sesi

> **Mekanisme keberlanjutan:** Setiap sesi kerja diakhiri dengan entri di sini. Jika token habis / agent berganti, **baca bagian ini lebih dulu** untuk tahu apa yang sudah dikerjakan, keputusan yang diambil, dan langkah berikutnya. Laporan detail tiap sesi ada di folder [`reports/`](reports/).

### Sesi 1 — Discovery + Fondasi Dokumen
- **Tanggal:** 2026-05-28
- **Tujuan:** Pahami PRD + V1, buat kerangka dokumentasi, tulis baseline.
- **Selesai:**
  - Baca penuh PRD (`PRD_VIYGO_V2.md`, 2545 baris).
  - Audit kode V1: composer.json, routes, User model, UserRole, CheckRole, bootstrap/app.php, PaymentController, AdminPanelProvider, migrasi kunci (users/order/pembayaran).
  - Buat `00-INDEX.md` (hub + konvensi global + 9 koreksi PRD).
  - Buat `01-tech-stack-dan-baseline-v1.md`.
  - Buat `02-arsitektur-overview-v2.md`.
- **Temuan kritis:** 9 koreksi terhadap PRD (lihat §3) — terutama Filament v5 (bukan v3), harga V1 dalam GBP, DomPDF belum terinstall.
- **Berikutnya:** Sesi 2 → `03-database-schema-migrations.md`.
- **Laporan lengkap:** [`reports/sesi-01.md`](reports/sesi-01.md)

### Sesi 2 — Database Schema & Migrations
- **Tanggal:** 2026-05-28
- **Tujuan:** Tulis spesifikasi lengkap 29 migration V2.
- **Selesai:** `03-database-schema-migrations.md` — blueprint Laravel untuk 1 ALTER + 28 CREATE, urutan kanonik (induk→anak), konvensi PK/FK, index & unique, catatan tipe SET/JSON/polymorphic, peringatan SQLite test.
- **Keputusan:** (a) `product_reviews` dipindah ke Batch D (setelah `product_orders`) untuk hilangkan deferred-FK; (b) money pakai `decimal(12,2)`; (c) tambah kolom non-PRD yang berguna: `product_pembayaran.raw_response`, `product_reviews.is_visible`, `products` unique `fresh_product_id` + FULLTEXT; (d) `product_order_items` snapshot harga/berat/nama.
- **Berikutnya:** Sesi 3 → `04-eloquent-models.md`.
- **Laporan lengkap:** [`reports/sesi-02.md`](reports/sesi-02.md)

### Sesi 3 — Eloquent Models & Constants
- **Tanggal:** 2026-05-28
- **Selesai:** `04-eloquent-models.md` — 8 Constants class baru (+update UserRole), update model `User` (relasi V2 + cabang `store` di canAccessPanel + helper tier), 28 model V2 (spesifikasi ringkas + kode penuh untuk Product/ProductOrder/morph), 5 Observer, morphMap.
- **Keputusan:** (a) cast SET `skin_type` JANGAN pakai `'array'` bawaan → pakai kolom `string`+custom CommaSeparated cast (portable SQLite); (b) `Relation::enforceMorphMap` untuk forum_likes & point_transactions; (c) `UPDATED_AT=null` untuk tabel created_at-only.
- **Berikutnya:** Sesi 4 → `05-backend-services-controllers.md`.
- **Laporan lengkap:** [`reports/sesi-03.md`](reports/sesi-03.md)

### Sesi 4 — Backend: Controllers, Services & Business Logic
- **Tanggal:** 2026-05-28
- **Selesai:** `05-backend-services-controllers.md` — mapping route→controller→service untuk semua modul; 12 service (OrderCodeGenerator, CartService, OngkirService, PromoService, PointService, **CheckoutService** lengkap dgn transaksi DB+lock, ProductPaymentService, InvoiceService, Forum/Badge); FormRequest; state machine (ProductOrderState); aturan bisnis (free-ongkir+tier, poin 1:1000, stok/oversell, review guard, scheduler).
- **Keputusan:** (a) kurangi stok saat order DIBUAT (cegah oversell) → butuh scheduler `CancelStaleProductOrders` utk refund stok+poin; (b) berat & subtotal dihitung server dari cart (anti-manipulasi); (c) ProductPaymentService terpisah, gross_amount IDR tanpa convert GBP.
- **Reconcile:** doc 03 diselaraskan dgn doc 04 → `products.skin_type` jadi `string` (bukan SET) untuk portabilitas test.
- **Berikutnya:** Sesi 5 → `06-integrasi.md`.
- **Laporan lengkap:** [`reports/sesi-04.md`](reports/sesi-04.md)

### Sesi 5 — Integrasi Eksternal
- **Tanggal:** 2026-05-28
- **Selesai:** `06-integrasi.md` — Midtrans produk (perbedaan vs V1, webhook+CSRF, item_details=grand_total, refund), api.co.id ongkir (`config/ongkir.php`, env, `OngkirService` impl + cache+timeout+anti-manipulasi), DomPDF (install+InvoiceService+template stream), Go scraper + 3 seeder (`FreshProductSeeder` idempotent, `AdminStoreSeeder` property-assignment role, `ForumCategorySeeder`), ringkasan env vars baru.
- **Keputusan:** (a) webhook produk—default endpoint terpisah `shop/midtrans/webhook` + CSRF except, tapi catat opsi single-endpoint dispatch-by-prefix untuk 1 akun Midtrans; (b) ongkir weight dihitung server, tarif divalidasi ulang saat checkout; (c) scraper USD→IDR (rate beda dari exchange_rate GBP Midtrans V1).
- **Berikutnya:** Sesi 6 → `07-admin-store-filament.md`.
- **Laporan lengkap:** [`reports/sesi-05.md`](reports/sesi-05.md)

### Sesi 6 — Admin Store (Filament v5 Panel)
- **Tanggal:** 2026-05-28
- **Selesai:** `07-admin-store-filament.md` — `StorePanelProvider` lengkap (pola OwnerPanelProvider, id `store`, path `admin/store`), registrasi di bootstrap/providers.php, authorization + matriks CRUD, 9 Resource (skeleton Filament v5 nyata dari KategoriResource: Product+ImagesRelationManager, ProductOrder status/resi, ProductReview moderasi, Lookbook+slides+items, **EmptyReturn approve→PointService.credit+BadgeService**, ExclusiveContent, ForumModeration), 4 widget dashboard.
- **Verifikasi syntax:** dibaca `app/Filament/Resources/KategoriResource.php` → konfirmasi v5 (`Filament\Schemas\Schema`, actions di `Filament\Actions`). Doc mengarahkan implementer rujuk resource V1 sebagai sumber kebenaran syntax.
- **Catatan:** path `admin/store` sub-path dari `admin` (default panel) — verifikasi tidak konflik; fallback `/store`.
- **Berikutnya:** Sesi 7 (terakhir) → `08-security-nfr-testing.md`.
- **Laporan lengkap:** [`reports/sesi-06.md`](reports/sesi-06.md)

### Sesi 7 — Security, NFR & Testing (penutup)
- **Tanggal:** 2026-05-28
- **Selesai:** `08-security-nfr-testing.md` — security (authz/IDOR/rate-limit/upload/XSS/payment/CSRF), performa (cache/queue/index/N+1/target metrik), strategi testing Pest (unit+feature+mock Midtrans/api.co.id+keputusan DB test), checklist kesiapan backend, **ringkasan 8 item terbuka lintas-doc (O1–O8)**.
- **Status keseluruhan:** Set dokumentasi teknis Architecture & Backend (doc 00–08) **LENGKAP**.
- **Berikutnya:** implementasi mengikuti urutan Phase 1–4 (doc 02 §8 / PRD §18); finalisasi keputusan O1–O8 saat coding.
- **Laporan lengkap:** [`reports/sesi-07.md`](reports/sesi-07.md)

<!-- Tambahkan entri sesi berikutnya di ATAS baris ini supaya yang terbaru di atas. -->

---

*Dokumen hidup — update `00-INDEX.md` setiap kali menambah/menyelesaikan dokumen.*
