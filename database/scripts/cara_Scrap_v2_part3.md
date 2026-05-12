# Cara Scraping Treatwell — VIYGO v2 Part 3 (Registry-driven, full crawl)

> Tanggal: **2026-05-10**
> Versi terbaru — pengganti Part 1 (`--kategori=hair`) & Part 2 (URL-based)
> Binary: `database/scripts/scraper.exe` (sumber: `scraper.go`)

---

## 0. Big Picture

Scraper ini **otomatis menjelajah semua link** di navbar Treatwell tanpa
perlu kasih URL satu-satu. Loop yang dijalankan:

```
   7 kategori utama (Hair, Hair Removal, Massage, Nails, Face, Body, Men's)
                    ×
  42 sub_kategori   (Blow Dry, Pedicure, Manicure, ... — tiap dropdown)
                    ×
   N kota           (default: London, Manchester, Birmingham)
   ────────────────────────────────────────────
   = (7 + 42) × 3 = 147 listing pages × salon di tiap halaman
```

Untuk **tiap salon yang ditemukan**, scraper merekam:

1. **Data salon lengkap** (dari JSON-LD `LocalBusiness`/`BeautySalon`):
   nama, alamat, koordinat, rating, jam buka, telepon, foto-foto.
2. **Service yang ditawarkan** (dari `hasOfferCatalog`): nama, harga, durasi.
3. **Staff** (best-effort dari `employeeDescription` di review JSON-LD).
4. **Tag salon ↔ kategori** (M:N): kalau salon X muncul di listing
   `/treatment-group-hair/in-london-uk/`, salon X di-tag ke kategori #1 Hair.
5. **Tag salon ↔ sub_kategori** (M:N): kalau salon X muncul di listing
   `/treatment-blow-dry/in-london-uk/`, salon X di-tag ke sub #2 Blow Dry.

Salon yang muncul di multi listing → muncul di multi pivot M:N. Mis. salon
"GHOST Hair" yang ditemukan di listing Hair, Blow Dry, dan Balayage akan
punya 3 baris `salon_sub_kategori` + 1 baris `salon_kategori` (Hair).

---

## 1. Apa Saja yang Di-Scrape?

### 1.1 Per salon — masuk `salon.json`

| Field VIYGO       | Sumber Treatwell (JSON-LD)                                       |
| ----------------- | ---------------------------------------------------------------- |
| `nama_salon`      | `name`                                                           |
| `deskripsi`       | `description`                                                    |
| `alamat`          | `address.streetAddress` + `addressLocality` + `postalCode`       |
| `phone_number`    | `telephone`                                                      |
| `latitude`        | `geo.latitude`                                                   |
| `longitude`       | `geo.longitude`                                                  |
| `rating`          | `aggregateRating.ratingValue`                                    |
| `total_review`    | `aggregateRating.reviewCount`                                    |
| `opening_time`    | `openingHoursSpecification[0].opens`                             |
| `closing_time`    | `openingHoursSpecification[0].closes`                            |
| `image_url`       | `image[0]` (foto utama)                                          |
| `source_url`      | URL detail page Treatwell (utk dedup antar run)                  |
| `id_kota`         | resolve dari `addressLocality` (auto-create kalau belum ada)     |

### 1.2 Per service — masuk `service.json`

| Field VIYGO        | Sumber Treatwell                                              |
| ------------------ | ------------------------------------------------------------- |
| `nama`             | `hasOfferCatalog.itemListElement[].itemOffered.name`          |
| `harga`            | `Offer.price` atau `AggregateOffer.lowPrice`                  |
| `durasi`           | `additionalProperty.value` (ISO 8601, mis. "PT45M" = 45 min)  |
| `id_kategori`      | dari URL listing context (mis. URL Hair → id_kategori=1)      |
| `id_sub_kategori`  | dari URL listing context (kalau URL sub) atau matcher keyword |

### 1.3 Per staff — masuk `staff.json`

| Field VIYGO   | Sumber Treatwell                                                |
| ------------- | --------------------------------------------------------------- |
| `name`        | regex `(?i)treatment by ([^"]+)` di `employeeDescription` JSON-LD |

> Best-effort — banyak salon tidak punya pattern ini, jadi staff coverage rendah.

