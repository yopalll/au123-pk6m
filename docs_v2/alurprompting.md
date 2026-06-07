# 🚀 Alur Prompting — VIYGO V2

> **Fungsi dokumen ini:** panduan langkah-demi-langkah berisi **prompt siap pakai**.
> Cara pakai: kerjakan dari atas ke bawah. Untuk tiap langkah, **copy blok prompt** (di dalam kotak)
> lalu paste ke AI agent (Claude Code). Tunggu selesai + verifikasi, baru lanjut ke langkah berikutnya.
>
> **JANGAN melompati urutan.** Tiap langkah punya prerequisite yang wajib selesai dulu.

---

## 📑 Peta Alur

```
MULAI DI SINI
     │
     ▼
[0] Persiapan & baca konteks
     │
     ▼
═══════════ PHASE 1 — FONDASI (wajib selesai semua) ═══════════
[1] Database (1A)  ──►  [2] Models (1B)  ──►  [3] Scraper+Seed+Panel (1C)
     │
     └──►  [4] Config + Navigasi (1D)   ← bisa paralel dgn [2][3]
     │
     ▼
═══════════ PHASE 2 — INTI ═══════════
[5] Rincian Booking + Invoice (2A)
[6] E-commerce Skincare (2B)   ← butuh [2][3][4]
     │
     ▼
═══════════ PHASE 3 — PENGEMBANGAN (boleh paralel) ═══════════
[7] Lookbook (3A)   [8] Empty Return (3B)   [9] Community (3C)
     │
     ▼
═══════════ PHASE 4 — POLISH ═══════════
[10] Cross-module + Dashboard + Testing + Audit + Docs (4)
```

---

## ⚙️ Prinsip Umum (SELALU berlaku)

Setiap prompt sudah memuat ini, tapi pahami konteksnya:

0. **🔴 WAJIB:** sebelum phase apa pun, baca **[CATATAN-LINGKUNGAN.md](CATATAN-LINGKUNGAN.md)** — fakta kode V1 (Filament **v5**, `User.role` guarded, penamaan Order/Salon/Pembayaran). Kalau plan bentrok dengan dokumen itu, ikuti dokumen itu.
1. **Proyek:** Laravel 12 + Livewire Flux + TailwindCSS v4 + **Filament v5.6**. **V1 sudah ada** (salon booking) — JANGAN rusak fitur V1.
2. **Konvensi DB:** PK custom (`id_product`, `id_cart`, dst.), FK ke V1 **wajib** `->constrained('users','id_user')`.
3. **Jalankan app:** cukup `php artisan serve` (tidak ada dev server kedua).
4. **Tiap langkah = 1 file plan** di `docs_v2/`. Agent harus **baca file itu dulu**, ikuti persis, lalu verifikasi.
5. **Berhenti & lapor** kalau ada error yang tidak bisa diselesaikan — jangan lanjut langkah berikutnya.

---

## [0] 🟢 MULAI DI SINI — Persiapan

**Prerequisite:** tidak ada.

> **Prompt — copy semua di bawah ini:**

```
Saya akan mengimplementasikan VIYGO V2 secara bertahap mengikuti plan di folder docs_v2/.

Tugas langkah ini (persiapan saja, JANGAN tulis kode fitur dulu):
1. Baca docs_v2/00-INDEX.md untuk memahami struktur keseluruhan.
2. Baca docs/PRD_VIYGO_V2.md bagian Section 12.0 (Kompatibilitas DB V1) dan Section 18 (Work Order) agar paham konvensi.
3. Periksa kondisi proyek saat ini:
   - Konfirmasi ini Laravel 12 (cek composer.json).
   - Lihat struktur app/Models/ — catat model V1 yang sudah ada (User, Salon, Order, dll.) dan nama PK-nya.
   - Lihat routes/web.php — catat route V1 yang sudah ada agar tidak bentrok.
   - Cek apakah Filament sudah terpasang & panel apa yang sudah ada.
4. Pastikan database terkoneksi: jalankan `php artisan migrate:status` dan laporkan hasilnya.

JANGAN buat file apa pun di langkah ini. Cukup laporkan temuan + konfirmasi proyek siap untuk Phase 1.
```

**✅ Sebelum lanjut:** agent sudah konfirmasi proyek Laravel 12, DB konek, dan paham model V1.

---

## [1] 🗄️ Phase 1A — Database (28 tabel + 1 ALTER)

**Prerequisite:** [0] selesai.

> **Prompt:**

