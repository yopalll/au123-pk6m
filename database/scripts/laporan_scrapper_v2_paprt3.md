# Laporan Scraper VIYGO Part 3 — 7 Kategori + 42 Sub-Kategori

> Tanggal: **2026-05-10**
> Versi: **Part 3** (final, koreksi dari Part 1 & Part 2)
> Binary: `database/scripts/scraper.exe` (sumber: `scraper.go`)
> Legacy scraper Go (V1, parse_*, treatwell_scraper) sudah dipindahkan
> ke `database/scripts/_legacy/` agar tidak konflik kompilasi.

---

## 0. Hitungan: Dari 49 Items Navbar ke 7 + 42

Daftar literal user (dropdown navbar Treatwell):

```
Hair:                                  ← header (1)
  Ladies' Haircuts                     ← sub
  Blow Dry                             ← sub
  Ladies' Hair Colouring & Highlights  ← sub
  Ladies' Brazilian Blow Dry           ← sub
  Balayage & Ombre                     ← sub
  Men's Haircut                        ← sub
  See all hair treatments              ← skip (handled by query)
Hair Removal:                          ← header (2)
  ... (6 sub + 1 See all)
Massage:                               ← header (3)
  ... (6 sub + 1 See all)
Nails:                                 ← header (4)
  ... (6 sub + 1 See all)
Face:                                  ← header (5)
  ... (6 sub + 1 See all)
Body:                                  ← header (6)
  ... (6 sub + 1 See all)
Mens:                                  ← header (7)
  Men's Haircut                        ← sub
  Beard trims and shaves               ← sub
  Men's Hair Colouring                 ← sub
  Men's Brazilian Blow Dry             ← sub
  Men's Facials                        ← sub
  Men's Waxing                         ← sub
  Barbers                              ← skip (handled by query)
```

**Hitungan:**

| Item                                        | Jumlah | Tujuan                                                            |
| ------------------------------------------- | ------ | ----------------------------------------------------------------- |
| Header grup (Hair: / Hair Removal: / dst)   | **7**  | → tabel `kategori` (7 baris)                                      |
| "See all X treatments" (di 6 grup non-Mens) | 6      | **SKIP** — link arahnya `/kategori/{slug}` (route biasa)         |
| "Barbers" (di Mens)                         | 1      | **SKIP** — link arahnya `/kategori/mens?filter=barbers` (query)   |
| Sub treatment di tiap grup                  | **42** | → tabel `sub_kategori` (42 baris, no dedup, slug pakai suffix)    |

Total entries di list = **49** = 7 header + 6 See all + 1 Barbers + 42 sub treatment ✓

User memilih **NO dedup** untuk "Men's Haircut" & "Men's Waxing":
- "Men's Haircut" muncul di Hair section (slug `mens-haircut-hair`) **dan** Mens section (slug `mens-haircut-mens`) → **2 baris terpisah** di sub_kategori
- "Men's Waxing" muncul di Hair Removal (`mens-waxing-hair-removal`) **dan** Mens (`mens-waxing-mens`) → **2 baris terpisah**
- Konsekuensi: klik di dropdown Hair vs Mens → halaman dgn URL berbeda, sumber salon bisa berbeda (kalau scraper menemukan listing yg berbeda dari `/treatment-men-s-haircut/` di waktu berbeda)

---

## 1. Skema Database Final (Part 3)

### 1.1 `kategori` (7 baris)

```sql
CREATE TABLE kategori (
  id_kategori     BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name            VARCHAR(100) NOT NULL,         -- "Hair", "Men's"
  slug            VARCHAR(120) NOT NULL UNIQUE,  -- "hair", "mens"
  deskripsi       TEXT NULL,
  icon_url        VARCHAR(255) NULL,
  treatwell_slug  VARCHAR(120) NULL,             -- utk URL Treatwell builder
  urutan          SMALLINT UNSIGNED DEFAULT 0,
  is_active       TINYINT(1) DEFAULT 1,
  created_at, updated_at,
  INDEX (is_active)
);
```