### 1.4 Per foto — masuk `salon_images.json`

| Field VIYGO   | Sumber Treatwell |
| ------------- | ---------------- |
| `image_url`   | `image[]` array  |
| `is_primary`  | `true` utk index 0, `false` utk lainnya |
| `urutan`      | index + 1        |

### 1.5 Per kota — masuk `kota.json`

| Field VIYGO    | Sumber                                                       |
| -------------- | ------------------------------------------------------------ |
| `nama_kota`    | `addressLocality` (auto-create kalau belum ada di kota.json) |
| `provinsi`     | `addressRegion` (mis. "England", "Scotland")                 |

### 1.6 Pivot — masuk `salon_kategori.json` + `salon_sub_kategori.json`

🆕 Baru di Part 3.

| File                       | Format                                                        |
| -------------------------- | ------------------------------------------------------------- |
| `salon_kategori.json`      | `[{"id_salon": 12, "id_kategori": 1}, ...]`                   |
| `salon_sub_kategori.json`  | `[{"id_salon": 12, "id_sub_kategori": 2}, ...]`               |

Pivot ini diisi **langsung saat scraping** berdasarkan URL context:
- Salon di listing `treatment-group-hair/...` → pivot `(salonID, 1)` di `salon_kategori`
- Salon di listing `treatment-blow-dry/...` → pivot `(salonID, 2)` di `salon_sub_kategori`

Lebih akurat dari "derive via service" karena tidak bergantung pada
keyword matcher.

### 1.7 User (owner) — masuk `users.json`

🆕 Baru di Part 3 (update terakhir).

Setiap **salon baru** yang ditemukan scraper otomatis dapat **1 owner user**:

| Field user        | Nilai                                                            |
| ----------------- | ---------------------------------------------------------------- |
| `id_user`         | Auto-increment (admin=1, customer=2, owner mulai dari 3)         |
| `first_name`      | "Owner"                                                          |
| `last_name`       | Nama salon (truncate ke 100 char)                                |
| `email`           | `owner_{id_salon}@viygo.com`                                     |
| `password`        | `"password"` — **PLAIN TEXT di JSON**, di-HASH bcrypt saat seed  |
| `role`            | `"salon_owner"`                                                  |
| `id_salon`        | Link balik ke salon yg di-own (metadata, bukan kolom DB users)   |

`salon.id_user` di-set ke `users.id_user` owner-nya, jadi relasi 1:1.

Plus 2 user bootstrap yg auto-generated saat scrape pertama (kalau
`users.json` masih `[]`):
- `id_user=1` admin@viygo.com (role=admin, password=`password`)
- `id_user=2` customer@viygo.com (role=customer, password=`password`)

**Catatan password**:
- Di `users.json`: **plain text** (`"password": "password"`) — untuk debugging/visibility
- Di MySQL `users.password`: **bcrypt hash** — Laravel auth `Hash::check()` jalan
- Mapping plain → hash terjadi di [`UserSeeder.php`](../seeders/UserSeeder.php)
- Hash di-cache per-password-unik supaya bcrypt cuma jalan sekali (mahal!)

---

## 2. Cara Kerja (3 Phase per URL)

### Phase 1 — Listing collection

```
URL: https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/
→ fetch HTML → extract <script type="application/ld+json">
→ parse @type="ItemList" → list URL salon
→ extract pagination links → push ke queue
→ loop sampai max-pages tercapai
```

Output: list URL salon (mis. `https://www.treatwell.co.uk/place/ghost-purley/`).

### Phase 2 — Detail scraping (paralel, 20 worker)

Untuk tiap URL salon:
```
fetch HTML → parse JSON-LD @type="LocalBusiness"/"BeautySalon"
→ extract: name, address, geo, rating, hours, image, hasOfferCatalog
→ recursive parse hasOfferCatalog → list service
→ regex employeeDescription → list staff (best-effort)
```

### Phase 3 — Merge ke akumulator

```
for setiap salon hasil Phase 2:
  if source_url sudah ada di salonByURL:
    re-use existing salonID
    skip insert salon/service/staff/image (sudah dari run sebelumnya)
  else:
    assign new salonID
    insert salon, service, staff, image
  
  # Selalu tag pivot (salon baru maupun existing)
  pivot salon_kategori     ← (salonID, primaryKategori)
  pivot salon_sub_kategori ← (salonID, primarySub)  [kalau URL = sub]
```

