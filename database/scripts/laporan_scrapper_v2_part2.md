# Laporan Scraper VIYGO v2 — Part 2 (43 Kategori Treatment)

> Tanggal: **2026-05-10**
> Versi: **v2 Part 2** — koreksi pemahaman dari Part 1
> Binary: `database/scripts/scraper.exe` (sumber: `scraper.go`)

---

## 0. Penting — Apa yang Berubah dari Part 1?

Bagian dari Part 1 yang **dibatalkan**:
- ❌ Skema "kategori = 7 grup utama"
- ❌ "sub_kategori = 43 treatment dgn FK ke kategori"
- ❌ Pivot `salon_sub_kategori`

Skema **baru** Part 2 (sesuai permintaan user):
- ✅ `kategori` = **43 baris** (semua treatment Treatwell yg disebut user)
- ✅ Field `kategori.grup` (string: Hair / Hair Removal / Massage / Nails / Face / Body / Men's) untuk navbar grouping
- ✅ `sub_kategori` = **nama treatment generik** (deduplicated dari nama service hasil scrape — mis. "Premium Bob Cut", "Express Blow Dry 30 min")
- ✅ Pivot `kategori_sub_kategori` (M:N) — 1 sub-kategori bisa diklasifikasikan ke beberapa kategori (mis. "Cut & Blow Dry" → masuk kategori `ladies-haircuts` + `blow-dry`)
- ✅ Pivot `salon_kategori` (M:N) — derived dari `service.id_kategori`, untuk menjawab "salon mana saja yg menyediakan kategori X?"
- ✅ `service.id_sub_kategori` (FK nullable) — service per salon di-tag ke 1 sub-kategori (treatment generik) sebagai primary classification

> **Catatan jumlah kategori**: User menyebut "49 kategori utama" tapi listnya
> berisi **43** treatment (6+6+6+6+6+6+7). Saya pakai 43 sesuai daftar literal.
> Kalau user mau menambah 6 lagi, tinggal append di
> [`KategoriSeeder.dataset()`](../seeders/KategoriSeeder.php) dan
> `kategoriRegistry` di [`scraper.go`](scraper.go).

---

## 1. Skema Database Final

### 1.1 `kategori` (43 baris) — schema baru

```sql
CREATE TABLE kategori (
  id_kategori        BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name               VARCHAR(150)  NOT NULL,         -- "Ladies' Haircuts"
  slug               VARCHAR(180)  NOT NULL UNIQUE,  -- "ladies-haircuts"
  grup               VARCHAR(60)   NOT NULL,         -- "Hair" / "Nails" / dll
  treatwell_slug     VARCHAR(180)  NULL,             -- "ladies-haircuts-1"
  deskripsi          TEXT          NULL,
  icon_url           VARCHAR(255)  NULL,
  urutan             SMALLINT UNSIGNED DEFAULT 0,    -- urutan dalam grup
  is_active          TINYINT(1)    DEFAULT 1,
  created_at, updated_at,
  INDEX (grup), INDEX (is_active)
);
```

Diisi oleh [`KategoriSeeder.php`](../seeders/KategoriSeeder.php) dengan
**43 baris** (lihat lampiran §8).

### 1.2 `sub_kategori` (variabel — diisi scraper)

```sql
CREATE TABLE sub_kategori (
  id_sub_kategori    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name               VARCHAR(200)  NOT NULL,        -- "Premium Bob Cut"
  slug               VARCHAR(220)  NOT NULL UNIQUE, -- "premium-bob-cut"
  deskripsi          TEXT          NULL,
  icon_url           VARCHAR(255)  NULL,
  is_active          TINYINT(1)    DEFAULT 1,
  created_at, updated_at,
  INDEX (is_active)
);
```

**Bukan** punya FK ke kategori — relasi M:N via pivot
`kategori_sub_kategori`. Diisi oleh `SubKategoriSeeder` dari
`database/data/sub_kategori.json` yang dihasilkan scraper Go.

### 1.3 Pivot `kategori_sub_kategori` (M:N)

```sql
CREATE TABLE kategori_sub_kategori (
  id                 BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  id_kategori        BIGINT UNSIGNED NOT NULL,  FK → kategori(id_kategori)  ON DELETE CASCADE,
  id_sub_kategori    BIGINT UNSIGNED NOT NULL,  FK → sub_kategori(id_sub_kategori)  ON DELETE CASCADE,
  created_at, updated_at,
  UNIQUE (id_kategori, id_sub_kategori), INDEX (id_sub_kategori)
);
```

Pivot ini menjawab: "satu treatment generik (mis. **Cut & Blow Dry**) diklasifikasikan ke **kategori apa saja**?" — bisa 1, bisa banyak. Diisi
oleh `KategoriSubKategoriSeeder` dari `database/data/kategori_sub_kategori.json`.

### 1.4 Pivot `salon_kategori` (M:N) — derived

```sql
CREATE TABLE salon_kategori (
  id                 BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  id_salon           BIGINT UNSIGNED NOT NULL,  FK → salon(id_salon)  ON DELETE CASCADE,
  id_kategori        BIGINT UNSIGNED NOT NULL,  FK → kategori(id_kategori)  ON DELETE CASCADE,
  created_at, updated_at,
  UNIQUE (id_salon, id_kategori), INDEX (id_kategori)
);
```

Tidak diisi langsung dari JSON — diturunkan otomatis oleh
`SalonKategoriSeeder` dari `distinct(service.id_kategori per id_salon)`.

### 1.5 Perubahan `service`

```sql
ALTER TABLE service
  MODIFY id_kategori BIGINT UNSIGNED NULL,
  ADD COLUMN id_sub_kategori BIGINT UNSIGNED NULL AFTER id_kategori,
  ADD CONSTRAINT FOREIGN KEY (id_sub_kategori)
      REFERENCES sub_kategori(id_sub_kategori) ON DELETE SET NULL,
  ADD INDEX (id_sub_kategori);
```

Setelah migration:
- `service.id_kategori` ∈ {NULL, 1..43}
- `service.id_sub_kategori` ∈ {NULL, ID dari sub_kategori}

### 1.6 Diagram Relasi Final

```
                     ┌──────────────────────────────────┐
                     │          kategori (43)           │
                     │   id, name, slug, grup, ...      │
                     └─┬─────────────────────────────┬──┘
                       │                             │
            M:N pivot  │                             │  M:N pivot
   kategori_sub_kategori                          salon_kategori
                       │                             │
                       ▼                             ▼
        ┌────────────────────┐            ┌──────────────┐
        │   sub_kategori     │            │    salon     │
        │ "Premium Bob Cut"  │            │              │
        │ "Express Blow Dry" │            └──────┬───────┘
        └─────────┬──────────┘                   │
                  │                              │ 1:N
                  │ 1:N                          │
                  ▼                              ▼
        ┌────────────────────────────────────────────────┐
        │                 service                        │
        │  id_salon, id_kategori (NULL-able),            │
        │  id_sub_kategori (NULL-able), nama, harga, ... │
        └────────────────────────────────────────────────┘
```

---

## 2. Cara Kerja Scraper v2 Part 2

### 2.1 Alur singkat

```
┌──────────────────┐    ┌─────────────────┐   ┌──────────────────────┐
│ kategoriRegistry │───▶│  build URL      │──▶│ scrapeListingPage()  │
│   (43 entry)     │    │ Treatwell per   │   │  → list salon URL    │
└──────────────────┘    │  kategori+kota  │   └──────────┬───────────┘
                        └─────────────────┘              │
                                                         ▼
                                              ┌──────────────────────┐
                                              │  scrapeDetailPage()  │
                                              │  paralel (20 worker) │
                                              └──────────┬───────────┘
                                                         │
                                                         ▼
                                              ┌────────────────────────┐
                                              │ ensureSubKategori()    │
                                              │ classifyToKategori()   │
                                              │ ensurePivot()          │
                                              └──────────┬─────────────┘
                                                         │
                                                         ▼
                                              ┌────────────────────────┐
                                              │ SAVE per kategori      │
                                              │ ke database/data/*.json│
                                              └────────────────────────┘
```

Save dilakukan **setelah setiap kategori selesai** (bukan di akhir saja),
jadi kalau scraping di-interrupt di kategori ke-15 (dari 43), 14 kategori
sebelumnya sudah aman tersimpan.

### 2.2 `kategoriRegistry` — sumber kebenaran

Di dalam `scraper.go` ada slice `kategoriRegistry []KategoriDef` berisi
**43 entry**. Tiap entry punya:

| Field           | Contoh                       | Kegunaan                                                |
| --------------- | ---------------------------- | ------------------------------------------------------- |
| `IDKategori`    | `1`                          | **HARUS sama** dgn auto-increment `KategoriSeeder`      |
| `Slug`          | `"ladies-haircuts"`          | Cocok dgn `kategori.slug` di DB                         |
| `Name`          | `"Ladies' Haircuts"`         | Display                                                 |
| `Grup`          | `"Hair"`                     | Dipakai navbar grouping & filter `--grup=Hair`          |
| `TreatwellSlug` | `"ladies-haircuts-1"`        | Dipakai utk membentuk URL listing Treatwell             |

> ⚠️ **Konsistensi ID**: kalau `KategoriSeeder.dataset()` diubah (urutan
> baris bergeser → ID auto-increment berubah), `kategoriRegistry` di
> `scraper.go` **wajib disesuaikan** lalu `go build -o scraper.exe scraper.go`.

### 2.3 URL listing Treatwell per kategori

Pola URL yang dipakai:

```
https://www.treatwell.co.uk/places/treatment-{TreatwellSlug}/in-{kota}-uk/
```

Contoh untuk **Ladies' Haircuts** di London:

```
https://www.treatwell.co.uk/places/treatment-ladies-haircuts-1/in-london-uk/
```

Kalau pola treatment-spesifik tidak ada hasil di Treatwell, scraper akan
return 0 listing dan **lanjut** ke kategori berikutnya (tidak crash).
Kategori yg sering kena issue ini adalah yg slug Treatwell-nya tidak
tepat — silakan tune `kategoriRegistry[*].TreatwellSlug` lalu build ulang.

### 2.4 Klasifikasi service → sub_kategori + pivot

Untuk setiap service yang ditemukan di JSON-LD `hasOfferCatalog` salon:

1. **Buat sub_kategori** (deduplicated by slug nama service):
   - `slug = toSlug(service.Name)` (mis. "Premium Bob Cut" → `premium-bob-cut`)
   - Kalau slug sudah ada → reuse `id_sub_kategori`
   - Kalau belum → buat entry baru di `sub_kategori.json`

2. **Klasifikasi nama service ke kategori** (M:N) via `classifyToKategori`:
   - Pass 1: cocokkan keyword utk kategori `primary` (yg lagi di-scrape)
   - Pass 2: scan semua kategori lain — kalau keyword cocok, tambahkan
   - Fallback: kalau tidak ada match, paksa primary (karena service ini
     ditemukan di listing kategori `primary`, jadi minimal terkait)

3. **Insert pivot** `kategori_sub_kategori` untuk setiap match (deduplicated).

4. **Insert service**:
   - `id_kategori` = primary (kategori yg lagi di-scrape)
   - `id_sub_kategori` = id sub_kategori yg baru dibuat / di-reuse

Contoh: scraper sedang loop kategori `Blow Dry` (id=2). Salon X punya
service "Cut & Blow Dry — 60 min — £45". Maka:

- Sub_kategori baru: `Cut & Blow Dry` → slug `cut-blow-dry`, id=N
- Klasifikasi: "cut & blow dry" cocok dgn keyword `blow dry` (id=2) **dan**
  keyword `ladies cut` (id=1, kalau cocok) → pivot inserts: `(2,N)` dan `(1,N)`
- Service: `id_kategori=2`, `id_sub_kategori=N`

Konsekuensi: query `Salon::whereHas('kategoris', fn ($k) => $k->where('id_kategori', 1))`
juga akan menemukan salon X (lewat pivot `salon_kategori` yg di-derive
dari distinct service.id_kategori — yg di sini = 2, jadi salon X tidak
akan muncul di kategori 1 kecuali ada service lain dgn `id_kategori=1`).

Kalau ingin salon X muncul juga di listing kategori 1 (Ladies' Haircuts),
tambahkan keyword `cut` atau `cut & blow dry` di `kategoriKeywords[1]` di
`scraper.go`, **lalu jalankan ulang scraper untuk kategori 1** agar
service yg sama dapat dimasukkan dgn `id_kategori=1` juga (akan jadi 2
row service di DB — itu OK, dianggap variant berbeda).

### 2.5 Output JSON

Setelah scraping satu / beberapa kategori, file-file ini di-update
(append + dedup by URL/slug):

| File                              | Isi                                                          |
| --------------------------------- | ------------------------------------------------------------ |
| `kota.json`                       | Daftar kota (auto-extend)                                    |
| `salon.json`                      | Salon (skip duplikat by source_url)                          |
| `service.json`                    | Service per salon, **id_kategori 1-43 + id_sub_kategori**    |
| `staff.json`                      | Staff                                                        |
| `salon_images.json`               | Foto                                                         |
| `sub_kategori.json` 🆕            | Treatment generik (deduplicated dari nama service)           |
| `kategori_sub_kategori.json` 🆕   | Pivot M:N kategori ↔ sub_kategori                            |

`kategori.json` **tidak di-touch** scraper — di-seed dari PHP
(KategoriSeeder).

---

## 3. Cara Pemakaian

### 3.1 Build

```powershell
cd database\scripts
.\build.bat
# atau manual:
go build -o scraper.exe scraper.go
```

### 3.2 Scrape per kategori

```powershell
# 1 kategori (slug VIYGO):
.\scraper.exe --kategori=blow-dry
.\scraper.exe --kategori=manicure
.\scraper.exe --kategori=eyelash-extensions

# 1 kategori, kota tertentu, batasi pages:
.\scraper.exe --kategori=pedicure --kota=manchester --max-pages=3

# Kota multi (comma-separated):
.\scraper.exe --kategori=hot-stone-massage --kota=london,leeds,bristol
```

### 3.3 Scrape per grup

```powershell
.\scraper.exe --grup=Hair
.\scraper.exe --grup="Hair Removal" --max-pages=3
.\scraper.exe --grup=Massage --kota=london
```

### 3.4 Scrape semua 43 kategori

```powershell
.\scraper.exe --kategori=all --max-pages=2
```

> ⚠️ **Estimasi durasi**: 43 kategori × 3 kota × 2 pages × ~15 salon =
> ~3.870 detail-fetch. Dengan 20 worker, ~3-5 menit per kategori → total
> 2-4 jam. Disarankan jalankan `--kategori=all` malam hari, atau loop per
> grup.

### 3.5 Workflow penuh

```powershell
# 1. Reset JSON kalau mau dari nol (opsional)
cd database\data
@('salon.json', 'service.json', 'staff.json', 'salon_images.json', 'kota.json', 'sub_kategori.json', 'kategori_sub_kategori.json') | ForEach-Object {
    '[]' | Out-File $_ -Encoding utf8
}

# 2. Scrape (per grup recommended biar lebih kontrol)
cd ..\scripts
.\scraper.exe --grup=Hair         --max-pages=3
.\scraper.exe --grup="Hair Removal" --max-pages=3
.\scraper.exe --grup=Massage      --max-pages=3
.\scraper.exe --grup=Nails        --max-pages=3
.\scraper.exe --grup=Face         --max-pages=3
.\scraper.exe --grup=Body         --max-pages=3
.\scraper.exe --grup="Men's"      --max-pages=3

# 3. Migrate & seed
cd ..\..
php artisan migrate:fresh --seed
```

Output `db:seed`:

```
[KategoriSeeder]               43 kategori treatment
[SubKategoriSeeder]             ~N treatment generik (dari sub_kategori.json)
[KategoriSubKategoriSeeder]     ~M baris pivot
[ServiceSeeder]                 service per salon (id_kategori 1..43)
[SalonKategoriSeeder]           pivot salon_kategori derived
```

---

## 4. Verifikasi setelah Run

```sql
-- 1. Kategori = 43
SELECT COUNT(*) FROM kategori;          -- expect: 43
SELECT grup, COUNT(*) FROM kategori GROUP BY grup;
-- Hair: 6, Hair Removal: 6, Massage: 6, Nails: 6, Face: 6, Body: 6, Men's: 7

-- 2. Sub_kategori (jumlah tergantung scraping)
SELECT COUNT(*) FROM sub_kategori;      -- biasanya beberapa ratus

-- 3. Pivot kategori_sub_kategori
SELECT COUNT(*) FROM kategori_sub_kategori;

-- 4. Distribusi service per kategori
SELECT k.name, COUNT(s.id_service)
FROM kategori k LEFT JOIN service s ON s.id_kategori = k.id_kategori
GROUP BY k.id_kategori, k.name
ORDER BY k.urutan;

-- 5. Salon yg muncul di kategori "Pedicure"
SELECT s.nama_salon
FROM salon s
JOIN salon_kategori sk ON sk.id_salon = s.id_salon
JOIN kategori k ON k.id_kategori = sk.id_kategori
WHERE k.slug = 'pedicure';

-- 6. Sub_kategori "Cut & Blow Dry" muncul di kategori apa saja?
SELECT k.name
FROM kategori k
JOIN kategori_sub_kategori ksk ON ksk.id_kategori = k.id_kategori
JOIN sub_kategori sk ON sk.id_sub_kategori = ksk.id_sub_kategori
WHERE sk.slug = 'cut-blow-dry';
```

Smoke test UI:
- `/` → navbar 7 grup, dropdown isinya 6-7 kategori per grup
- `/kategori/blow-dry` → halaman kategori, listing salon
- `/kategori-grup/Hair` → halaman grup, listing 6 kategori Hair + salon
- `/sub-kategori/{any-slug-yg-ada}` → halaman sub-kategori

---

## 5. Troubleshooting

| Masalah                                                                | Sebab                                              | Solusi                                                                       |
| ---------------------------------------------------------------------- | -------------------------------------------------- | ---------------------------------------------------------------------------- |
| Scraper return 0 listing utk kategori X                                | TreatwellSlug salah / treatment URL beda format    | Cek manual di Treatwell.co.uk, update `TreatwellSlug` di `kategoriRegistry`  |
| `sub_kategori.json` membengkak (>10rb baris)                           | Nama service di Treatwell sangat unique per salon  | Tambah dedup logic post-process: cluster slug serupa via Levenshtein         |
| Pivot `kategori_sub_kategori` punya entries dgn id_kategori = NULL     | Bug di scraper                                     | Cek `classifyToKategori` — pastikan return non-empty (fallback ke primary)   |
| Halaman `/kategori/{slug}` kosong padahal salon ada di Treatwell       | Pivot `salon_kategori` belum di-seed               | Jalankan `php artisan db:seed` lagi (ServiceSeeder + SalonKategoriSeeder)    |
| Navbar dropdown kosong                                                 | Cache `viygo:navbar:kategori_v2` (15 menit)        | `php artisan cache:clear`                                                    |
| Build `scraper.go` error: undefined `findNodes`                        | Konflik dgn parse_*.go                             | Build per file: `go build -o scraper.exe scraper.go` (bukan `go build .`)    |
| `php artisan migrate` gagal: FK constraint                             | Data lama di service.id_kategori menunjuk ID > 43  | Migration #1 sudah set `id_kategori = NULL` sebelum re-attach FK             |

---

## 6. Daftar File yang Berubah

### Schema (5 migration baru)

- [`database/migrations/2026_05_10_000001_restructure_kategori_table.php`](../migrations/2026_05_10_000001_restructure_kategori_table.php)
- [`database/migrations/2026_05_10_000002_create_sub_kategori_table.php`](../migrations/2026_05_10_000002_create_sub_kategori_table.php)
- [`database/migrations/2026_05_10_000003_create_kategori_sub_kategori_table.php`](../migrations/2026_05_10_000003_create_kategori_sub_kategori_table.php)
- [`database/migrations/2026_05_10_000004_create_salon_kategori_table.php`](../migrations/2026_05_10_000004_create_salon_kategori_table.php)
- [`database/migrations/2026_05_10_000005_add_id_sub_kategori_to_service.php`](../migrations/2026_05_10_000005_add_id_sub_kategori_to_service.php)

### Seeders

- [`database/seeders/KategoriSeeder.php`](../seeders/KategoriSeeder.php) — 43 baris
- [`database/seeders/SubKategoriSeeder.php`](../seeders/SubKategoriSeeder.php) — dari `sub_kategori.json`
- [`database/seeders/KategoriSubKategoriSeeder.php`](../seeders/KategoriSubKategoriSeeder.php) — dari `kategori_sub_kategori.json`
- [`database/seeders/SalonKategoriSeeder.php`](../seeders/SalonKategoriSeeder.php) — derive dari service
- [`database/seeders/ServiceSeeder.php`](../seeders/ServiceSeeder.php) — kompatibel format lama+baru
- [`database/seeders/DatabaseSeeder.php`](../seeders/DatabaseSeeder.php) — urutan seeder baru

### Models

- [`app/Models/Kategori.php`](../../app/Models/Kategori.php) — `subKategori()` belongsToMany, `salons()` belongsToMany, `scopeGrup`
- [`app/Models/SubKategori.php`](../../app/Models/SubKategori.php) — `kategori()` belongsToMany
- [`app/Models/Salon.php`](../../app/Models/Salon.php) — `kategoris()` belongsToMany (replace `subKategoris`)
- [`app/Models/Service.php`](../../app/Models/Service.php) — `subKategori()` belongsTo (sudah ada dari Part 1, tetap)

### Controller & View

- [`app/Http/Controllers/KategoriController.php`](../../app/Http/Controllers/KategoriController.php) — `show`, `showGrup`, `showSub`
- [`routes/web.php`](../../routes/web.php#L25-L27) — 3 route kategori
- [`resources/views/components/viygo-navbar.blade.php`](../../resources/views/components/viygo-navbar.blade.php) — group by `grup`, link ke kategori
- [`resources/views/kategori/grup.blade.php`](../../resources/views/kategori/grup.blade.php) — view baru untuk halaman grup

### Scraper Go

- [`database/scripts/scraper.go`](scraper.go) — refactor: 43 kategori, M:N pivot, save per kategori
- [`database/scripts/build.bat`](build.bat) — build script (sudah dari Part 1)

---

## 7. Beda dgn Part 1

| Aspek                    | Part 1 (cancelled)                       | Part 2 (current)                                            |
| ------------------------ | ---------------------------------------- | ----------------------------------------------------------- |
| `kategori` rows          | 7 grup utama                             | **43 treatment Treatwell** (+ field `grup`)                 |
| `sub_kategori` rows      | 43 (treatment, FK ke kategori)           | Variabel — **deduplicated nama service** dari scraper       |
| Relasi sub_kategori      | N:1 (belongsTo Kategori)                 | **M:N (pivot kategori_sub_kategori)**                       |
| Pivot salon-related      | `salon_sub_kategori`                     | **`salon_kategori`** (derived dari service)                 |
| Navbar dropdown          | Loop kategori utama → sub_kategori       | Loop **grup** → kategori dalam grup                         |
| Scraper kategori loop    | 7 kategori                               | **43 kategori** (+ flag `--grup` untuk batch per grup)      |
| `--kategori=hair`        | 1 dari 7 kategori utama                  | (tidak valid — pakai `--grup=Hair` atau slug treatment)     |
| Output file scraper      | salon, service, staff, images, kota      | + `sub_kategori.json` + `kategori_sub_kategori.json`        |

---

## 8. Lampiran — 43 Kategori (KategoriSeeder.dataset)

| ID | Grup         | Slug                                | Name                                  | Treatwell Slug                       |
| -- | ------------ | ----------------------------------- | ------------------------------------- | ------------------------------------ |
| 1  | Hair         | ladies-haircuts                     | Ladies' Haircuts                       | ladies-haircuts-1                    |
| 2  | Hair         | blow-dry                            | Blow Dry                               | blow-dry                             |
| 3  | Hair         | ladies-hair-colouring-highlights    | Ladies' Hair Colouring & Highlights    | hair-colouring                       |
| 4  | Hair         | ladies-brazilian-blow-dry           | Ladies' Brazilian Blow Dry             | ladies-brazilian-blow-dry            |
| 5  | Hair         | balayage-ombre                      | Balayage & Ombre                       | balayage                             |
| 6  | Hair         | mens-haircut                        | Men's Haircut                          | men-s-haircut                        |
| 7  | Hair Removal | facial-threading                    | Facial Threading                       | facial-threading                     |
| 8  | Hair Removal | ladies-waxing                       | Ladies' Waxing                         | ladies-waxing                        |
| 9  | Hair Removal | sugaring                            | Sugaring                               | sugaring                             |
| 10 | Hair Removal | hollywood-waxing                    | Hollywood Waxing                       | hollywood-waxing                     |
| 11 | Hair Removal | mens-waxing                         | Men's Waxing                           | men-s-waxing                         |
| 12 | Hair Removal | ladies-leg-waxing                   | Ladies' Leg Waxing                     | ladies-leg-waxing                    |
| 13 | Massage      | deep-tissue-massage                 | Deep Tissue Massage                    | deep-tissue-massage                  |
| 14 | Massage      | swedish-massage                     | Swedish Massage                        | swedish-massage                      |
| 15 | Massage      | therapeutic-massage                 | Therapeutic Massage                    | therapeutic-massage                  |
| 16 | Massage      | thai-massage                        | Thai Massage                           | thai-massage                         |
| 17 | Massage      | aromatherapy-massage                | Aromatherapy Massage                   | aromatherapy-massage                 |
| 18 | Massage      | hot-stone-massage                   | Hot Stone Massage                      | hot-stone-massage                    |
| 19 | Nails        | pedicure                            | Pedicure                               | pedicure                             |
| 20 | Nails        | manicure                            | Manicure                               | manicure                             |
| 21 | Nails        | nail-gel-polish-removal             | Nail or Gel Polish Removal             | nail-or-gel-polish-removal           |
| 22 | Nails        | gel-nails-manicure                  | Gel Nails Manicure                     | gel-nails-manicure                   |
| 23 | Nails        | gel-nails-pedicure                  | Gel Nails Pedicure                     | gel-nails-pedicure                   |
| 24 | Nails        | acrylic-hard-gel-nail-extensions    | Acrylic, Hard Gel & Nail Extensions    | hard-gel-extensions-overlays         |
| 25 | Face         | classic-facials                     | Classic Facials                        | classic-facials                      |
| 26 | Face         | eyelash-extensions                  | Eyelash Extensions                     | eyelash-extensions                   |
| 27 | Face         | eyebrow-eyelash-tinting             | Eyebrow and Eyelash Tinting            | eyebrow-eyelash-tinting              |
| 28 | Face         | eyebrow-threading                   | Eyebrow Threading                      | eyebrow-threading                    |
| 29 | Face         | eyebrow-waxing                      | Eyebrow Waxing                         | eyebrow-waxing                       |
| 30 | Face         | definition-brows                    | Definition Brows                       | brow-definition                      |
| 31 | Body         | spray-tanning-sunless-tanning       | Spray Tanning and Sunless Tanning      | spray-tanning-and-sunless-tanning    |
| 32 | Body         | body-exfoliation-treatments         | Body Exfoliation Treatments            | body-exfoliation-treatments          |
| 33 | Body         | body-wraps                          | Body Wraps                             | body-wraps                           |
| 34 | Body         | colonic-hydrotherapy                | Colonic Hydrotherapy                   | colonic-hydrotherapy                 |
| 35 | Body         | cryolipolysis                       | Cryolipolysis                          | cryolipolysis                        |
| 36 | Body         | cellulite-treatments                | Cellulite Treatments                   | cellulite-treatments                 |
| 37 | Men's        | mens-haircut-mens                   | Men's Haircut                          | men-s-haircut                        |
| 38 | Men's        | beard-trims-shaves                  | Beard Trims and Shaves                 | beard-trimming                       |
| 39 | Men's        | mens-hair-colouring                 | Men's Hair Colouring                   | men-s-hair-colouring                 |
| 40 | Men's        | mens-brazilian-blow-dry             | Men's Brazilian Blow Dry               | men-s-brazilian-blow-dry             |
| 41 | Men's        | mens-facials                        | Men's Facials                          | men-s-facials                        |
| 42 | Men's        | mens-waxing-mens                    | Men's Waxing                           | men-s-waxing                         |
| 43 | Men's        | barbers                             | Barbers                                | barbers                              |

---

## 9. Catatan tentang "49"

User menyebut **49 kategori utama** namun listnya berisi **43**:
- Hair: 6
- Hair Removal: 6
- Massage: 6
- Nails: 6
- Face: 6
- Body: 6
- Men's: 7

Total: **43**.

Saya pakai 43 sesuai daftar literal. Untuk menambah jadi 49:
1. Tambah 6 baris baru di [`KategoriSeeder.dataset()`](../seeders/KategoriSeeder.php).
2. Tambah baris yang sama (dgn ID auto-increment 44-49) di
   `kategoriRegistry` di [`scraper.go`](scraper.go).
3. Tambah keyword utk kategori baru di `kategoriKeywords` (scraper.go).
4. `go build -o scraper.exe scraper.go`.
5. `php artisan migrate:fresh --seed`.

---

## 10. Verifikasi Build

Per **2026-05-10**:

- ✅ `go build -o scraper.exe scraper.go` → BUILD OK
- ✅ Semua 15 file PHP (5 migration + 6 seeder + 4 model/controller) → syntax OK (`php -l`)
- ✅ `scraper.exe` (run tanpa flag) → menampilkan usage dgn 43 kategori

---

## 11. Apa yg masih bisa di-tune

- **Performance scraping**: kalau Treatwell rate-limit aggresif, turunkan
  `maxWorkers` (default 20 → coba 10).
- **Klasifikasi false-positive**: kalau "deep cleanse facial" terklasifikasi
  ke kategori `face` saja padahal harusnya juga ke `body` (kalau ada
  varian "deep cleanse body"), tambah/sempit keyword di `kategoriKeywords`.
- **Sub_kategori dedup lemah**: scraper dedup berdasarkan slug exact.
  Mis. "Premium Cut" dan "premium cut" → 2 entry. Untuk menyatukan,
  scraper sudah lowercase + slug-ify, tapi spasi/tanda baca tetap bikin
  beda. Kalau perlu fuzzy dedup: post-process via Levenshtein.
- **Per-grup parallelism**: saat ini scraper proses kategori sequentially
  agar save per-kategori bisa atomic. Untuk speed, bisa ubah jadi
  paralel per-grup (7 goroutine paralel). Trade-off: kalau crash, bisa
  hilang banyak data.