```
Kerjakan docs_v2/phase-1a-database.md secara LENGKAP dan PERSIS.

Konteks penting:
- Ini Laravel 12. V1 sudah ada (PK custom: users.id_user, salon.id_salon, promo.id_promo, dll.).
- WAJIB: PK custom untuk tabel baru (id_product, id_cart, dst.), FK ke V1 pakai ->constrained('users','id_user').
- JANGAN ubah tabel V1 kecuali 1 migration ALTER untuk users.role (Step 1.1).

Langkah:
1. Buat migration Step 1.1 (ALTER users.role tambah 'admin_store').
2. Buat 28 migration tabel baru SESUAI URUTAN BATCH A-G di file itu (tabel induk dulu, baru anak).
3. Gunakan nama kolom & tipe data PERSIS seperti di plan.
4. Jalankan `php artisan migrate`.
5. Jalankan `php artisan migrate:status` — pastikan 29 migration baru berstatus "Ran".

Kalau migrate error (mis. FK gagal), perbaiki urutan/definisi lalu ulangi. Laporkan hasil akhir migrate:status.
```

**✅ Verifikasi:** `php artisan migrate:status` → 29 migration baru "Ran" tanpa error.

---

## [2] 🧩 Phase 1B — Eloquent Models (28 model)

**Prerequisite:** [1] selesai (tabel sudah ada).

> **Prompt:**

```
Kerjakan docs_v2/phase-1b-models.md secara LENGKAP.

Langkah:
1. Buat 28 model baru di app/Models/ sesuai plan. Tiap model WAJIB punya:
   $table, $primaryKey (jika bukan 'id'), $fillable, dan semua relasi (belongsTo/hasMany/belongsToMany).
2. Perhatikan model dengan timestamp khusus (Wishlist, EmptyReturnPhoto, PointTransaction, ForumLike, ForumBookmark) — ikuti setting public $timestamps / const CREATED_AT seperti di plan.
3. Update model User V1 (app/Models/User.php) — tambahkan relasi baru (cartItems, wishlists, productOrders, addresses, skincareProfile, points, emptyReturns, forumThreads, communityPoint, badges). JANGAN hapus relasi V1 yang sudah ada.

Verifikasi via tinker:
   php artisan tinker
   >>> Product::count(); UserPoint::count(); ForumCategory::count(); Cart::first();
Semua harus jalan tanpa exception. Laporkan hasilnya.
```

**✅ Verifikasi:** `php artisan tinker` query tiap model tanpa error.

---

## [3] 🤖 Phase 1C — Scraper + Seeder + Filament Store Panel

**Prerequisite:** [2] selesai.

> **Prompt:**

```
Kerjakan docs_v2/phase-1c-scraper-seed.md secara LENGKAP. File ini punya 4 bagian: Scraper, 3 Seeder, dan Filament Store Panel (Step 1.5b).

Langkah:
1. SCRAPER (Step 1.4): buat scripts/scraper/go.mod, config.json, dan fresh_scraper.go sesuai spesifikasi.
   - CATATAN: kalau Go/Chrome tidak tersedia di environment ini, ATAU scraping fresh.com gagal,
     JANGAN berhenti. Buat saja file scrapernya, lalu siapkan 1 file JSON contoh berisi 3-5 produk dummy
     per kategori di scripts/scraper/output/ agar FreshProductSeeder tetap bisa dites. Beri tahu saya.
2. SEEDER (Step 1.5): buat FreshProductSeeder, AdminStoreSeeder, ForumCategorySeeder PERSIS seperti plan.
   - AdminStoreSeeder pakai first_name & last_name (BUKAN 'name') — users V1 begitu.
3. FILAMENT STORE PANEL (Step 1.5b): buat StorePanelProvider (path /admin/store), daftarkan di
   bootstrap/providers.php, implement FilamentUser + canAccessPanel() di model User (admin_store & admin boleh masuk).
4. Jalankan seeder:
   php artisan db:seed --class=AdminStoreSeeder
   php artisan db:seed --class=ForumCategorySeeder
   php artisan db:seed --class=FreshProductSeeder
5. Verifikasi: User::where('role','admin_store')->exists() = true, ForumCategory::count() = 5, Product::count() > 0.
   Lalu buka /admin/store/login — pastikan panel muncul (resource masih kosong, itu normal).

Laporkan: status scraper (jalan / pakai dummy), jumlah produk ter-seed, dan apakah panel /admin/store bisa diakses.
```

**✅ Verifikasi:** admin_store ada, 5 forum kategori, produk ter-seed (real/dummy), `/admin/store/login` tampil.