**Auto-save**: setelah tiap job selesai, semua JSON di-save. Aman di-Ctrl+C.

---

## 3. Cara Pakai

### 3.1 Build (sekali)

```powershell
cd database\scripts
go build -o scraper.exe scraper.go
```

### 3.2 Full crawl (default)

```powershell
.\scraper.exe
```

Otomatis loop **(7 + 42) × 3 = 147 jobs** (kategori utama + sub × London/Manchester/Birmingham).

Estimasi durasi: **2-4 jam** (tergantung rate-limit Treatwell). Disarankan
malam hari atau weekend.

### 3.3 Filter via flag

```powershell
# Hanya 1 kota
.\scraper.exe --kota=london

# Multi-kota custom
.\scraper.exe --kota=london,leeds,bristol

# Skip 7 kategori utama (cuma 42 sub × kota)
.\scraper.exe --skip-kategori

# Skip 42 sub_kategori (cuma 7 kategori × kota)
.\scraper.exe --skip-sub

# Hanya 1 kategori utama (+ semua sub-nya yg juga jalan kalau --skip-sub tidak set)
.\scraper.exe --only-kategori=hair --skip-sub

# Hanya 1 sub_kategori
.\scraper.exe --only-sub=blow-dry --skip-kategori

# Batasi pagination
.\scraper.exe --max-pages=2
```

### 3.4 Workflow rekomendasi (bertahap)

```powershell
# Step 1: Coba 1 sub_kategori dulu utk validasi (~3 menit)
.\scraper.exe --only-sub=blow-dry --skip-kategori --kota=london --max-pages=2

# Step 2: Test 1 kategori utama (~5-10 menit)
.\scraper.exe --only-kategori=nails --skip-sub --kota=london --max-pages=3

# Step 3: Full crawl (semua kategori + sub × 3 kota, ~2-4 jam)
.\scraper.exe --max-pages=3
```

---

## 4. Output JSON yg Dihasilkan

Setelah scraper jalan, cek `database/data/`:

| File                          | Status      | Fungsi                                                          |
| ----------------------------- | ----------- | --------------------------------------------------------------- |
| `kota.json`                   | ✅ diisi    | Daftar kota unique (auto-extend tiap salon baru di kota baru)  |
| `users.json`                  | 🆕 diisi    | Admin + Customer + 1 owner per salon (password plain text)     |
| `salon.json`                  | ✅ diisi    | Salon detail (dedup by source_url), `id_user` link ke owner    |
| `service.json`                | ✅ diisi    | Service per salon, ber-id_kategori 1..7 + id_sub_kategori 1..42 |
| `staff.json`                  | ✅ diisi    | Staff per salon (best-effort)                                  |
| `salon_images.json`           | ✅ diisi    | Foto-foto per salon                                            |
| `salon_kategori.json`         | 🆕 diisi   | **Pivot M:N salon ↔ kategori** (URL context)                  |
| `salon_sub_kategori.json`     | 🆕 diisi   | **Pivot M:N salon ↔ sub_kategori** (URL context)              |
| `kategori.json`               | ❌ kosong  | Tidak dipakai — kategori 7 baris di-seed dari `KategoriSeeder` |

---

## 5. Migrate & Seed

```powershell
cd ..\..   # kembali ke root project

# Pertama kali (atau setelah perubahan migration):
php artisan migrate         # atau migrate:fresh kalau mau reset total

# Setiap habis scraping:
php artisan db:seed         # truncate semua + reseed dari JSON
```

Output `db:seed`:
```
[KotaSeeder]                  N kota dari kota.json
[KategoriSeeder]              7 kategori utama
[SubKategoriSeeder]           42 sub_kategori
[KategoriSubKategoriSeeder]   42 baris pivot (urutan navbar)
[UserSeeder]                  ~N users
[SalonSeeder]                 N salon dari salon.json
[ServiceSeeder]               N service ber-id_kategori 1..7 + id_sub_kategori 1..42
[SalonKategoriSeeder]         M baris pivot dari salon_kategori.json
[SalonSubKategoriSeeder]      P baris pivot dari salon_sub_kategori.json
[StaffSeeder]                 N staff
[SalonImagesSeeder]           N images
```

