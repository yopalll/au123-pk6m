# 🎯 Prompt Perbaikan #1 — Audit Data & Implementasi Navbar Kategori Dropdown ala Treatwell

---

## Konteks Proyek

Kamu sedang mengerjakan proyek **VIYGO** — sebuah clone dari website **Treatwell.co.uk**, dibangun menggunakan **Laravel** (Blade + Alpine.js). Seluruh data salon, service, dan kategori di-scrape dari Treatwell dan tersimpan dalam database SQLite.

### Struktur Data yang Tersedia

| Tabel | Primary Key | Jumlah Record | Keterangan |
|-------|-------------|---------------|------------|
| `salon` | `id_salon` | ~8,750 | Data salon lengkap (nama, alamat, rating, koordinat, dll.) |
| `service` | `id_service` | ~100,000+ | Service per salon, terhubung ke `id_salon` dan `id_kategori` |
| `kategori` | `id_kategori` | ~11,000+ | Kategori sangat granular (per-salon), memiliki field `name`, `slug`, `deskripsi` |
| `kota` | `id_kota` | ~1,517 | Kota-kota di UK |
| `staff` | `id_staff` | ~10,906 | Staff per salon |
| `salon_images` | `id_salon_image` | ~73,000+ | Gambar-gambar salon |

### File-File Penting

- **Layout utama:** `resources/views/layouts/public.blade.php`
- **Navbar component:** `resources/views/components/viygo-navbar.blade.php`
- **Kategori controller:** `app/Http/Controllers/KategoriController.php`
- **Kategori page:** `resources/views/kategori/show.blade.php`
- **Model Kategori:** `app/Models/Kategori.php` (field: `name`, `deskripsi`, `slug`, `icon_url`, `is_active`)
- **Model Service:** `app/Models/Service.php` (relasi ke `Salon` via `id_salon`, ke `Kategori` via `id_kategori`)
- **Model Salon:** `app/Models/Salon.php` (relasi `services()`, `kota()`, `primaryImage()`, dll.)
- **Routes:** `routes/web.php` → `Route::get('/kategori/{slug}', [KategoriController::class, 'show'])`
- **Data JSON mentah:** `database/data/kategori.json`, `database/data/service.json`, `database/data/salon.json`

### Kondisi Navbar Saat Ini

Navbar sekarang hanya menampilkan link sederhana yang langsung mengarah ke halaman pencarian (`/cari?q=hair`, dll.). **Belum ada dropdown** yang menampilkan sub-kategori ketika di-hover. Kategori yang saat ini tampil: Hair, Face, Massage, Nails, Brows & Lashes, Body, Men's.

---

## 📋 TUGAS 1: Audit & Verifikasi Data terhadap Treatwell.co.uk

### Tujuan

Periksa dan bandingkan data yang sudah ada di database/website VIYGO dengan data asli di **Treatwell.co.uk**. Fokus pada:

1. **Verifikasi kategori utama** — Pastikan 7 kategori utama navbar (Hair, Hair Removal, Massage, Nails, Face, Body, Men's) sudah memiliki data sub-kategori yang sesuai di database.

2. **Mapping sub-kategori** — Untuk setiap sub-kategori yang didefinisikan di bawah ini, cari padanan yang tepat di tabel `kategori` berdasarkan field `name` atau `slug`. Jika tidak ditemukan, catat sebagai "missing" dan buat rekomendasi apakah perlu ditambahkan.

3. **Verifikasi ketersediaan salon** — Untuk setiap sub-kategori, pastikan ada salon yang memiliki service terkait (melalui relasi `service.id_kategori`). Laporkan jumlah salon yang tersedia per sub-kategori.

4. **Buat laporan lengkap** bernama `LAPORAN_AUDIT_KATEGORI.md` di folder `database/data/`, dengan format tabel yang rapi, berisi:
   - Nama sub-kategori Treatwell
   - Slug/nama padanan di VIYGO
   - Jumlah salon yang memiliki service di kategori tersebut
   - Status: ✅ (ada & cukup data), ⚠️ (ada tapi minim), ❌ (tidak ditemukan)

---

## 📋 TUGAS 2: Implementasi Navbar Dropdown Kategori ala Treatwell

### Referensi Visual (Treatwell.co.uk)

Pada website Treatwell asli, navbar memiliki perilaku berikut:
- **Baris atas**: Logo | Search bar | My Account
- **Baris bawah**: HAIR | HAIR REMOVAL | MASSAGE | NAILS | FACE | BODY | MEN'S | GIFT CARD | LOOKBOOK | THE TREATMENT FILES
- Ketika **salah satu kategori di-hover**, muncul **dropdown panel** yang berisi:
  - **Kolom kiri**: Daftar sub-kategori sebagai link yang bisa diklik, plus link "See all [category] treatments" di bagian bawah
  - **Kolom kanan** (opsional): 1-2 gambar promosi terkait kategori tersebut

### Kategori & Sub-Kategori yang Harus Ditampilkan

Implementasikan dropdown dengan struktur berikut. Setiap sub-kategori harus menjadi link menuju halaman `/kategori/{slug}` yang menampilkan salon-salon yang menyediakan treatment tersebut.

#### 🔹 HAIR
| Sub-Kategori | Slug yang Diharapkan |
|---|---|
| Ladies' Haircuts | `ladies-haircuts` |
| Blow Dry | `blow-dry` |
| Ladies' Hair Colouring & Highlights | `ladies-hair-colouring-highlights` |
| Ladies' Brazilian Blow Dry | `ladies-brazilian-blow-dry` |
| Balayage & Ombre | `balayage-ombre` |
| Men's Haircut | `mens-haircut` |
| **See all hair treatments** | *Link ke `/cari?q=hair`* |

#### 🔹 HAIR REMOVAL
| Sub-Kategori | Slug yang Diharapkan |
|---|---|
| Facial Threading | `facial-threading` |
| Ladies' Waxing | `ladies-waxing` |
| Sugaring | `sugaring` |
| Hollywood Waxing | `hollywood-waxing` |
| Men's Waxing | `mens-waxing` |
| Ladies' Leg Waxing | `ladies-leg-waxing` |
| **See all hair removal treatments** | *Link ke `/cari?q=hair+removal`* |

#### 🔹 MASSAGE
| Sub-Kategori | Slug yang Diharapkan |
|---|---|
| Deep Tissue Massage | `deep-tissue-massage` |
| Swedish Massage | `swedish-massage` |
| Therapeutic Massage | `therapeutic-massage` |
| Thai Massage | `thai-massage` |
| Aromatherapy Massage | `aromatherapy-massage` |
| Hot Stone Massage | `hot-stone-massage` |
| **See all massage treatments** | *Link ke `/cari?q=massage`* |

#### 🔹 NAILS
| Sub-Kategori | Slug yang Diharapkan |
|---|---|
| Pedicure | `pedicure` |
| Manicure | `manicure` |
| Nail or Gel Polish Removal | `nail-or-gel-polish-removal` |
| Gel Nails Manicure | `gel-nails-manicure` |
| Gel Nails Pedicure | `gel-nails-pedicure` |
| Acrylic, Hard Gel & Nail Extensions | `acrylic-hard-gel-nail-extensions` |
| **See all nail treatments** | *Link ke `/cari?q=nail`* |

#### 🔹 FACE
| Sub-Kategori | Slug yang Diharapkan |
|---|---|
| Classic Facials | `classic-facials` |
| Eyelash Extensions | `eyelash-extensions` |
| Eyebrow and Eyelash Tinting | `eyebrow-and-eyelash-tinting` |
| Eyebrow Threading | `eyebrow-threading` |
| Eyebrow Waxing | `eyebrow-waxing` |
| Definition Brows | `definition-brows` |
| **See all face treatments** | *Link ke `/cari?q=facial`* |

#### 🔹 BODY
| Sub-Kategori | Slug yang Diharapkan |
|---|---|
| Spray Tanning and Sunless Tanning | `spray-tanning-and-sunless-tanning` |
| Body Exfoliation Treatments | `body-exfoliation-treatments` |
| Body Wraps | `body-wraps` |
| Colonic Hydrotherapy | `colonic-hydrotherapy` |
| Cryolipolysis | `cryolipolysis` |
| Cellulite Treatments | `cellulite-treatments` |
| **See all body treatments** | *Link ke `/cari?q=body`* |

#### 🔹 MEN'S
| Sub-Kategori | Slug yang Diharapkan |
|---|---|
| Men's Haircut | `mens-haircut` |
| Beard Trims and Shaves | `beard-trims-and-shaves` |
| Men's Hair Colouring | `mens-hair-colouring` |
| Men's Brazilian Blow Dry | `mens-brazilian-blow-dry` |
| Men's Facials | `mens-facials` |
| Men's Waxing | `mens-waxing` |
| Barbers | `barbers` |
| *(Tidak ada "See all" — Men's sudah spesifik)* | |

---

### Instruksi Implementasi Teknis

#### A. Perubahan pada Navbar (`resources/views/components/viygo-navbar.blade.php`)

1. **Ganti array `$topCategories`** yang sekarang (link sederhana ke `/cari`) dengan struktur data baru yang mencakup sub-kategori.

2. **Implementasi dropdown** menggunakan **Alpine.js** (`x-data`, `@mouseenter`, `@mouseleave`) agar dropdown muncul saat hover dan hilang saat cursor keluar.

3. **Desain dropdown** harus mengikuti gaya Treatwell:
   - Panel putih dengan shadow lembut, muncul tepat di bawah navbar
   - Sub-kategori ditampilkan sebagai daftar link vertikal di sisi kiri
   - Setiap link mengarah ke `/kategori/{slug}` (menggunakan `route('kategori.show', $slug)`)
   - Link "See all [X] treatments" ditampilkan di bagian bawah dengan style **bold/tebal** dan berwarna berbeda (navy/biru tua)
   - Opsional: tambahkan 1-2 gambar placeholder di sisi kanan panel dropdown

4. **Responsif**: Pada mobile, dropdown bisa diubah menjadi accordion atau slide panel.

5. **Animasi**: Tambahkan transisi halus (`transition`, `x-transition`) saat dropdown muncul/hilang.

#### B. Perubahan pada Controller & Routes

1. **KategoriController** (`app/Http/Controllers/KategoriController.php`) sudah bisa menangani tampilan per kategori via slug. Pastikan slug-slug sub-kategori di atas **sudah ada di tabel `kategori`** di database.

2. Jika ada sub-kategori yang slug-nya belum tersedia di database, buatlah **seeder atau migration** untuk menambahkannya, atau lakukan **mapping fuzzy** ke kategori yang sudah ada (misal: "Ladies' Haircuts" bisa di-map ke kategori bernama "Haircuts - Ladies" jika ada di database).

3. Jika perlu membuat **parent category grouping** (karena saat ini tabel `kategori` tidak punya hierarki parent-child), ada dua pendekatan:
   - **Opsi 1 (Recommended):** Hardcode mapping di navbar component (karena hanya 7 grup utama)
   - **Opsi 2:** Tambahkan kolom `parent_group` atau `category_group` di tabel `kategori` via migration

#### C. Styling

1. Gunakan variabel CSS yang sudah ada:
   - `--viygo-navy: #1B2D6B` (warna utama)
   - `--viygo-blue: #4BA3CC` (aksen)
   - `--viygo-blue-lt: #E8F4FB` (hover background)
2. Font sudah menggunakan **DM Sans** (body) dan **DM Serif Display** (heading)
3. Pastikan dropdown tidak menutup konten navbar lain dan memiliki `z-index` yang tepat
4. Tambahkan underline animasi pada kategori yang sedang di-hover (sudah ada `.cat-nav-link::after` di layout)

---

## ✅ Kriteria Selesai (Definition of Done)

- [ ] File `LAPORAN_AUDIT_KATEGORI.md` telah dibuat dengan mapping lengkap antara sub-kategori Treatwell ↔ data VIYGO
- [ ] Navbar menampilkan 7 kategori utama: **HAIR**, **HAIR REMOVAL**, **MASSAGE**, **NAILS**, **FACE**, **BODY**, **MEN'S**
- [ ] Setiap kategori memiliki dropdown yang muncul saat hover, berisi sub-kategori sebagai link
- [ ] Setiap sub-kategori link mengarah ke `/kategori/{slug}` dan menampilkan salon-salon yang relevan
- [ ] Link "See all [X] treatments" mengarah ke halaman pencarian yang sesuai
- [ ] Dropdown memiliki animasi transisi yang halus
- [ ] Tidak ada error di console browser
- [ ] Navbar tetap responsif di mobile
- [ ] Semua slug sub-kategori sudah tersedia di database (atau di-map ke yang sudah ada)

---

## ⚠️ Catatan Penting

1. **Jangan hapus** link Gift Card, Lookbook, Treatment Files, dan For Salons dari navbar — mereka harus tetap ada setelah divider.
2. **Kategori di database sangat granular** (~11,000 kategori) karena setiap salon bisa punya nama kategori sendiri. Sub-kategori di atas adalah **kategori umum tingkat atas** yang perlu di-map dari kategori granular tersebut.
3. Gunakan **pencarian fuzzy/LIKE** pada tabel `kategori` jika slug exact tidak ditemukan. Misalnya, untuk "Ladies' Haircuts", cari kategori yang `name LIKE '%Haircut%Ladies%'` atau `slug LIKE '%ladies%haircut%'`.
4. Data JSON mentah tersedia di `database/data/` jika perlu melakukan analisis offline.
5. Website ini menggunakan **Alpine.js** (sudah terinstall via Vite) — gunakan itu untuk interaktivitas dropdown, bukan jQuery.