---

## [4] 🧭 Phase 1D — Config (DomPDF + Ongkir) + Navigasi

**Prerequisite:** [1] selesai (boleh paralel dengan [2] & [3]).

> **Prompt:**

```
Kerjakan docs_v2/phase-1d-config-nav.md secara LENGKAP.

Langkah:
1. composer require barryvdh/laravel-dompdf.
2. Buat config/ongkir.php sesuai plan. Tambah API_CO_ID_KEY & ONGKIR_ORIGIN_CITY ke .env.example DAN .env (kosongkan API key dulu kalau belum punya).
3. Update navigasi: tambah link Shop, Lookbook, Komunitas, Empty Return di navbar utama; tambah menu akun (Pesanan Produk, Wishlist, Poin, Bookmark); buat mobile bottom tab bar + include di layout.
   - PENTING: cari dulu file navbar/layout V1 yang BENAR (mis. resources/views/components/viygo-navbar.blade.php atau layouts/public.blade.php) sebelum edit. Sesuaikan dengan struktur class V1 yang ada.
4. Tambah placeholder routes V2 + view coming-soon.blade.php agar link tidak 404.

Verifikasi:
- php artisan tinker → config('ongkir.origin_city') = "Jakarta Selatan", class_exists DomPDF Pdf = true.
- Jalankan `php artisan serve`, buka home → navbar tampilkan menu baru, mobile view (DevTools 375px) → bottom tab bar muncul, semua link tidak 404.
Laporkan hasilnya.
```

**✅ Verifikasi:** DomPDF tersedia, config ongkir terbaca, navbar + bottom tab bar tampil, link tidak 404.

> **🎯 CHECKPOINT PHASE 1:** Pastikan [1][2][3][4] semua hijau sebelum masuk Phase 2.

---

## [5] 🧾 Phase 2A — Rincian Booking + Invoice PDF

**Prerequisite:** [4] selesai (DomPDF terpasang).

> **Prompt:**

```
Kerjakan docs_v2/phase-2a-booking-invoice.md secara LENGKAP.

Konteks: modul ini extends fitur booking V1 (tabel order, pembayaran, order_detail, salon, treatment yang SUDAH ADA). JANGAN buat tabel baru.

Langkah:
1. SEBELUM nulis controller: cek struktur model Order V1 yang asli (nama kolom: kode_order, subtotal, total, diskon, status; nama relasi ke salon/pembayaran/order_detail). Sesuaikan query di plan dengan nama kolom/relasi V1 yang SEBENARNYA — plan memakai nama asumsi, perbaiki bila beda.
2. Tambah 2 route (akun.booking.detail, akun.booking.invoice).
3. Tambah method bookingDetail() & downloadInvoice() di AkunController.
4. Buat view akun/booking-detail.blade.php (pakai referensi docs_v2/design/e2.1_order_booking_history/code.html).
5. Buat template PDF resources/views/pdf/invoice-booking.blade.php.
6. Tambah link "Lihat Rincian" di halaman list booking V1.

Verifikasi (jalankan php artisan serve, login sbg customer yg punya booking):
- /akun/bookings/{kode} tampil benar (status, salon, service, timeline).
- Klik Download Invoice → file PDF ter-download & isinya benar.
- Mobile 375px → tabel service jadi card.
Laporkan + sebut kalau ada nama kolom V1 yang harus disesuaikan.
```

**✅ Verifikasi:** halaman detail + PDF invoice berfungsi, responsive.

---

## [6] 🛍️ Phase 2B — E-commerce Skincare (modul terbesar)

**Prerequisite:** [2], [3], [4] selesai.

> **Prompt:**