---

## 6. Verifikasi

### 6.1 Database (SQL)

```sql
-- Skema fixed (di-seed dari PHP)
SELECT COUNT(*) FROM kategori;            -- 7
SELECT COUNT(*) FROM sub_kategori;        -- 42
SELECT COUNT(*) FROM kategori_sub_kategori; -- 42

-- Data salon (dari scraper)
SELECT COUNT(*) FROM salon;
SELECT COUNT(*) FROM service;

-- Pivot M:N (dari scraper)
SELECT COUNT(*) FROM salon_kategori;
SELECT COUNT(*) FROM salon_sub_kategori;

-- Salon X punya kategori apa saja?
SELECT k.name FROM kategori k
JOIN salon_kategori sk ON sk.id_kategori = k.id_kategori
WHERE sk.id_salon = 1;

-- Salon X punya sub_kategori apa saja?
SELECT sk.name FROM sub_kategori sk
JOIN salon_sub_kategori ssk ON ssk.id_sub_kategori = sk.id_sub_kategori
WHERE ssk.id_salon = 1;

-- Sub_kategori "Blow Dry" tersedia di salon mana saja?
SELECT s.nama_salon FROM salon s
JOIN salon_sub_kategori ssk USING (id_salon)
JOIN sub_kategori sk USING (id_sub_kategori)
WHERE sk.slug = 'blow-dry';
```

### 6.2 UI

```powershell
php artisan serve
# buka http://localhost:8000/
```

- Hover **HAIR** di navbar → 6 sub muncul
- Klik **Blow Dry** → `/sub-kategori/blow-dry` → list salon (dari pivot `salon_sub_kategori`)
- Klik **See all hair treatments** → `/kategori/hair` → list salon (dari pivot `salon_kategori`)
- Klik salah satu salon → detail page dengan service list

---

## 7. Tips & Limitasi

### Tips

1. **Mulai kecil** — `--only-sub=blow-dry --kota=london --max-pages=2` (3 menit) buat validasi flow.
2. **Auto-resume** — kalau Ctrl+C di tengah, data sudah ke-save per job. Re-run akan **skip duplikat salon** otomatis (by source_url).
3. **Pivot tetap di-update** untuk salon existing — kalau salon X sudah ke-scrape via Hair listing, lalu di-scrape ulang via Blow Dry listing, pivot `salon_sub_kategori` (X, 2) tetap di-tambah.
4. **Service hanya di-insert utk salon BARU** — kalau salon sudah ada, service-nya tidak di-double-insert.
5. **Cek `id_sub_kategori` coverage** setelah scrape:
   ```sql
   SELECT
     SUM(id_sub_kategori IS NULL) AS untagged,
     SUM(id_sub_kategori IS NOT NULL) AS tagged
   FROM service;
   ```

### Limitasi

- **Rate limit Treatwell** — kalau dapat HTTP 429 berulang, turunkan `maxWorkers` di `scraper.go` (default 20 → coba 10), build ulang.
- **`employeeDescription` regex fragile** — staff coverage rendah, hanya kalau salon punya review terstruktur dgn pattern "treatment by X".
- **1 jam buka per salon** — `openingHoursSpecification[0]` saja yg disimpan; salon dgn jam beda per hari hilang detail-nya.
- **Slow** — full crawl (147 jobs × ~30s/job + detail) bisa 2-4 jam. Jalankan malam atau pakai filter.
- **`Barbers` tidak ke-loop** — bukan sub_kategori, di-handle di view sebagai filter `?filter=barbers` di `/kategori/mens`.

---

## 8. Troubleshooting