Diisi oleh [`KategoriSeeder.php`](../seeders/KategoriSeeder.php):
| ID | name         | slug          | treatwell_slug    |
| -- | ------------ | ------------- | ----------------- |
| 1  | Hair         | hair          | hair              |
| 2  | Hair Removal | hair-removal  | hair-removal      |
| 3  | Massage      | massage       | massage           |
| 4  | Nails        | nails         | nails             |
| 5  | Face         | face          | face-beauty       |
| 6  | Body         | body          | body-treatments   |
| 7  | Men's        | mens          | mens-grooming     |

### 1.2 `sub_kategori` (42 baris, no dedup)

```sql
CREATE TABLE sub_kategori (
  id_sub_kategori  BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name             VARCHAR(150) NOT NULL,
  slug             VARCHAR(180) NOT NULL UNIQUE,  -- "blow-dry", "mens-haircut-hair"
  deskripsi        TEXT NULL,
  icon_url         VARCHAR(255) NULL,
  treatwell_slug   VARCHAR(180) NULL,
  is_active        TINYINT(1) DEFAULT 1,
  created_at, updated_at,
  INDEX (is_active)
);
```

42 baris diisi [`SubKategoriSeeder.php`](../seeders/SubKategoriSeeder.php).
Lihat lampiran §10 untuk daftar lengkap.

### 1.3 Pivot `kategori_sub_kategori` (42 baris)

```sql
CREATE TABLE kategori_sub_kategori (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  id_kategori      BIGINT UNSIGNED NOT NULL  FK → kategori   ON DELETE CASCADE,
  id_sub_kategori  BIGINT UNSIGNED NOT NULL  FK → sub_kategori ON DELETE CASCADE,
  urutan           SMALLINT UNSIGNED DEFAULT 0,  -- urutan di dropdown navbar
  created_at, updated_at,
  UNIQUE (id_kategori, id_sub_kategori),
  INDEX (id_sub_kategori)
);
```

Karena tidak dedup, mapping = 1:1:
- Hair (1) → sub 1..6 (urutan 1..6)
- Hair Removal (2) → sub 7..12
- Massage (3) → sub 13..18
- Nails (4) → sub 19..24
- Face (5) → sub 25..30
- Body (6) → sub 31..36
- Men's (7) → sub 37..42

Total **42 baris pivot**. Diisi
[`KategoriSubKategoriSeeder.php`](../seeders/KategoriSubKategoriSeeder.php).

> Catatan: walau saat ini **N:1** (1 sub → 1 parent), kita tetap pakai
> tabel pivot M:N agar fleksibel kalau nanti mau menambah cross-link
> (mis. "Sugaring" terdaftar di Hair Removal *dan* Body wax).

### 1.4 Pivot `salon_kategori` — derived

```sql
CREATE TABLE salon_kategori (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  id_salon     BIGINT UNSIGNED NOT NULL  FK → salon  ON DELETE CASCADE,
  id_kategori  BIGINT UNSIGNED NOT NULL  FK → kategori  ON DELETE CASCADE,
  created_at, updated_at,
  UNIQUE (id_salon, id_kategori),
  INDEX (id_kategori)
);
```

Diisi [`SalonKategoriSeeder.php`](../seeders/SalonKategoriSeeder.php) dari
`distinct(service.id_kategori per id_salon)`.

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
- `service.id_kategori` ∈ {NULL, 1..7}
- `service.id_sub_kategori` ∈ {NULL, 1..42}

### 1.6 Diagram Final