```
Kerjakan docs_v2/phase-2b-ecommerce.md secara LENGKAP. Ini modul terbesar — kerjakan BERURUTAN sub-step 2.2.1 sampai 2.2.10, jangan loncat.

Langkah:
1. Sub 2.2.1: buat 9 controller di app/Http/Controllers/Shop/ + daftarkan SEMUA route (termasuk shop.regional.cities).
2. Sub 2.2.2: ShopController (index, kategori, koleksi, show + filter review & rating breakdown, search) + view katalog. Pakai design docs_v2/design/j2_product_detail_*/ & m_j1_skincare_shop_landing/.
3. Sub 2.2.3: Skincare Finder (quiz 3 step + simpan profil + rekomendasi).
4. Sub 2.2.4: Wishlist (toggle + index + pindah ke cart + share link sesuai plan).
5. Sub 2.2.5: Cart (add/update/remove + free ongkir progress bar). Design: j3_shopping_bag_2/.
6. Sub 2.2.6: OngkirController (check + cities autocomplete, dgn cache + timeout fallback).
7. Sub 2.2.7: Checkout (alamat + promo; bagian poin BIARKAN placeholder, diisi Phase 3B). kode_order format VYG-S-XXXXXX.
8. Sub 2.2.8: Payment Midtrans (reuse config midtrans V1).
9. Sub 2.2.9: Riwayat pesanan + detail + invoice PDF produk + Review (verified purchase).
10. Sub 2.2.10: pastikan semua view responsive (grid 2→3→4 kolom, filter jadi bottom sheet di mobile).
11. ADMIN STORE: buat Filament resource ProductResource, ProductCategoryResource, ProductCollectionResource, ProductOrderResource (read+update saja), ProductReviewResource di app/Filament/Store/Resources/ (panel /admin/store sudah dibuat di Phase 1C). Terapkan permission matrix (ProductOrder: tanpa create/delete).

Verifikasi end-to-end (php artisan serve):
- /shop tampil produk dari seed.
- Add to cart → checkout → pilih ongkir → (Midtrans sandbox) → pesanan tercatat status sesuai.
- Review hanya bisa setelah delivered/completed.
- /admin/store: login admin.store, CRUD produk jalan, akses sesuai permission.
Laporkan hasil tiap sub-step. Kalau Midtrans/API ongkir belum ada key, mock/skip bagian itu & beri tahu saya.
```

**✅ Verifikasi:** flow browse → cart → checkout → payment → pesanan jalan; admin store CRUD produk.

> **🎯 CHECKPOINT PHASE 2:** [5] & [6] hijau sebelum Phase 3.

---

## [7] 📸 Phase 3A — Lookbook

**Prerequisite:** [6] selesai (produk ada untuk di-tag).

> **Prompt:**

```
Kerjakan docs_v2/phase-3a-lookbook.md secara LENGKAP.

Konteks: route /lookbook & model Lookbook dasar mungkin sudah ada di V1 — OVERRIDE/extend, jangan duplikat.

Langkah:
1. Routes (lookbook.index, show, shopAll) + LookbookController (override V1).
2. View lookbook/index.blade.php (grid editorial, filter tema, hero) — design k1_lookbook_index_editorial_view/.
3. View lookbook/show.blade.php (slideshow + product tag pins interaktif + "Shop This Look" + share) — design k1.1_lookbook_detail_midnight_muse/.
4. LookbookResource di Filament Store (CRUD lookbook + slides + product tags via Repeater nested).

Verifikasi (php artisan serve):
- Admin store buat lookbook + 2 slide + tag produk.
- /lookbook tampil; klik → detail; product pin → popup; navigasi slide jalan.
- "Shop This Look" → semua produk masuk cart.
- Mobile 375px → grid 1 kolom.
Laporkan.
```

**✅ Verifikasi:** lookbook CRUD + tampil + product tags + Shop This Look berfungsi.

---

## [8] ♻️ Phase 3B — Empty Return + Poin + Konten Eksklusif

**Prerequisite:** [6] selesai (poin terhubung ke checkout).

> **Prompt:**

```
Kerjakan docs_v2/phase-3b-empty-return.md secara LENGKAP.

Langkah:
1. Routes (emptyReturn.*, akun.poin*, exclusive.*) + 3 controller (EmptyReturn, Point, ExclusiveContent).
2. app/Services/PointService.php (creditFromEmptyReturn + spendPoints + hitung tier starter→bronze→silver→gold).
3. View: empty-return/index (counter+impact), create (form+upload foto), history; akun/poin (saldo+tier+progress); exclusive/index+show (lock per tier). Design: l1_, l2_, l4_.
4. Filament: EmptyReturnResource (action Approve/Reject → panggil PointService), ExclusiveContentResource (CRUD).
5. INTEGRASI KE CHECKOUT (Sub 3.2.7): update ProductCheckoutController::store() — ganti placeholder poin jadi PointService::spendPoints() (1 poin = Rp 1.000).

Verifikasi (php artisan serve):
- Customer submit empty return → admin store approve set poin → saldo & tier user naik.
- /akun/poin tampilkan tier + progress.
- Tier cukup → konten eksklusif terbuka; tier kurang → terkunci.
- Checkout pakai poin → potongan muncul di grand_total.
Laporkan.
```

**✅ Verifikasi:** submit → approve → poin → tier → konten terbuka → poin terpakai di checkout.