| Masalah                                                              | Solusi                                                                                          |
| -------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| Build error: `redeclared in this block`                              | Pastikan `database/scripts/` cuma punya `scraper.go`. Legacy ada di `_legacy/`                  |
| `HTTP 429` berulang                                                  | Turunkan `maxWorkers` (20 → 10) di scraper.go, build ulang                                      |
| Job kategori `face` tidak ada listing                                | Cek TreatwellSlug di `kategoriRegistry`. `face` pakai slug `face-beauty` di Treatwell           |
| Output `+0 salon baru, +N pivot kategori`                            | Salon sudah ada dari run sebelumnya — pivot tetap di-update. Normal                             |
| `php artisan db:seed` error: FK constraint                           | `php artisan migrate:fresh --seed` (drop & recreate semua)                                      |
| Pivot `salon_kategori` jumlahnya kecil padahal salon banyak          | `--skip-kategori` dipakai. Re-run tanpa `--skip-kategori` agar URL kategori utama juga di-loop |
| Pivot `salon_sub_kategori` jumlahnya kecil padahal salon banyak      | `--skip-sub` dipakai. Re-run tanpa `--skip-sub` agar URL sub_kategori juga di-loop             |

---

## 9. File Output Sample

### `salon_kategori.json`
```json
[
  {"id_salon": 1, "id_kategori": 1},
  {"id_salon": 1, "id_kategori": 7},
  {"id_salon": 2, "id_kategori": 4},
  {"id_salon": 3, "id_kategori": 1},
  {"id_salon": 3, "id_kategori": 5}
]
```
Salon #1 menyediakan Hair + Men's. Salon #3 menyediakan Hair + Face.

### `salon_sub_kategori.json`
```json
[
  {"id_salon": 1, "id_sub_kategori": 2},
  {"id_salon": 1, "id_sub_kategori": 5},
  {"id_salon": 1, "id_sub_kategori": 38},
  {"id_salon": 2, "id_sub_kategori": 19},
  {"id_salon": 2, "id_sub_kategori": 22}
]
```
Salon #1 ditemukan di listing Blow Dry (#2), Balayage (#5), dan Beard Trims (#38).

---

## 10. Beda dgn Part 1 & Part 2

| Aspek                           | Part 1 (`--kategori=hair`) | Part 2 (URL-based)             | **Part 3 (registry-driven)** |
| ------------------------------- | -------------------------- | ------------------------------ | ---------------------------- |
| Input                           | flag kategori/sub          | URL Treatwell positional       | **TIDAK perlu input** — auto |
| Multi-URL/kota dalam 1 run      | Yes (loop kota)            | No (1 URL = 1 run)             | **Yes (full loop)**          |
| Pivot salon_kategori            | derived dari service       | derived dari service           | **explicit dari URL context**|
| Pivot salon_sub_kategori        | tidak ada                  | tidak ada                      | **explicit dari URL context**|
| Auto-tag salon ke kategori      | only via service id        | only via service id            | **+pivot direct**            |
| Filter --only-X / --skip-X      | tidak ada                  | tidak ada                      | **ada**                      |

---

## 11. File-file yg Berubah

- [`database/migrations/2026_05_10_000005_create_salon_sub_kategori_table.php`](../migrations/2026_05_10_000005_create_salon_sub_kategori_table.php) — **NEW** pivot M:N
- [`app/Models/Salon.php`](../../app/Models/Salon.php) — tambah relasi `subKategoris()` belongsToMany
- [`app/Models/SubKategori.php`](../../app/Models/SubKategori.php) — tambah relasi `salons()` belongsToMany
- [`database/scripts/scraper.go`](scraper.go) — **REWRITTEN** registry-driven
- [`database/seeders/SalonKategoriSeeder.php`](../seeders/SalonKategoriSeeder.php) — load dari JSON (fallback derive)
- [`database/seeders/SalonSubKategoriSeeder.php`](../seeders/SalonSubKategoriSeeder.php) — **NEW**
- [`database/seeders/DatabaseSeeder.php`](../seeders/DatabaseSeeder.php) — tambah seeder baru di chain
- `database/data/salon_kategori.json` — **NEW** output scraper
- `database/data/salon_sub_kategori.json` — **NEW** output scraper

---

## 12. Quick Reference

```powershell
# Setup
cd database\scripts
go build -o scraper.exe scraper.go

# Test 1 sub kategori (3 menit)
.\scraper.exe --only-sub=blow-dry --skip-kategori --kota=london --max-pages=2

# Full crawl (2-4 jam)
.\scraper.exe --max-pages=3

# Migrate & seed
cd ..\..
php artisan migrate:fresh --seed
```
