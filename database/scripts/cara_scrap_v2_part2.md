# Cara Scraping Treatwell — VIYGO v2 Part 2 (URL-based)

> Tanggal: **2026-05-10**
> Mode baru: **URL-based** — kasih URL Treatwell langsung, scraper auto-detect kategori + kota
> Binary: `database/scripts/scraper.exe` (sumber: `scraper.go`)
> Pengganti: panduan `cara_Scrap_v2_part1.md` (yg pakai flag `--kategori=hair` dst.) sekarang **DEPRECATED**

---

## 0. Apa yg Berubah dari Part 1?

| Aspek                  | Part 1 (deprecated)                                   | **Part 2 (current)**                                          |
| ---------------------- | ----------------------------------------------------- | ------------------------------------------------------------- |
| Input                  | `--kategori=hair` / `--sub=blow-dry` / `--grup=Hair`  | **URL Treatwell langsung sebagai argumen positional**         |
| Kota                   | `--kota=london,manchester` (multi-kota loop)          | **Auto-detect dari URL** (1 kota per run)                     |
| Auto-detect kategori   | Tidak — harus kasih flag                              | **Ya** — di-extract dari segment `treatment-XXX` URL          |
| Auto-detect kota       | Tidak                                                 | **Ya** — di-extract dari segment `in-XXX-uk` URL              |
| Override manual        | (tidak applicable)                                    | `--id-kategori=N` / `--id-sub-kategori=N` / `--kota=Name`     |

Kenapa pindah ke URL-based? Lebih **flexible** — bisa scrape URL apapun dari
Treatwell (termasuk URL custom dgn filter `/offer-type-local/`, dst.) tanpa
harus modify registry di Go.

---

## 1. Quick Start (3 langkah)

### Langkah 1 — Build (sekali)

```powershell
cd database\scripts
go build -o scraper.exe scraper.go
```

### Langkah 2 — Scrape pakai URL

