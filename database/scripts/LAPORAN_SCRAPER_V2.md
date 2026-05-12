# Laporan Scraper VIYGO v2 — Per-Kategori (Treatwell.co.uk)

> Tanggal dibuat: **2026-05-10**
> Author: refactor schema kategori → kategori utama + sub_kategori
> Binary: `database/scripts/scraper.exe` (Go, satu file `scraper.go`)

---

## 1. Latar Belakang

Skema lama `kategori` menampung **2.000+ baris campur-aduk** (hasil scraping
v1 yang otomatis bikin kategori baru per nama service). Akibatnya:

- Navbar dropdown harus pakai **mapping manual hardcoded** ke slug DB
  (lihat history `viygo-navbar.blade.php`).
- Filter "salon yang nyediain Manicure" jadi **tidak akurat** karena 1 nama
  treatment bisa kebagi ke banyak baris kategori berbeda.
- Halaman `/kategori/{slug}` kadang kosong karena slug navbar tidak match
  dengan slug kategori hasil scraping.

Solusi v2:

1. **Reduksi `kategori` jadi 7 baris kategori utama** (Hair, Hair Removal,
   Massage, Nails, Face, Body, Men's) — satu-satu sesuai navbar.
2. Tambah tabel **`sub_kategori`** sebagai child dari kategori utama, isinya
   treatment-treatment spesifik (Ladies' Haircuts, Blow Dry, Pedicure, dst).
   Inilah yang dipakai sebagai **penghubung** salon ↔ service ↔ navbar.
3. Tambah **pivot `salon_sub_kategori`** (M:N) supaya bisa menjawab "salon
   mana saja yang menyediakan sub-kategori X" tanpa scan tabel `service`.
4. Tambah kolom **`service.id_sub_kategori`** (FK nullable) supaya tiap
   service bisa di-tag ke sub-kategori yang spesifik. Pivot di-derive
   otomatis oleh `SalonSubKategoriSeeder`.
5. **Scraper Go ditulis ulang** (`scraper.go`) supaya bisa dijalankan
   per-kategori dengan satu binary + flag `--kategori=hair`. Setiap service
   yang ditemukan langsung ditandai `id_kategori` (1-7) **dan**
   `id_sub_kategori` (via keyword matcher).

---

## 2. Skema Database Baru

### 2.1 Tabel `kategori` (7 baris saja)

| id | name         | slug          |
| -- | ------------ | ------------- |
| 1  | Hair         | hair          |
| 2  | Hair Removal | hair-removal  |
| 3  | Massage      | massage       |
| 4  | Nails        | nails         |
| 5  | Face         | face          |
| 6  | Body         | body          |
| 7  | Men's        | mens          |

Diisi oleh [`KategoriSeeder.php`](../seeders/KategoriSeeder.php).

### 2.2 Tabel `sub_kategori` (43 baris dari navbar Treatwell)

```
id_sub_kategori (PK)
id_kategori    (FK → kategori, cascade delete)
name           (mis. "Ladies' Haircuts")
slug           (mis. "ladies-haircuts", unique per kategori)
treatwell_slug (slug asli URL Treatwell, dipakai scraper)
deskripsi
icon_url
urutan         (urutan tampilan di dropdown)
is_active
```

Diisi oleh [`SubKategoriSeeder.php`](../seeders/SubKategoriSeeder.php).

### 2.3 Pivot `salon_sub_kategori`

```
id_salon         (FK → salon)
id_sub_kategori  (FK → sub_kategori)
unique(id_salon, id_sub_kategori)
```

Tidak diisi langsung — diturunkan otomatis oleh
[`SalonSubKategoriSeeder.php`](../seeders/SalonSubKategoriSeeder.php) dari
`distinct service.id_sub_kategori per id_salon`.

### 2.4 Perubahan `service`

Migration `2026_05_10_000003_add_id_sub_kategori_to_service.php`:

- `id_kategori` → **nullable** (sebelumnya NOT NULL)
- tambah `id_sub_kategori` nullable, FK → `sub_kategori`, `nullOnDelete()`
- index baru di `id_sub_kategori`

### 2.5 Diagram Relasi

```
┌────────────┐ 1   N ┌──────────────┐ 1   N ┌──────────┐
│  kategori  │──────▶│ sub_kategori │──────▶│ service  │
│ (7 baris)  │       │   (43 brs)   │       │  (2K+)   │
└────────────┘       └──────────────┘       └──────────┘
                            ▲                     │
                            │ M                   │ N
                            │                     ▼
                     ┌──────┴───────┐       ┌──────────┐
                     │salon_sub_    │◀──────│  salon   │
                     │ kategori     │   N M │          │
                     │  (pivot)     │       └──────────┘
                     └──────────────┘
```

Pertanyaan-pertanyaan yang sekarang murah dijawab:
- "Salon apa saja yang menyediakan **Pedicure** di London?"
  → `salon_sub_kategori JOIN sub_kategori WHERE slug='pedicure'`
- "Berapa salon utk kategori utama **Massage**?"
  → `salon_sub_kategori WHERE id_sub_kategori IN (sub_kategori WHERE id_kategori=3)`
- "Service termurah utk kategori **Hair** di salon X?"
  → `MIN(service.harga) WHERE id_salon=X AND id_kategori=1`

---

## 3. Cara Kerja Scraper v2

### 3.1 Arsitektur singkat

```
┌──────────────────┐   ┌──────────────┐    ┌────────────────────┐
│ kategoriRegistry │──▶│ buildListing │───▶│ scrapeDetailPage() │
│  (di scraper.go) │   │   URL per    │    │  per salon (par.)  │
└──────────────────┘   │   kategori   │    └────────┬───────────┘
                       └──────────────┘             │
                                                    ▼
                                          ┌──────────────────┐
                                          │ matchSubKategori │
                                          │  (keyword match) │
                                          └─────────┬────────┘
                                                    │
                                                    ▼
                                          ┌──────────────────┐
                                          │ merge ke JSON di │
                                          │ database/data/   │
                                          └──────────────────┘
```

### 3.2 `kategoriRegistry` — sumber kebenaran tunggal

Di dalam `scraper.go` ada array konstanta `kategoriRegistry` berisi 7
`KategoriConfig`. Tiap config menyimpan:

| Field           | Contoh                                     | Kegunaan                                          |
| --------------- | ------------------------------------------ | ------------------------------------------------- |
| `IDKategori`    | `1`                                        | Akan jadi `service.id_kategori`                   |
| `Slug`          | `"hair"`                                   | Cocok dgn `kategori.slug` di DB                   |
| `Name`          | `"Hair"`                                   | Display                                           |
| `TreatmentTag`  | `"treatment-group-hair"`                   | Dipakai membentuk URL listing Treatwell           |
| `SubKategori[]` | array `SubKategoriDef`                     | 6-7 sub_kategori per kategori utama               |

Tiap `SubKategoriDef`:

| Field           | Contoh                                | Kegunaan                                                                |
| --------------- | ------------------------------------- | ----------------------------------------------------------------------- |
| `ID`            | `1`                                   | **HARUS sama** dgn id auto-increment di `SubKategoriSeeder.php`         |
| `Slug`          | `"ladies-haircuts"`                   | Cocok dgn `sub_kategori.slug`                                           |
| `Name`          | `"Ladies' Haircuts"`                  | Display                                                                 |
| `TreatwellSlug` | `"ladies-haircuts-1"`                 | Dipakai membentuk filter URL (opsional, untuk kebutuhan lanjutan)       |
| `Keywords`      | `["ladies haircut", "ladies cut"...]` | **Keyword match** untuk klasifikasi service hasil scrape ke sub_kategori |

> ⚠️ **Konsistensi ID**: jika sub_kategori ditambah/dihapus di
> `SubKategoriSeeder.php`, `kategoriRegistry.SubKategori[*].ID` di
> `scraper.go` **wajib disesuaikan** (urutan auto-increment menentukan ID).

### 3.3 Pembentukan URL listing per kategori

```
https://www.treatwell.co.uk/places/{TreatmentTag}/offer-type-local/in-{kota}-uk/
```

Contoh untuk Hair di London:

```
https://www.treatwell.co.uk/places/treatment-group-hair/offer-type-local/in-london-uk/
```

Default kota yang di-scrape kalau `--kota` tidak diberikan: `london`,
`manchester`, `birmingham` (3 kota terbesar UK = paling banyak salon).

### 3.4 Tagging service ke sub_kategori (`matchSubKategori`)

Setiap service yang ditemukan di JSON-LD `hasOfferCatalog` akan dicocokkan:

1. **Pass 1 — keyword di nama service**. Iterasi sub_kategori → cek
   `strings.Contains(strings.ToLower(svc.Nama), keyword)`. Karena keyword
   diatur dari **paling spesifik ke paling umum** (`"deep tissue"` sebelum
   `"massage"`), match pertama menang.
2. **Pass 2 — keyword di `CategoryHint`** (nama OfferCatalog dari Treatwell).
   Berguna kalau nama service-nya generik (mis. "60 min relax") tapi catalog
   parent-nya bilang "Massage > Aromatherapy".
3. Kalau tidak match, `id_sub_kategori = NULL` — service tetap masuk DB
   tapi tidak akan muncul di filter sub-kategori spesifik (masih muncul di
   kategori utama via `id_kategori`).

### 3.5 Output JSON

Scraper merge (append + dedup by `source_url`) ke file existing:

| File                 | Field penting yg ditambah                                  |
| -------------------- | ---------------------------------------------------------- |
| `kota.json`          | (sama)                                                     |
| `salon.json`         | (sama)                                                     |
| `service.json`       | **`id_kategori` 1-7**, **`id_sub_kategori`**, `kategori_slug`, `sub_kategori_slug` |
| `staff.json`         | (sama)                                                     |
| `salon_images.json`  | (sama)                                                     |

`kategori.json` **tidak di-touch** lagi (di-seed dari PHP, bukan scraper).

---

## 4. Cara Pemakaian

### 4.1 Build (sekali, atau setiap habis edit `scraper.go`)

```powershell
cd database\scripts
.\build.bat
```

Atau manual:

```powershell
go build -o scraper.exe scraper.go
```

> Jangan pakai `go run` untuk scraping besar — `go build` sekali, executable
> dipakai berkali-kali.

### 4.2 Scrape satu kategori (default 3 kota, 10 halaman per kota)

```powershell
.\scraper.exe --kategori=hair
.\scraper.exe --kategori=nails
.\scraper.exe --kategori=massage
.\scraper.exe --kategori=face
.\scraper.exe --kategori=body
.\scraper.exe --kategori=hair-removal
.\scraper.exe --kategori=mens
```

### 4.3 Override kota / batas halaman

```powershell
# Hair, hanya kota Manchester, 5 halaman
.\scraper.exe --kategori=hair --kota=manchester --max-pages=5

# Nails, beberapa kota sekaligus (comma-separated)
.\scraper.exe --kategori=nails --kota=london,leeds,bristol --max-pages=3
```

### 4.4 Scrape semua 7 kategori sekaligus

```powershell
.\scraper.exe --kategori=all --max-pages=5
```

> Total durasi (3 kota × 5 halaman × 7 kategori, ~15 salon/halaman) =
> **kurang lebih 30-50 menit** tergantung rate limit.

### 4.5 Workflow penuh: scrape → migrate → seed

```powershell
# 1. Reset JSON kalau mau dari nol (opsional)
cd database\data
'[]' | Out-File salon.json -Encoding utf8
'[]' | Out-File service.json -Encoding utf8
'[]' | Out-File staff.json -Encoding utf8
'[]' | Out-File salon_images.json -Encoding utf8
'[]' | Out-File kota.json -Encoding utf8

# 2. Scrape per kategori (atau --kategori=all)
cd ..\scripts
.\scraper.exe --kategori=hair --max-pages=10
.\scraper.exe --kategori=nails --max-pages=10
# ... ulangi utk 5 kategori sisanya

# 3. Migrate (jalankan migration baru)
cd ..\..
php artisan migrate

# 4. Seed (truncate semua tabel + insert dari JSON)
php artisan db:seed
```

Yang akan terjadi waktu `db:seed`:

```
[TRUNCATE] semua tabel terkait dibersihkan
[KategoriSeeder]    → 7 baris (Hair, Hair Removal, ...)
[SubKategoriSeeder] → 43 baris (Ladies' Haircuts, Pedicure, ...)
[KotaSeeder]        → dari kota.json
[UserSeeder]        → 1 admin + 1 customer + N owner (dari salon)
[SalonSeeder]       → dari salon.json
[ServiceSeeder]     → dari service.json (mapping id_kategori 1-7 + id_sub_kategori)
[SalonSubKategoriSeeder] → derive pivot dari distinct service.id_sub_kategori per salon
[StaffSeeder]       → dari staff.json
[SalonImagesSeeder] → dari salon_images.json
```

---

## 5. Jaga Konsistensi (Penting!)

Karena scraper Go dan seeder PHP **dua dunia**, ID sub_kategori harus
disinkronkan manual. Aturan:

1. Tambah/edit/hapus sub_kategori → ubah **DULU** `SubKategoriSeeder.php`.
2. Catat baris baru dapat ID berapa (urutan = auto-increment, ID terakhir
   sebelumnya + 1).
3. Update `kategoriRegistry` di `scraper.go` agar `SubKategoriDef.ID` cocok.
4. Re-build scraper: `.\build.bat`.

> **Tip**: kalau bingung, jalankan dulu `php artisan migrate:fresh --seed`
> setelah edit seeder, lalu cek tabel `sub_kategori`:
> ```sql
> SELECT id_sub_kategori, slug FROM sub_kategori ORDER BY id_sub_kategori;
> ```
> dan salin angkanya ke `scraper.go`.

---

## 6. Perbedaan dgn Scraper v1 (legacy)

| Aspek               | v1 (`treatwell_scraper.go`)                  | v2 (`scraper.go`)                                  |
| ------------------- | -------------------------------------------- | -------------------------------------------------- |
| Argumen             | URL listing manual                           | `--kategori=<slug>` (URL dibangun otomatis)        |
| Mapping kategori    | `guessCategory()` ngebikin kategori baru     | Langsung pakai 7 kategori utama dari registry      |
| Tagging service     | Cuma `id_kategori`                           | `id_kategori` **+** `id_sub_kategori`              |
| Pivot salon-cat     | Tidak ada                                    | Pivot `salon_sub_kategori` di-derive otomatis      |
| Output kategori.json| Selalu di-overwrite                          | **Tidak di-touch** (di-seed dari PHP)              |
| Loop multi-kategori | Manual (jalankan exe berkali-kali)           | `--kategori=all`                                   |

`treatwell_scraper.go` (v1) tetap dibiarkan sbg **fallback** kalau perlu
scrape URL custom yang tidak masuk pola `treatment-group-*`. Tidak akan di
maintain lagi.

---

## 7. Checklist Verifikasi Setelah Run

Setelah `scrape → migrate → db:seed`, cek:

- [ ] `SELECT COUNT(*) FROM kategori;` → **7**
- [ ] `SELECT COUNT(*) FROM sub_kategori;` → **43**
- [ ] `SELECT COUNT(*) FROM salon_sub_kategori;` → **> 0** (kalau scraper sudah dijalankan)
- [ ] `SELECT id_kategori, COUNT(*) FROM service GROUP BY id_kategori;`
      → semua `id_kategori` ∈ {NULL, 1, 2, 3, 4, 5, 6, 7}
- [ ] `SELECT COUNT(*) FROM service WHERE id_sub_kategori IS NOT NULL;`
      → idealnya > 70% dari total service (tergantung kualitas keyword match)
- [ ] Buka `/` di browser → navbar dropdown **Hair / Nails / dst.** muncul
      dari DB (bukan hardcode).
- [ ] Klik salah satu sub-kategori (mis. "Pedicure") → halaman
      `/sub-kategori/pedicure` listing salon yang punya pedicure.
- [ ] Klik kategori utama (mis. "Nails") → halaman `/kategori/nails` listing
      semua salon yang punya minimal 1 sub-kategori di Nails.

---

## 8. Troubleshooting

| Masalah                                                | Sebab umum                                                 | Solusi                                                                                            |
| ------------------------------------------------------ | ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| `service.id_sub_kategori` kebanyakan NULL              | Keyword di `kategoriRegistry` belum nutup variasi naming   | Tambah keyword di `scraper.go`, build ulang, scrape ulang (data baru saja yg dapat tagging)       |
| Pivot `salon_sub_kategori` kosong setelah seed         | `ServiceSeeder` jalan sebelum sub_kategori → semua null    | Pastikan urutan di `DatabaseSeeder.php` benar (sub_kategori sebelum service)                      |
| Halaman `/sub-kategori/{slug}` 404                     | Slug di `SubKategoriSeeder.php` ≠ slug yg diketik di URL   | Cek `SELECT slug FROM sub_kategori`, samakan                                                      |
| Navbar dropdown kosong                                 | Cache `viygo:navbar:kategori` (15 menit) nyimpan data lama | `php artisan cache:clear`                                                                         |
| Build `scraper.go` error: undefined `findNodes`        | Kompilasi conflict dgn file `parse_*.go` di folder sama    | Pakai build per file: `go build -o scraper.exe scraper.go` (bukan `go build .`)                   |
| `php artisan migrate` gagal di migration 2026_05_10_3  | Driver lama tidak support `change()` non-doctrine          | Pastikan Laravel 11+ atau install `composer require doctrine/dbal`                                |
| `db:seed` lambat (>5 menit utk 40rb service)           | `insert` 1-by-1                                            | Sudah pakai chunk 500 di `ServiceSeeder` — kalau masih lambat, naikkan ke 2000                    |

---

## 9. Lampiran — Mapping Kategori → Sub-kategori

### Hair (id_kategori=1)
| ID | Slug                          | Treatwell slug              |
| -- | ----------------------------- | --------------------------- |
| 1  | ladies-haircuts               | ladies-haircuts-1           |
| 2  | blow-dry                      | blow-dry                    |
| 3  | hair-colouring-highlights     | hair-colouring              |
| 4  | ladies-brazilian-blow-dry     | ladies-brazilian-blow-dry   |
| 5  | balayage-ombre                | balayage                    |
| 6  | mens-haircut                  | men-s-haircut               |

### Hair Removal (id_kategori=2)
| ID | Slug              | Treatwell slug      |
| -- | ----------------- | ------------------- |
| 7  | facial-threading  | facial-threading    |
| 8  | ladies-waxing     | ladies-waxing       |
| 9  | sugaring          | sugaring            |
| 10 | hollywood-waxing  | hollywood-waxing    |
| 11 | mens-waxing       | men-s-waxing        |
| 12 | ladies-leg-waxing | ladies-leg-waxing   |

### Massage (id_kategori=3)
| ID | Slug                  | Treatwell slug          |
| -- | --------------------- | ----------------------- |
| 13 | deep-tissue-massage   | deep-tissue-massage     |
| 14 | swedish-massage       | swedish-massage         |
| 15 | therapeutic-massage   | therapeutic-massage     |
| 16 | thai-massage          | thai-massage            |
| 17 | aromatherapy-massage  | aromatherapy-massage    |
| 18 | hot-stone-massage     | hot-stone-massage       |

### Nails (id_kategori=4)
| ID | Slug                              | Treatwell slug                |
| -- | --------------------------------- | ----------------------------- |
| 19 | pedicure                          | pedicure                      |
| 20 | manicure                          | manicure                      |
| 21 | nail-gel-polish-removal           | nail-or-gel-polish-removal    |
| 22 | gel-nails-manicure                | gel-nails-manicure            |
| 23 | gel-nails-pedicure                | gel-nails-pedicure            |
| 24 | acrylic-hard-gel-nail-extensions  | hard-gel-extensions-overlays  |

### Face (id_kategori=5)
| ID | Slug                       | Treatwell slug             |
| -- | -------------------------- | -------------------------- |
| 25 | classic-facials            | classic-facials            |
| 26 | eyelash-extensions         | eyelash-extensions         |
| 27 | eyebrow-eyelash-tinting    | eyebrow-eyelash-tinting    |
| 28 | eyebrow-threading          | eyebrow-threading          |
| 29 | eyebrow-waxing             | eyebrow-waxing             |
| 30 | definition-brows           | brow-definition            |

### Body (id_kategori=6)
| ID | Slug                              | Treatwell slug                  |
| -- | --------------------------------- | ------------------------------- |
| 31 | spray-tanning-sunless-tanning     | spray-tanning-and-sunless-tanning |
| 32 | body-exfoliation-treatments       | body-exfoliation-treatments     |
| 33 | body-wraps                        | body-wraps                      |
| 34 | colonic-hydrotherapy              | colonic-hydrotherapy            |
| 35 | cryolipolysis                     | cryolipolysis                   |
| 36 | cellulite-treatments              | cellulite-treatments            |

### Men's (id_kategori=7)
| ID | Slug                       | Treatwell slug              |
| -- | -------------------------- | --------------------------- |
| 37 | mens-haircut               | men-s-haircut               |
| 38 | beard-trims-shaves         | beard-trimming              |
| 39 | mens-hair-colouring        | men-s-hair-colouring        |
| 40 | mens-brazilian-blow-dry    | men-s-brazilian-blow-dry    |
| 41 | mens-facials               | men-s-facials               |
| 42 | mens-waxing                | men-s-waxing                |
| 43 | barbers                    | barbers                     |

---

## 10. Kontak / Owner Refactor

- Refactor schema oleh: agent VSCode (Claude Opus 4.7)
- Permintaan asli: kategori utama untuk navbar + sub_kategori sebagai
  penghubung salon ↔ service ↔ navbar + scraper Go per-kategori.
- Tanggal: 2026-05-10.
- Riwayat audit kategori lama: `database/data/LAPORAN_AUDIT_KATEGORI_V2.md`.