```
┌──────────────────┐
│   kategori (7)   │
│ Hair, HR, ...    │
└─┬────────────────┘
  │ 1
  │ kategori_sub_kategori (pivot 42, urutan)
  │ N
┌─┴────────────────┐    ┌──────────────┐
│ sub_kategori     │    │   salon      │
│ (42 baris)       │    │              │
│ Pedicure,        │    └──────┬───────┘
│ Blow Dry,        │           │ N
│ Mens Haircut     │           │ salon_kategori (derived)
│ (no dedup)       │           │ N
└─┬────────────────┘    ┌──────┴───────┐
  │                     │              │
  │ 1                   ▼              │
  │ N           ┌──────────────────────────────┐
  └───────────▶ │            service           │
                │  id_salon, id_kategori (NULL),│
                │  id_sub_kategori (NULL)       │
                └──────────────────────────────┘
```

---

## 2. Cara Kerja Scraper Part 3

### 2.1 Dua mode scraping

**Mode `--sub=<slug>`** (lebih spesifik, RECOMMENDED untuk akurasi):
- Build URL: `https://www.treatwell.co.uk/places/treatment-{TreatwellSlug}/in-{kota}-uk/`
- Contoh: `treatment-blow-dry/in-london-uk/`
- Salon yg ditemukan = salon yg explicit menyediakan treatment ini
- Tag service: primary = `id_sub_kategori` dari registry, refine via matcher

**Mode `--kategori=<slug>`** (lebih luas, RECOMMENDED untuk volume):
- Build URL: `https://www.treatwell.co.uk/places/treatment-group-{TreatwellSlug}/in-{kota}-uk/`
- Contoh: `treatment-group-hair/in-london-uk/`
- Salon yg ditemukan = semua salon yg punya minimal 1 service di grup ini
- Tag service: hanya via matcher (tidak ada primary)

> **Best practice**: jalankan **mode kategori** dulu utk capture banyak salon
> (cepat), lalu **mode sub** utk fine-tune tagging service yg belum ke-tag.

### 2.2 `kategoriRegistry` & `subKategoriRegistry`

Di [`scraper.go`](scraper.go):

```go
var kategoriRegistry = []KategoriDef{
    {1, "hair",         "Hair",         "hair"},
    {2, "hair-removal", "Hair Removal", "hair-removal"},
    ...
}

var subKategoriRegistry = []SubKategoriDef{
    {1, 1, "ladies-haircuts", "Ladies' Haircuts", "ladies-haircuts-1"},
    {2, 1, "blow-dry",        "Blow Dry",         "blow-dry"},
    ...
}
```

ID **HARUS** sinkron dgn `KategoriSeeder.dataset()` & `SubKategoriSeeder.dataset()`.

### 2.3 Matcher (`matchSubKategori`)

Untuk setiap service yg ditemukan dari OfferCatalog Treatwell:

1. **Pass 1** — keyword di nama service. Iterasi sub_kategori yg punya
   `id_kategori = primary kategori`, cocokkan ke `subKategoriKeywords[id]`.
2. **Pass 2** — kalau no match: cocokkan keyword di `CategoryHint` Treatwell.
3. Kalau tetap no match → `id_sub_kategori = NULL` (service masuk DB tanpa
   sub-tagging, masih bisa diakses via kategori utama).

### 2.4 Output JSON

| File                | Field penting                                            |
| ------------------- | -------------------------------------------------------- |
| `salon.json`        | (sama seperti V1)                                        |
| `service.json`      | **`id_kategori` 1..7 + `id_sub_kategori` 1..42**         |
| `staff.json`        | (sama)                                                   |
| `salon_images.json` | (sama)                                                   |
| `kota.json`         | (sama)                                                   |

`kategori.json` & `sub_kategori.json` tidak ditulis scraper — di-seed
dari PHP karena 7 & 42 baris fixed.

Pivot `salon_kategori` di-derive otomatis oleh `SalonKategoriSeeder` dari
distinct service.id_kategori per salon.

---

## 3. Cara Pemakaian

### 3.1 Build

```powershell
cd database\scripts
go build -o scraper.exe scraper.go
# atau:
.\build.bat
```

### 3.2 Mode Sub-Kategori (paling akurat)