---

## [9] 💬 Phase 3C — Community Forum

**Prerequisite:** [3] selesai (ForumCategorySeeder), [2] selesai (Models). Boleh paralel [7][8].

> **Prompt:**

```
Kerjakan docs_v2/phase-3c-community.md secara LENGKAP.

Langkah:
1. Routes komunitas.* + akun.bookmarks + 3 controller (Forum, ForumReply, ForumInteraction) di app/Http/Controllers/Forum/.
2. composer require ezyang/htmlpurifier + helper clean() di app/helpers.php (daftarkan di composer.json autoload files, lalu composer dump-autoload). Pakai clean() di semua input konten forum (anti-XSS).
3. View: komunitas/index (kategori+trending+stats), kategori, thread (detail+reply nested max 2 level), create (rich text + tag produk), leaderboard; akun/bookmarks.
   - Tidak ada design khusus forum — pakai design system di docs_v2/design/DESIGN-SYSTEM.md (Serene Floral Noir).
4. Gamification: community_points (+5 buat thread, +1 dapat reply, +2 dapat like), badge auto-assign (Skincare Guru, Top Reviewer, Eco Warrior, Rising Star), leaderboard.
5. Filament: ForumModerationResource (pin/hide/delete).

Verifikasi (php artisan serve):
- Buat thread → tampil; reply → tampil; like & bookmark jalan (cek /akun/bookmarks).
- community_points penulis bertambah; badge muncul saat threshold tercapai.
- Admin store bisa pin/hide thread.
- Mobile: kategori jadi horizontal chips.
Laporkan.
```

**✅ Verifikasi:** thread/reply/like/bookmark + poin komunitas + badge + moderasi jalan.

> **🎯 CHECKPOINT PHASE 3:** [7][8][9] hijau sebelum Phase 4.

---

## [10] ✨ Phase 4 — Polish, Testing, Audit, Docs

**Prerequisite:** [7], [8], [9] SEMUA selesai.

> **Prompt:**

```
Kerjakan docs_v2/phase-4-polish-testing.md secara LENGKAP, urut Step 4.1 sampai 4.6.

Langkah:
1. 4.1 Cross-module: pastikan poin↔checkout, tier↔free ongkir (Silver 1x/bln, Gold unlimited), tier↔konten, wishlist↔lookbook, forum↔produk, badge↔empty return/review semua tersambung.
2. 4.2 Dashboard Filament Store: StatsOverviewWidget, SalesTrendWidget (7 hari), LowStockWidget (stok<10).
3. 4.3 UI/UX: responsive final (375/768/1280), skeleton loading, toast, image lazy loading + srcset, font Playfair Display + Manrope.
4. 4.4 Testing: buat & jalankan feature test (Cart, Checkout, Ongkir termasuk timeout, Review, EmptyReturn approval, Forum, Invoice PDF, Authorization admin_store). Target `php artisan test` semua PASS.
5. 4.5 Audit: migration composite index, rate limiting (ongkir/submit/checkout), validasi upload (mimes+max 2MB), HTMLPurifier, cache (produk/lookbook/ongkir), queue.
6. 4.6 Docs: update README.md (setup V2: env, migrate, seeder, scraper, admin store) + .env.example.

Verifikasi:
- php artisan test → semua hijau.
- Test end-to-end manual: empty return → poin → checkout pakai poin → tier naik → konten terbuka → free ongkir.
- Dashboard admin store tampilkan data real.
Laporkan checklist Phase 4 mana yang hijau/merah.
```

**✅ Verifikasi:** `php artisan test` pass, cross-module flow end-to-end jalan, dashboard tampil data.

---

## 🏁 Selesai

Kalau [10] hijau → VIYGO V2 lengkap. Urutan ringkas yang harus diingat:

```
[0] Persiapan
[1] DB → [2] Models → [3] Scraper+Seed+Panel
[4] Config+Nav
[5] Booking Invoice → [6] E-commerce
[7] Lookbook  +  [8] Empty Return  +  [9] Community   (paralel)
[10] Polish + Testing
```

> **Tips saat menjalankan:**
> - Kerjakan **1 langkah per sesi** agar fokus & mudah verifikasi.
> - Selalu **verifikasi dulu** sebelum lanjut — jangan menumpuk error.
> - Kalau agent bilang ada nama kolom/relasi V1 yang beda dari plan, **percayai temuan agent** (plan memakai nama asumsi).
> - Backup/commit git setiap selesai 1 phase.
