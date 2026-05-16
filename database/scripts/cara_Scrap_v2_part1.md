# Cara Scraping Treatwell — VIYGO v2 Part 1 (Panduan Praktis)

> Tanggal: **2026-05-10**
> Audience: developer / operator yg mau scrape ulang dari nol
> Binary: `database/scripts/scraper.exe`

Dokumen ini adalah **PANDUAN STEP-BY-STEP** dari nol sampai data terisi
di MySQL. Untuk penjelasan teknis (apa yg di-scrape, kenapa, dst.) lihat
[`laporan_Scrapper_v2_part4.md`](laporan_Scrapper_v2_part4.md).

---

## 1. Prasyarat (cek dulu sebelum mulai)

| Tool                         | Cara cek                                           |
| ---------------------------- | -------------------------------------------------- |
| **Go ≥ 1.21**                | `go version` → harus muncul `go1.21.x` atau lebih  |
| **PHP ≥ 8.2 + Laravel CLI**  | `php --version` & `php artisan --version`          |
| **MySQL** jalan              | `mysql -uroot -p` masuk → `SHOW DATABASES;`        |
| **`.env`** sudah benar       | Cek `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`    |
| **Internet** stabil          | Treatwell di-host UK, butuh koneksi jelas          |

---

## 2. Step 1 — Reset semua data (sudah otomatis)

Ini SUDAH dilakukan otomatis oleh assistant Claude saat task ini dimulai.
Data JSON di `database/data/` semua sudah jadi `[]`:

```
salon.json          5 bytes  ([])
service.json        5 bytes  ([])
staff.json          5 bytes  ([])
salon_images.json   5 bytes  ([])
kota.json           5 bytes  ([])
kategori.json       5 bytes  ([])
```

**Kalau Anda mau reset manual lagi nanti**, jalankan PowerShell di
folder root project:

```powershell
cd database\data
@('salon.json', 'service.json', 'staff.json', 'salon_images.json', 'kota.json', 'kategori.json') | ForEach-Object {
    '[]' | Out-File $_ -Encoding utf8 -NoNewline
}
```

> **Catatan**: `kategori.json` & `sub_kategori.json` (kalau ada) tidak
> dipakai scraper — kategori 7 baris + sub_kategori 42 baris di-seed
> langsung dari PHP (`KategoriSeeder.php` & `SubKategoriSeeder.php`).
> Jadi mau reset / tidak, hasilnya sama.

---

## 3. Step 2 — Build scraper (sekali, atau habis edit)

Buka PowerShell, masuk ke `database\scripts\`:

```powershell
cd database\scripts
go build -o scraper.exe scraper.go
```

Sukses → muncul file `scraper.exe`. Kalau error `redeclared in this block`,
pastikan tidak ada file `package main` lain di folder yg sama (legacy
scraper sudah dipindah ke `_legacy/`).

Cek scraper jalan:

```powershell
.\scraper.exe
```

Akan muncul **usage screen** dgn daftar 7 kategori utama + 42 sub_kategori.

---

## 4. Step 3 — Scrape (pilih strategi)

Scraper punya **2 mode**:

### Mode A — `--kategori=<slug>` (cepat, volume banyak)

URL Treatwell yg di-build:
```
https://www.treatwell.co.uk/places/treatment-group-{TreatwellSlug}/in-{kota}-uk/
```

Ngumpulin **semua salon** yang punya treatment di grup itu. Tagging
service via keyword matcher.

```powershell
# 1 kategori utama, kota default (London/Manchester/Birmingham), 5 page/kota
.\scraper.exe --kategori=hair

# Override kota & batasi page
.\scraper.exe --kategori=nails --kota=manchester --max-pages=3

# Multi-kota
.\scraper.exe --kategori=massage --kota=london,leeds,bristol

# SEMUA 7 kategori sekaligus
.\scraper.exe --kategori=all --max-pages=3
```

### Mode B — `--sub=<slug>` (akurat, treatment-spesifik)

URL Treatwell yg di-build:
```
https://www.treatwell.co.uk/places/treatment-{TreatwellSlug}/in-{kota}-uk/
```

Ngumpulin salon yang **explicit menyediakan treatment** ini. Tagging
service ke `id_sub_kategori` jelas.

```powershell
# 1 sub_kategori spesifik
.\scraper.exe --sub=blow-dry
.\scraper.exe --sub=pedicure --kota=manchester

# SEMUA 42 sub_kategori sekaligus
.\scraper.exe --sub=all --max-pages=2
```

### Strategi yg saya rekomendasi

```powershell
# Phase 1: scrape volume cepat — 7 kategori utama (default 3 kota × 5 page)
.\scraper.exe --kategori=all --max-pages=3
# Estimasi: ~30-60 menit, hasilnya ratusan salon