```powershell
# 1 sub_kategori (pakai slug VIYGO):
.\scraper.exe --sub=blow-dry
.\scraper.exe --sub=pedicure --kota=manchester --max-pages=3

# Semua 42 sub_kategori sekaligus:
.\scraper.exe --sub=all --max-pages=2
```

### 3.3 Mode Kategori-Utama (paling cepat utk volume)

```powershell
# 1 kategori utama (loop semua salon di grup):
.\scraper.exe --kategori=hair
.\scraper.exe --kategori=mens --max-pages=5

# Semua 7 kategori sekaligus:
.\scraper.exe --kategori=all --max-pages=3
```

### 3.4 Workflow penuh

```powershell
# 1. Reset JSON (opsional)
cd database\data
@('salon.json', 'service.json', 'staff.json', 'salon_images.json', 'kota.json') | ForEach-Object {
    '[]' | Out-File $_ -Encoding utf8
}

# 2. Scrape — Phase 1: kategori utama (volume)
cd ..\scripts
.\scraper.exe --kategori=all --max-pages=3

# 3. Phase 2 (optional): refine via sub_kategori
.\scraper.exe --sub=all --max-pages=2

# 4. Migrate & seed
cd ..\..
php artisan migrate:fresh --seed
```

Output `db:seed`:

```
[KategoriSeeder]               7 kategori (Hair, Hair Removal, Massage, Nails, Face, Body, Men's)
[SubKategoriSeeder]            42 sub_kategori (excluding See all + Barbers)
[KategoriSubKategoriSeeder]    42 baris pivot (urutan navbar)
[ServiceSeeder]                service per salon (id_kategori 1..7 + id_sub_kategori 1..42)
[SalonKategoriSeeder]          pivot salon_kategori derived
```

---

## 4. Verifikasi Setelah Run

```sql
-- 1. Kategori = 7
SELECT COUNT(*) FROM kategori;          -- expect: 7
SELECT id_kategori, name, slug FROM kategori ORDER BY urutan;

-- 2. Sub_kategori = 42
SELECT COUNT(*) FROM sub_kategori;      -- expect: 42

-- 3. Pivot = 42
SELECT COUNT(*) FROM kategori_sub_kategori;  -- expect: 42

-- 4. Cek navbar dropdown — 6 sub per kategori
SELECT k.name, COUNT(ksk.id_sub_kategori) AS jumlah_sub
FROM kategori k
LEFT JOIN kategori_sub_kategori ksk ON ksk.id_kategori = k.id_kategori
GROUP BY k.id_kategori, k.name
ORDER BY k.urutan;
-- Hair: 6, Hair Removal: 6, ..., Men's: 6 (semua 6, no dedup)

-- 5. "Men's Haircut" duplikasi (di slug)
SELECT id_sub_kategori, name, slug FROM sub_kategori WHERE name = "Men's Haircut";
-- Should return 2 rows: id=6 mens-haircut-hair + id=37 mens-haircut-mens

-- 6. Salon yg muncul di kategori "Pedicure"
SELECT s.nama_salon
FROM salon s
JOIN service sv ON sv.id_salon = s.id_salon
JOIN sub_kategori sk ON sk.id_sub_kategori = sv.id_sub_kategori
WHERE sk.slug = 'pedicure'
GROUP BY s.id_salon, s.nama_salon;

-- 7. Salon yg masuk grup "Mens" (filter Barbers)
SELECT DISTINCT s.nama_salon
FROM salon s
JOIN salon_kategori sk ON sk.id_salon = s.id_salon
JOIN kategori k ON k.id_kategori = sk.id_kategori
WHERE k.slug = 'mens'
  AND (s.nama_salon LIKE '%barber%'
       OR EXISTS (SELECT 1 FROM service sv WHERE sv.id_salon = s.id_salon AND sv.nama LIKE '%barber%'));
```