Buka [treatwell.co.uk](https://www.treatwell.co.uk/), pilih kategori +
kota → **copy URL dari address bar** → paste ke command:

```powershell
.\scraper.exe https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/
```

Output banner akan menunjukkan **auto-detect**:

```
╔══════════════════════════════════════════════════════════════╗
║   🚀 VIYGO Treatwell Scraper Part 4 (URL-based)             ║
╚══════════════════════════════════════════════════════════════╝

🔗 URL          : https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/
📁 Data dir     : C:\treatwell2\VIYGO\database\data
📄 Max pages    : 5

🔍 Auto-detect dari URL:
   • Treatwell slug : blow-dry  (sub_kategori)
   • Kota slug      : london → London
   • SUB_KATEGORI: Blow Dry (#2) → parent Hair (#1)

✅ Final mapping (setelah override flags, kalau ada):
   • id_kategori      : 1
   • id_sub_kategori  : 2
   • Kota (default)   : London
```

### Langkah 3 — Migrate & seed (sekali per batch scraping)

```powershell
cd ..\..
php artisan migrate     # kalau migration baru belum jalan
php artisan db:seed     # truncate + reseed dari JSON
```

---

## 2. Pola URL yg Dikenali

### Pola 1 — `treatment-group-{slug}` (kategori utama, banyak salon)

```
https://www.treatwell.co.uk/places/treatment-group-hair/in-london-uk/
https://www.treatwell.co.uk/places/treatment-group-massage/in-manchester-uk/
https://www.treatwell.co.uk/places/treatment-group-mens-grooming/in-bristol-uk/
```

Auto-detect:
- `treatment-group-hair` → kategori **#1 Hair**
- Sub_kategori: TIDAK di-set (per-service via matcher)

### Pola 2 — `treatment-{slug}` (sub_kategori spesifik)

```
https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/
https://www.treatwell.co.uk/places/treatment-pedicure/in-manchester-uk/
https://www.treatwell.co.uk/places/treatment-deep-tissue-massage/in-bristol-uk/
```

Auto-detect:
- `treatment-blow-dry` → sub_kategori **#2 Blow Dry** → parent **#1 Hair**
- Service akan di-tag id_sub_kategori = 2 (primary), id_kategori = 1

### Pola 3 — Path tambahan dibolehkan

```
https://www.treatwell.co.uk/places/treatment-group-hair/offer-type-local/in-london-uk/
```

Scraper hanya pedulikan segment `treatment-XXX` dan `in-XXX-uk`, segment
lain di-skip. Aman.

---

## 3. Daftar Treatwell Slug → VIYGO Kategori

### Kategori utama (untuk URL `treatment-group-{slug}`)

| treatwell_slug          | id_kategori | name         |
| ----------------------- | ----------- | ------------ |
| `hair`                  | 1           | Hair         |
| `hair-removal`          | 2           | Hair Removal |
| `massage`               | 3           | Massage      |
| `nails`                 | 4           | Nails        |
| `face-beauty`           | 5           | Face         |
| `body-treatments`       | 6           | Body         |
| `mens-grooming`         | 7           | Men's        |

### Sub_kategori (untuk URL `treatment-{slug}`)

Run `scraper.exe` (tanpa argumen) → daftar lengkap 42 sub_kategori dgn
treatwell-slug-nya akan tampil. Atau lihat
[`SubKategoriSeeder.php`](../seeders/SubKategoriSeeder.php) → method
`dataset()`.

Beberapa contoh:
| treatwell_slug              | id_sub | name              | parent kategori    |
| --------------------------- | ------ | ----------------- | ------------------ |
| `blow-dry`                  | 2      | Blow Dry          | Hair (1)           |
| `pedicure`                  | 19     | Pedicure          | Nails (4)          |
| `deep-tissue-massage`       | 13     | Deep Tissue       | Massage (3)        |
| `eyelash-extensions`        | 26     | Eyelash Extensions| Face (5)           |
| `beard-trimming`            | 38     | Beard Trims       | Men's (7)          |

> Catatan: "Men's Haircut" (treatwell-slug `men-s-haircut`) muncul di
> 2 sub_kategori — id #6 (Hair) dan id #37 (Men's). Auto-detect akan
> ambil yg pertama match (id #6, Hair). Pakai `--id-sub-kategori=37`
> kalau mau force ke Men's.

---

## 4. Flag Opsional

### `--max-pages=N`

Batasi halaman listing yg di-crawl. Default `5`.

```powershell
.\scraper.exe https://...in-london-uk/ --max-pages=10
.\scraper.exe https://...in-london-uk/ --max-pages=2
```

### `--id-kategori=N`

OVERRIDE auto-detect → set primary id_kategori manual (1..7).

Berguna untuk URL custom yg pattern-nya tidak dikenali, mis.
`/places/at-barbershop/in-london-uk/` → tidak ada `treatment-XXX`,
auto-detect akan WARNING. Pakai:

```powershell
.\scraper.exe https://www.treatwell.co.uk/places/at-barbershop/in-london-uk/ --id-kategori=7
```

### `--id-sub-kategori=N`

OVERRIDE → set primary id_sub_kategori manual (1..42). Berguna kalau
sub_kategori auto-detect "Men's Haircut" matched ke id #6 (Hair) tapi
Anda mau force ke #37 (Men's):

```powershell
.\scraper.exe https://...treatment-men-s-haircut/in-london-uk/ --id-sub-kategori=37
```

### `--kota=Name`

OVERRIDE nama kota manual (display + fallback default kalau salon's
addressLocality kosong):

```powershell
.\scraper.exe https://...in-newcastle-upon-tyne-uk/ --kota="Newcastle"
```

> Catatan: `--kota` hanya **default**. Kota tiap salon tetap diambil
> dari JSON-LD `addressLocality` salon detail page. Override ini cuma
> jadi fallback kalau JSON-LD-nya kosong.

---

## 5. Workflow Lengkap (Scenario)

### Scenario A — Scrape 1 sub_kategori di 1 kota

```powershell
cd database\scripts
.\scraper.exe https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/
# Hasil: ~30-50 salon Blow Dry di London
# Service auto-tagged id_sub_kategori=2, id_kategori=1
```

### Scenario B — Scrape 1 sub_kategori di multi-kota

Karena tiap run = 1 URL = 1 kota, jalankan beberapa kali:

```powershell
.\scraper.exe https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/
.\scraper.exe https://www.treatwell.co.uk/places/treatment-blow-dry/in-manchester-uk/
.\scraper.exe https://www.treatwell.co.uk/places/treatment-blow-dry/in-birmingham-uk/
# Salon yg sudah pernah di-scrape (by source_url) akan di-skip otomatis
```

### Scenario C — Scrape kategori utama (volume banyak salon)

```powershell
.\scraper.exe https://www.treatwell.co.uk/places/treatment-group-hair/in-london-uk/ --max-pages=10
# ~150 salon, ~3000 service
# Service auto-tagged id_kategori=1, id_sub_kategori via matcher
```

### Scenario D — Scrape semua kategori utama via batch script

Buat file `scrape_all.bat`:

```bat
@echo off
echo === HAIR ===
.\scraper.exe https://www.treatwell.co.uk/places/treatment-group-hair/in-london-uk/ --max-pages=3
echo === HAIR REMOVAL ===
.\scraper.exe https://www.treatwell.co.uk/places/treatment-group-hair-removal/in-london-uk/ --max-pages=3
echo === MASSAGE ===
.\scraper.exe https://www.treatwell.co.uk/places/treatment-group-massage/in-london-uk/ --max-pages=3
echo === NAILS ===
.\scraper.exe https://www.treatwell.co.uk/places/treatment-group-nails/in-london-uk/ --max-pages=3
echo === FACE ===
.\scraper.exe https://www.treatwell.co.uk/places/treatment-group-face-beauty/in-london-uk/ --max-pages=3
echo === BODY ===
.\scraper.exe https://www.treatwell.co.uk/places/treatment-group-body-treatments/in-london-uk/ --max-pages=3
echo === MEN'S ===
.\scraper.exe https://www.treatwell.co.uk/places/treatment-group-mens-grooming/in-london-uk/ --max-pages=3
echo.
echo Selesai! Jalankan: php artisan db:seed
pause
```

Jalankan: `.\scrape_all.bat`

### Scenario E — URL custom (Barbers)

```powershell
.\scraper.exe https://www.treatwell.co.uk/places/at-barbershop/in-london-uk/ --id-kategori=7
# Pattern at-XXX tidak punya treatment-XXX, jadi auto-detect kategori akan WARNING.
# Dengan --id-kategori=7, semua service ditandai id_kategori=Mens.
# Sub_kategori akan resolve via matcher (Beard Trims, Men's Haircut, dll).
```

---

## 6. Output yg Dihasilkan

Setelah run, cek `database/data/`:

| File                | Diisi      | Note                                                            |
| ------------------- | ---------- | --------------------------------------------------------------- |
| `salon.json`        | ✅          | Append + dedup by source_url                                    |
| `service.json`      | ✅          | id_kategori 1..7 + id_sub_kategori 1..42 (atau NULL)            |
| `staff.json`        | ✅          | Best-effort dari review JSON-LD                                 |
| `salon_images.json` | ✅          | Semua foto, `is_primary=true` utk first image                   |
| `kota.json`         | ✅          | Append kota baru kalau belum ada                                |
| `kategori.json`     | ❌ (kosong) | Tidak dipakai — kategori 7 baris di-seed dari KategoriSeeder.php |

---

## 7. Reset Data Sebelum Re-scrape Total

Kalau mau mulai dari nol:

```powershell
cd database\data
@('salon.json', 'service.json', 'staff.json', 'salon_images.json', 'kota.json') | ForEach-Object {
    '[]' | Out-File $_ -Encoding utf8 -NoNewline
}
cd ..\scripts
# scrape lagi dari URL apapun
.\scraper.exe https://www.treatwell.co.uk/places/treatment-group-hair/in-london-uk/
```

---

## 8. Verifikasi

```sql
-- Salon ada?
SELECT COUNT(*) FROM salon;

-- Service punya id_kategori?
SELECT id_kategori, COUNT(*) FROM service GROUP BY id_kategori;

-- Service ter-tag sub_kategori?
SELECT
  SUM(id_sub_kategori IS NULL) AS untagged,
  SUM(id_sub_kategori IS NOT NULL) AS tagged
FROM service;

-- Salon di kota tertentu?
SELECT s.nama_salon, k.nama_kota
FROM salon s JOIN kota k USING (id_kota)
WHERE k.nama_kota = 'London'
LIMIT 10;

-- Sub_kategori "Blow Dry" punya berapa salon?
SELECT DISTINCT s.nama_salon
FROM salon s
JOIN service sv USING (id_salon)
JOIN sub_kategori sk USING (id_sub_kategori)
WHERE sk.slug = 'blow-dry';
```

UI smoke test: `php artisan serve` → buka `http://localhost:8000/` →
hover **HAIR** → klik **Blow Dry** → harus tampil salon hasil scraping.

---

## 9. Troubleshooting

| Masalah                                                                  | Solusi                                                                                          |
| ------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------- |
| `❌ Parse URL gagal: URL tidak punya segment 'treatment-XXX'...`         | URL Anda tidak match pola. Pakai `--id-kategori=N` (1..7) untuk override manual                |
| `⚠️ treatment-blow-dry tidak dikenali (no match di registry)`            | Slug Treatwell tidak ada di `subKategoriRegistry`. Tambah ke `scraper.go` lalu build ulang     |
| Output: `id_kategori : NULL`                                             | Auto-detect gagal & tidak ada `--id-kategori`. Re-run dgn flag override                        |
| Service banyak tapi `id_sub_kategori=NULL`                               | Mode kategori utama (group) tidak punya primary sub. Tuning keyword di `subKategoriKeywords`   |
| `HTTP 429` berulang                                                      | Rate-limited. Tunggu 5-10 menit, atau turunkan `maxWorkers` di scraper.go (20 → 10), build     |
| `No listings found`                                                      | URL invalid. Buka manual di browser dulu, pastikan ada salon di listing                        |
| Build error: `redeclared in this block`                                  | File `*.go` lain di `database/scripts/` konflik. Pastikan legacy ada di `_legacy/`             |
| `php artisan migrate` error: `FK constraint`                             | Jalankan `php artisan migrate:fresh` (drop semua + recreate)                                    |
| Navbar dropdown tidak update                                             | Cache: `php artisan cache:clear`                                                                |

---

## 10. Tips

1. **Mulai kecil**: scrape 1 URL dulu (`--max-pages=1`) buat validasi flow & confirm auto-detect benar.
2. **Cek banner**: pastikan baris `id_kategori : N` & `id_sub_kategori : N` benar SEBELUM scraper Phase 1 mulai.
3. **Ulangi tanpa takut duplikasi**: salon yg sudah ada (by `source_url`) akan di-skip otomatis. Aman dijalankan berkali-kali.
4. **Bila lagi tuning matcher**: scrape ulang URL yg sama (after fix `subKategoriKeywords` & build) → service yg salon-nya sudah ada akan tetap di-skip karena dedup salon. Untuk re-tag, perlu reset `service.json` dulu (atau adjust scraper utk allow service-only re-scrape — feature future).
5. **URL dgn karakter khusus** (mis. `&`): wrap dgn double-quote: `.\scraper.exe "https://..."`.

---

## 11. File yg Berubah dari Part 1 → Part 2

| File                                                  | Status                                |
| ----------------------------------------------------- | ------------------------------------- |
| `database/scripts/scraper.go`                         | **REWRITTEN** — URL-based             |
| `database/scripts/scraper.exe`                        | **REBUILT**                           |
| `database/scripts/cara_Scrap_v2_part1.md`             | DEPRECATED (still kept for reference) |
| `database/scripts/cara_scrap_v2_part2.md`             | **NEW** (this file)                   |
| `database/scripts/laporan_Scrapper_v2_part4.md`       | Tetap relevan (audit & schema info)   |
| Schema (migration + seeder) Part 3                    | TIDAK BERUBAH                         |
| Model + Controller + Navbar                           | TIDAK BERUBAH                         |

---

## 12. Limitasi Mode URL-based

- **1 URL = 1 kota**: untuk multi-kota, jalankan command terpisah per kota (atau pakai batch script).
- **URL non-Treatwell**: tidak bisa — scraper hard-coded utk parse `treatwell.co.uk`.
- **Listing tanpa `in-XXX-uk` segment**: `KotaSlug` akan kosong, fallback ke salon's `addressLocality`. Pakai `--kota=NAME` untuk override.
- **Pagination tidak bisa di-skip start**: scraper selalu mulai dari page 1. Untuk continue dari page tertentu, modify `scraper.go` (atau jalankan dgn URL `/page-N/` langsung).