# Phase 2: refine tagging — 42 sub_kategori (kota spesifik biar cepat)
.\scraper.exe --sub=all --kota=london --max-pages=2
# Estimasi: ~20-40 menit, dapat tag id_sub_kategori utk service yg miss
```

> **Auto-save**: scraper save JSON setelah setiap kategori/sub selesai.
> Kalau di-`Ctrl+C` di tengah, data sebelumnya tetap aman.
>
> **Auto-dedup**: salon yg sudah di-scrape (cek by `source_url`)
> akan di-skip — aman dijalankan berkali-kali.

---

## 5. Step 4 — Migrate (kalau migration baru belum jalan)

Kalau Anda BELUM pernah jalankan migration Part 3:

```powershell
cd ..\..    # kembali ke root project
php artisan migrate
```

Output expected:
```
2026_05_10_000001_restructure_kategori_table ........... DONE
2026_05_10_000002_create_sub_kategori_table ............ DONE
2026_05_10_000003_create_kategori_sub_kategori_table ... DONE
2026_05_10_000004_create_salon_kategori_table .......... DONE
2026_05_10_000005_add_id_sub_kategori_to_service ....... DONE
```

Kalau sudah pernah jalankan → skip. Kalau mau RESET TOTAL skema:

```powershell
php artisan migrate:fresh    # hati-hati: drop semua tabel + recreate
```

---

## 6. Step 5 — Seed (load JSON → MySQL)

```powershell
php artisan db:seed
```

Output expected (urut):
```
[TRUNCATE] Clearing existing data...
[KotaSeeder]                  N kota records
[KategoriSeeder]              7 kategori (Hair s/d Men's)
[SubKategoriSeeder]           42 sub_kategori
[KategoriSubKategoriSeeder]   42 baris pivot
[UserSeeder]                  ~N users (1 admin + 1 customer + N salon owners)
[SalonSeeder]                 N salon
[ServiceSeeder]               N service (id_kategori 1..7 + id_sub_kategori 1..42)
[SalonKategoriSeeder]         N baris pivot salon_kategori derived
[StaffSeeder]                 N staff
[SalonImagesSeeder]           N images
```

> **Catatan**: kalau `service.json` punya `id_kategori` di luar 1..7,
> seeder akan SET NULL utk row tsb (kolom sudah nullable). Cek warning
> di output: `(N service: id_kategori invalid → set NULL)`.

---

## 7. Step 6 — Verifikasi

### Via MySQL

```sql
SELECT COUNT(*) FROM kategori;            -- harus 7
SELECT COUNT(*) FROM sub_kategori;        -- harus 42
SELECT COUNT(*) FROM kategori_sub_kategori; -- harus 42
SELECT COUNT(*) FROM salon;               -- > 0 kalau scraper jalan
SELECT COUNT(*) FROM service;             -- > 0
SELECT COUNT(*) FROM salon_kategori;      -- > 0 (derived)

-- Distribusi service per kategori
SELECT k.name, COUNT(s.id_service) AS jumlah_service
FROM kategori k LEFT JOIN service s ON s.id_kategori = k.id_kategori
GROUP BY k.id_kategori ORDER BY k.urutan;

-- Salon yg muncul di kategori "Pedicure"
SELECT DISTINCT s.nama_salon
FROM salon s
JOIN service sv ON sv.id_salon = s.id_salon
JOIN sub_kategori sk ON sk.id_sub_kategori = sv.id_sub_kategori
WHERE sk.slug = 'pedicure';
```

### Via Browser

1. Jalankan dev server:
   ```powershell
   php artisan serve
   ```
2. Buka `http://localhost:8000/`
3. Cek navbar — 7 kategori utama harus muncul
4. Hover salah satu (mis. **NAILS**) — dropdown harus tampil 6 sub_kategori
5. Klik salah satu sub (mis. **Pedicure**) → ke `/sub-kategori/pedicure`
6. Klik "See all nail treatments" → ke `/kategori/nails`
7. Hover **MEN'S** → dropdown 6 sub + link "Barbers" (special filter)

---

## 8. Workflow Rekap (TL;DR)

```powershell
# 1. Build scraper (sekali)
cd database\scripts
go build -o scraper.exe scraper.go

# 2. Scrape (data → JSON)
.\scraper.exe --kategori=all --max-pages=3

# 3. Migrate (kalau belum)
cd ..\..
php artisan migrate

# 4. Seed (JSON → MySQL)
php artisan db:seed

# 5. Cek
php artisan serve
# buka http://localhost:8000/
```

---

## 9. Troubleshooting Cepat

| Error                                                  | Solusi                                                                                                          |
| ------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------- |
| `go: command not found`                                | Install Go: https://go.dev/dl/                                                                                  |
| `go build` error: `redeclared in this block`           | Pastikan tidak ada file `*.go` lain di `database/scripts/` — semua legacy ada di `_legacy/`                     |
| Scraper output: `HTTP 429` berulang                    | Treatwell rate-limit. Tunggu 5 menit, atau turunkan `maxWorkers` di `scraper.go` (default 20 → coba 10), build ulang |
| Scraper output: `No listings found`                    | TreatwellSlug salah. Buka URL manual di browser, sesuaikan di `scraper.go` → `kategoriRegistry` / `subKategoriRegistry` |
| `php artisan migrate` error: `FK constraint`           | Migration #1 sudah handle drop FK + set NULL. Kalau tetap error, jalankan `migrate:fresh` (drop semua + reset) |
| `php artisan db:seed` error: duplicate slug            | Truncate sudah dijalankan otomatis di `DatabaseSeeder.run()`. Kalau tetap, cek seeder lain belum dijalankan terpisah |
| Navbar dropdown kosong di browser                      | Cache: `php artisan cache:clear` (cache navbar 15 menit)                                                       |
| `/sub-kategori/{slug}` halaman 404                     | Slug tidak match. Cek `SELECT slug FROM sub_kategori;` & samakan                                               |
| Salon banyak tapi service `id_sub_kategori` NULL       | Mode `--kategori` tidak punya primary sub. Run `--sub=all` setelah-nya untuk refine tagging                    |

---

## 10. Estimasi Durasi & Volume

| Skenario                                               | Durasi    | Salon hasil   | Service hasil |
| ------------------------------------------------------ | --------- | ------------- | ------------- |
| `--kategori=hair --kota=london --max-pages=2`          | ~3-5 mnt  | ~30 salon     | ~600 service  |
| `--kategori=hair --max-pages=5` (3 kota default)       | ~10-15 mnt| ~250 salon    | ~5.000        |
| `--kategori=all --max-pages=3` (7 × 3 kota × 3 page)   | ~30-60 mnt| ~1.500 salon  | ~30.000       |
| `--sub=all --max-pages=2` (42 × 3 kota × 2 page)       | ~60-90 mnt| (kebanyakan dup) | ~10.000 (refine tagging) |

> Hari kerja jam kerja UK biasanya scraper lebih lambat (rate-limit naik).
> Lebih cepat malam hari (UK siang Anda).

---

## 11. Tips Operasional

1. **Mulai kecil dulu**: jangan langsung `--kategori=all`. Coba `--kategori=hair --kota=london --max-pages=2` dulu utk validasi flow.
2. **Lihat live progress**: scraper print per-salon `[N/M] ✅ Nama Salon (X services)`. Kalau banyak `❌`, ada masalah jaringan.
3. **Backup JSON sebelum re-scrape besar**: `cp database/data/*.json /tmp/backup-$(date +%Y%m%d)/`
4. **Cek `id_sub_kategori` coverage**: setelah scrape, run SQL ini —
   ```sql
   SELECT
     SUM(id_sub_kategori IS NULL) AS untagged,
     SUM(id_sub_kategori IS NOT NULL) AS tagged
   FROM service;
   ```
   Kalau untagged > 50%, run `--sub=all` untuk refine.
5. **Inspect 1 salon**: setelah scrape selesai, buka `salon.json`, ambil 1 entry, cek `source_url` → buka di browser, bandingkan dgn data yg ke-extract.

---

## 12. File Output yg Akan Terisi

Setelah scraper selesai, cek `database/data/`:

| File                | Isi                                                |
| ------------------- | -------------------------------------------------- |
| `kota.json`         | Daftar kota unik dari hasil scrape (mis. London, Manchester, ...) |
| `salon.json`        | Salon: nama, alamat, koordinat, rating, jam buka, foto utama       |
| `service.json`      | Service per salon: nama, harga, durasi, **id_kategori 1..7**, **id_sub_kategori 1..42** (atau null) |
| `staff.json`        | Staff per salon (extracted dari review/employee description JSON-LD) |
| `salon_images.json` | Foto-foto salon (URL, `is_primary` untuk foto utama, `urutan`)      |
| `kategori.json`     | (TIDAK dipakai) — akan di-overwrite tetap kosong, kategori datang dari KategoriSeeder.php |

---

## 13. Setelah Selesai — Apa yg Bisa Dilakukan

- Browse `/` → lihat homepage VIYGO dengan navbar berbasis DB
- Browse `/kategori/blow-dry` → halaman kategori
- Browse `/sub-kategori/pedicure` → halaman sub_kategori
- Login Filament admin (`/admin`) → kelola kategori, sub_kategori, salon
- Re-scrape kapan saja: `--kategori=...` atau `--sub=...` lagi (data append + dedup otomatis)

---

Untuk detail teknis (apa yg di-scrape per salon, struktur JSON-LD,
algoritma classifier), lihat **[`laporan_Scrapper_v2_part4.md`](laporan_Scrapper_v2_part4.md)**.