Smoke test UI:
- `/` → 7 kategori top nav, dropdown 6 sub per kategori
- `/kategori/hair` → "See all hair treatments" → listing salon Hair
- `/sub-kategori/blow-dry` → listing salon Blow Dry spesifik
- `/sub-kategori/mens-haircut-hair` vs `/sub-kategori/mens-haircut-mens` → halaman terpisah
- `/kategori/mens?filter=barbers` → listing salon Men's yg barber-style

---

## 5. Penanganan "See all" & "Barbers" (Tanpa Row DB)

| Link di navbar             | URL yang di-generate                  | Backend handler                                                          |
| -------------------------- | ------------------------------------- | ------------------------------------------------------------------------ |
| "See all hair treatments"  | `/kategori/hair`                      | `KategoriController::show('hair')` — tampilkan semua salon dgn id_kat=1  |
| "See all hair removal..."  | `/kategori/hair-removal`              | sama dgn slug yg sesuai                                                  |
| "See all massage..."       | `/kategori/massage`                   | -                                                                        |
| "See all nail..."          | `/kategori/nails`                     | -                                                                        |
| "See all face..."          | `/kategori/face`                      | -                                                                        |
| "See all body..."          | `/kategori/body`                      | -                                                                        |
| "Barbers" (di Mens)        | `/kategori/mens?filter=barbers`       | `KategoriController::show('mens')` + `if ($isBarbersKey) {...}` filter   |

Filter Barbers:
```php
$q->where('nama_salon', 'like', '%barber%')
  ->orWhereHas('services', fn ($s) => $s->where('nama', 'like', '%barber%'));
```

Bisa dipertajam nanti dgn tag tabel salon (mis. `salon.is_barbershop`).

---

## 6. Beda dgn Part 1 & Part 2

| Aspek               | Part 1 (cancelled)        | Part 2 (cancelled)              | **Part 3 (final)**                         |
| ------------------- | ------------------------- | ------------------------------- | ------------------------------------------ |
| `kategori` rows     | 7                         | 43 (dgn field grup)             | **7** (Hair s/d Men's)                     |
| `sub_kategori` rows | 43 (FK to kategori)       | dynamic (dari scraper)          | **42** (no dedup, slug suffix)             |
| Relasi sub→kat      | N:1                       | M:N pivot                       | **M:N pivot dgn `urutan`**                 |
| Salon ↔ kat         | salon_sub_kategori        | salon_kategori (derived)        | **salon_kategori (derived)**               |
| Navbar              | 7 grup, sub di dropdown   | 7 grup, kategori di dropdown    | **7 kategori, sub di dropdown**            |
| "See all" / Barbers | hardcode di blade         | hardcode di blade               | **handled by route + query khusus**        |
| Scraper             | per-grup (7)              | per-kategori (43)               | **--sub (42) + --kategori (7) modes**      |

---

## 7. Daftar File

### Schema (5 migration)

- [`2026_05_10_000001_restructure_kategori_table.php`](../migrations/2026_05_10_000001_restructure_kategori_table.php)
- [`2026_05_10_000002_create_sub_kategori_table.php`](../migrations/2026_05_10_000002_create_sub_kategori_table.php)
- [`2026_05_10_000003_create_kategori_sub_kategori_table.php`](../migrations/2026_05_10_000003_create_kategori_sub_kategori_table.php)
- [`2026_05_10_000004_create_salon_kategori_table.php`](../migrations/2026_05_10_000004_create_salon_kategori_table.php)
- [`2026_05_10_000005_add_id_sub_kategori_to_service.php`](../migrations/2026_05_10_000005_add_id_sub_kategori_to_service.php)

### Seeders

- [`KategoriSeeder.php`](../seeders/KategoriSeeder.php) — 7 baris
- [`SubKategoriSeeder.php`](../seeders/SubKategoriSeeder.php) — 42 baris
- [`KategoriSubKategoriSeeder.php`](../seeders/KategoriSubKategoriSeeder.php) — pivot 42 baris dgn urutan
- [`SalonKategoriSeeder.php`](../seeders/SalonKategoriSeeder.php) — derived dari service
- [`ServiceSeeder.php`](../seeders/ServiceSeeder.php) — load service.json
- [`DatabaseSeeder.php`](../seeders/DatabaseSeeder.php) — orchestrator

### Models

- [`Kategori.php`](../../app/Models/Kategori.php) — `subKategori()` belongsToMany dgn pivot urutan
- [`SubKategori.php`](../../app/Models/SubKategori.php) — `kategori()` belongsToMany
- [`Salon.php`](../../app/Models/Salon.php) — `kategoris()` belongsToMany
- [`Service.php`](../../app/Models/Service.php) — belongsTo kategori + sub_kategori

### Controller & View

- [`KategoriController.php`](../../app/Http/Controllers/KategoriController.php) — `show` (+ Barbers filter), `showSub`
- [`routes/web.php`](../../routes/web.php) — 2 route: kategori.show + sub-kategori.show
- [`resources/views/components/viygo-navbar.blade.php`](../../resources/views/components/viygo-navbar.blade.php) — render 7 kategori dari DB

### Scraper Go

- [`scraper.go`](scraper.go) — 7 + 42 registry, 2 mode (--sub, --kategori)
- `_legacy/` — V1 scraper (treatwell_scraper.go, parse_*.go) di-arsipkan

---

## 8. Troubleshooting

| Masalah                                                                 | Solusi                                                                       |
| ----------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| Build `scraper.go` error `redeclared in this block`                     | Hapus / pindahkan file `package main` lain di folder `database/scripts/` ke `_legacy/` |
| `service.id_sub_kategori` mostly NULL setelah scrape                    | Tambah keyword di `subKategoriKeywords` (scraper.go), build & run ulang      |
| Halaman `/kategori/{slug}` kosong padahal salon scraped                 | `php artisan db:seed` lagi (SalonKategoriSeeder)                             |
| `/sub-kategori/mens-haircut-hair` kosong tapi `/sub-kategori/mens-haircut-mens` ada salon | Wajar — keduanya scrape URL berbeda di Treatwell. Re-run `--sub=mens-haircut-hair` saja |
| Navbar dropdown menampilkan kategori lama                               | `php artisan cache:clear` (cache navbar 15 menit)                            |
| Mens dropdown tidak punya "See all"                                     | Sengaja — sesuai Treatwell. Pakai "Barbers" sebagai gantinya.                |

---

## 9. Verifikasi Build

Per **2026-05-10** (Part 3):

- ✅ `go build -o scraper.exe scraper.go` → BUILD OK
- ✅ Semua 15 file PHP (5 migration + 6 seeder + 4 model/controller) → syntax OK (`php -l`)
- ✅ Legacy Go files dipindahkan ke `_legacy/` (no package conflict)
- ✅ Filament resources tetap kompatibel (kategori 7 baris, dropdown otomatis sesuai)

---

## 10. Lampiran — 42 Sub-Kategori

### Hair (id_kategori=1) — sub 1..6
| ID | Slug                                | Name                                  |
| -- | ----------------------------------- | ------------------------------------- |
| 1  | ladies-haircuts                     | Ladies' Haircuts                       |
| 2  | blow-dry                            | Blow Dry                               |
| 3  | ladies-hair-colouring-highlights    | Ladies' Hair Colouring & Highlights    |
| 4  | ladies-brazilian-blow-dry           | Ladies' Brazilian Blow Dry             |
| 5  | balayage-ombre                      | Balayage & Ombre                       |
| 6  | **mens-haircut-hair**               | Men's Haircut *(slug suffix -hair)*    |

### Hair Removal (id_kategori=2) — sub 7..12
| ID | Slug                          | Name                |
| -- | ----------------------------- | ------------------- |
| 7  | facial-threading              | Facial Threading    |
| 8  | ladies-waxing                 | Ladies' Waxing      |
| 9  | sugaring                      | Sugaring            |
| 10 | hollywood-waxing              | Hollywood Waxing    |
| 11 | **mens-waxing-hair-removal**  | Men's Waxing *(slug suffix -hair-removal)* |
| 12 | ladies-leg-waxing             | Ladies' Leg Waxing  |

### Massage (id_kategori=3) — sub 13..18
| ID | Slug                  | Name                 |
| -- | --------------------- | -------------------- |
| 13 | deep-tissue-massage   | Deep Tissue Massage  |
| 14 | swedish-massage       | Swedish Massage      |
| 15 | therapeutic-massage   | Therapeutic Massage  |
| 16 | thai-massage          | Thai Massage         |
| 17 | aromatherapy-massage  | Aromatherapy Massage |
| 18 | hot-stone-massage     | Hot Stone Massage    |

### Nails (id_kategori=4) — sub 19..24
| ID | Slug                              | Name                                 |
| -- | --------------------------------- | ------------------------------------ |
| 19 | pedicure                          | Pedicure                             |
| 20 | manicure                          | Manicure                             |
| 21 | nail-gel-polish-removal           | Nail or Gel Polish Removal           |
| 22 | gel-nails-manicure                | Gel Nails Manicure                   |
| 23 | gel-nails-pedicure                | Gel Nails Pedicure                   |
| 24 | acrylic-hard-gel-nail-extensions  | Acrylic, Hard Gel & Nail Extensions  |

### Face (id_kategori=5) — sub 25..30
| ID | Slug                       | Name                          |
| -- | -------------------------- | ----------------------------- |
| 25 | classic-facials            | Classic Facials               |
| 26 | eyelash-extensions         | Eyelash Extensions            |
| 27 | eyebrow-eyelash-tinting    | Eyebrow and Eyelash Tinting   |
| 28 | eyebrow-threading          | Eyebrow Threading             |
| 29 | eyebrow-waxing             | Eyebrow Waxing                |
| 30 | definition-brows           | Definition Brows              |

### Body (id_kategori=6) — sub 31..36
| ID | Slug                              | Name                              |
| -- | --------------------------------- | --------------------------------- |
| 31 | spray-tanning-sunless-tanning     | Spray Tanning and Sunless Tanning |
| 32 | body-exfoliation-treatments       | Body Exfoliation Treatments       |
| 33 | body-wraps                        | Body Wraps                        |
| 34 | colonic-hydrotherapy              | Colonic Hydrotherapy              |
| 35 | cryolipolysis                     | Cryolipolysis                     |
| 36 | cellulite-treatments              | Cellulite Treatments              |

### Men's (id_kategori=7) — sub 37..42 (Barbers TIDAK termasuk)
| ID | Slug                          | Name                       |
| -- | ----------------------------- | -------------------------- |
| 37 | **mens-haircut-mens**         | Men's Haircut *(slug suffix -mens)* |
| 38 | beard-trims-shaves            | Beard Trims and Shaves     |
| 39 | mens-hair-colouring           | Men's Hair Colouring       |
| 40 | mens-brazilian-blow-dry       | Men's Brazilian Blow Dry   |
| 41 | mens-facials                  | Men's Facials              |
| 42 | **mens-waxing-mens**          | Men's Waxing *(slug suffix -mens)* |

**Note**: id 6 & 37 → "Men's Haircut" (no dedup); id 11 & 42 → "Men's Waxing" (no dedup).

---

## 11. Catatan Final

- **49 items** dropdown navbar = 7 header + 6 "See all" + 1 "Barbers" + 42 sub treatment
- **Tabel kategori = 7 baris** ← user-confirmed
- **Tabel sub_kategori = 42 baris** (no dedup) ← user-confirmed
- **Pivot kategori_sub_kategori = 42 baris** (1:1 mapping, M:N untuk fleksibilitas)
- **"See all" + "Barbers" = handled by query/route**, tidak masuk DB
- Scraper Go: dua mode (`--sub` lebih akurat, `--kategori` lebih cepat)
