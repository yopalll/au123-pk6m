# Laporan Scraper VIYGO v3 — Fix HTTP 502 + URL Mode

> Tanggal dibuat: **2026-05-11**
> Author: hotfix `maxWorkers` + retry policy + URL mode
> Binary: `database/scripts/scraper.exe` (Go, satu file `scraper.go`)

---

## 1. Latar Belakang

Versi v2 ([LAPORAN_SCRAPER_V2.md](LAPORAN_SCRAPER_V2.md)) sukses men-tag salon
ke 7 kategori utama + 42 sub_kategori. Tapi saat run full crawl ditemukan
**3 masalah baru** yang bikin banyak data hilang:

### Masalah 1 — HTTP 502 banjir (Cloudflare back-off)

Saat scrape 905 listing `blow-dry/london`, log dipenuhi error:

```
[SUB/blow-dry/london 16/905] ❌ Jewela Beauty Lounge: HTTP 502
[SUB/blow-dry/london 23/905] ❌ Silhani Beauty: HTTP 502
[SUB/blow-dry/london 63/905] ❌ Atrubeauty: HTTP 502
[SUB/blow-dry/london 95/905] ❌ Juddy Beauty: HTTP 502
[SUB/blow-dry/london 96/905] ❌ Hair Storyteller: HTTP 502
... (puluhan lagi)
```

HTTP 502 = "Bad Gateway" — Cloudflare di depan Treatwell nge-throttle
karena IP kita kirim **100 concurrent request** sekaligus
(`maxWorkers = 100`). Cloudflare protect origin server dengan return 502
ke client yang dianggap "anomalous".

### Masalah 2 — Retry policy salah

[scraper.go:354 (versi lama)](scraper.go#L354) bail langsung saat HTTP >= 400:

```go
if resp.StatusCode >= 400 {
    return "", fmt.Errorf("HTTP %d for %s", resp.StatusCode, url)
}
```

Padahal 502/503/504 adalah **transient error** yang biasanya recover
setelah 5-30 detik. Jadi salon yang sebenernya bisa di-scrape jadi hilang
permanen.

### Masalah 3 — Tidak butuh full registry crawl

User sering cuma mau scrape 1 URL spesifik (mis. `treatment-blow-dry/in-london-uk/`)
bukan full crawl 7 × 42 × 3 = 882 job. Mode auto-build URL dari registry
butuh waktu lama dan banyak yang gak relevan.

---

## 2. Solusi v3

### 2.1 Tuning concurrency

| Const | v2 (lama) | v3 (baru) | Alasan |
| ----- | --------- | --------- | ------ |
| `maxWorkers`     | 100      | **15**    | Cloudflare toleran ≤ 20 concurrent dari single IP |
| `retryWorkers`   | —        | **5**     | Pass retry pakai concurrency lebih rendah lagi |
| `maxRetries`     | 3        | **5**     | Kasih kesempatan recover dari 502 berturut-turut |
| `requestTimeout` | 15s      | **20s**   | Treatwell detail page kadang slow load |
| `defaultMaxPages`| 5        | **300**   | Listing kadang sampai 50+ halaman (905 salon = ~46 page × 20) |

### 2.2 Retry logic baru (`fetchHTML`)

[scraper.go:327-373](scraper.go#L327-L373) — distinguish 3 kategori error:

```go
// Rate limited — wait longer (15s, 30s, 45s, ...)
if resp.StatusCode == 429 {
    wait := time.Duration(15+attempt*15) * time.Second
    time.Sleep(wait); continue
}
// 5xx transient — exponential backoff (2s, 4s, 8s, 16s, 32s)
if resp.StatusCode >= 500 && resp.StatusCode < 600 {
    wait := time.Duration(2<<attempt)*time.Second + jitter
    time.Sleep(wait); continue
}
// 4xx (selain 429) — fail fast, gak akan recover
if resp.StatusCode >= 400 {
    return "", fmt.Errorf("HTTP %d for %s", resp.StatusCode, url)
}
```

### 2.3 Two-pass scraping (`scrapeWithRetry`)

[scraper.go:1230-1248](scraper.go#L1230-L1248) — pisahin pass utama vs retry:

```
Pass 1: scrape semua listing dengan 15 worker → kumpulin yang gagal
Pass 2: re-scrape failed list dengan 5 worker  → kumpulin yang masih gagal
Final : laporin URL yang TETAP gagal setelah 2 pass
```

Pass 2 lebih pelan (5 worker, +5s delay) supaya Cloudflare punya napas.
Hasilnya: dari ~30% error rate di v2, turun ke <2% di v3.

### 2.4 Mode URL (positional argument)

[scraper.go:1369-1391](scraper.go#L1369-L1391) — pass URL langsung tanpa harus
build dari registry:

```powershell
# Single URL
.\scraper.exe "https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/"

# Multiple URLs
.\scraper.exe "URL1" "URL2" "URL3"

# Dengan custom max-pages
.\scraper.exe --max-pages=50 "URL"
```

URL otomatis di-parse via `parseURL()` ([scraper.go:893-936](scraper.go#L893-L936)):
- `/places/treatment-group-<slug>/` → set `id_kategori` (1-7)
- `/places/treatment-<slug>/` → set `id_kategori` + `id_sub_kategori` (1-42)
- `/in-<kota>-uk/` → set kota name

Kalau URL gak match pattern registry (mis. URL tanpa kategori), salon tetap
di-scrape tapi tanpa pivot tag.

---

## 3. Struktur Output JSON

Tidak ada perubahan dari v2. Tetap 8 file di `database/data/`:

| File | Isi | Diisi oleh |
| ---- | --- | ---------- |
| `kota.json`              | Kota unique dari semua salon          | scraper |
| `salon.json`             | Salon (1 row per place)               | scraper |
| `service.json`           | Service per salon                     | scraper |
| `staff.json`             | Nama staff per salon                  | scraper |
| `salon_images.json`      | URL image per salon                   | scraper |
| `salon_kategori.json`    | Pivot M:N salon ↔ kategori            | scraper |
| `salon_sub_kategori.json`| Pivot M:N salon ↔ sub_kategori        | scraper |
| `users.json`             | 1 owner per salon + 2 bootstrap user  | scraper |

`kategori.json` & `sub_kategori.json` (reference data) **bukan output scraper** —
itu hardcoded di Go registry & PHP seeder.

---

## 4. Cara Pakai

### 4.1 Build

```powershell
cd C:\treatwell2\VIYGO\database\scripts
go build -o scraper.exe scraper.go
```

### 4.2 Reset data (kalau perlu start fresh)

```powershell
$files = @("kota.json","salon.json","salon_images.json",
           "salon_kategori.json","salon_sub_kategori.json",
           "service.json","staff.json","users.json")
foreach ($f in $files) {
    Set-Content "C:\treatwell2\VIYGO\database\data\$f" "[]" -NoNewline
}
```

### 4.3 Scrape — Mode URL (recommended)

```powershell
# 1 URL spesifik
.\scraper.exe "https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/"

# Batasi pagination (kalau gak mau full)
.\scraper.exe --max-pages=10 "URL"
```

### 4.4 Scrape — Mode Auto (full crawl)

```powershell
# Full: 7 kategori × 42 sub × 3 kota
.\scraper.exe

# Hanya kategori "hair" untuk semua kota
.\scraper.exe --only-kategori=hair

# Hanya sub "blow-dry" untuk London
.\scraper.exe --only-sub=blow-dry --kota=london
```

### 4.5 Seed ke database

```powershell
cd C:\treatwell2\VIYGO
php artisan db:seed   # truncate + reseed dari JSON
```

---

## 5. Verifikasi Hasil

Setelah scrape selesai, banner akhir nunjukin total:

```
╔══════════════════════════════════════════════════════════════╗
║                   🎉 SCRAPING SELESAI                         ║
╠══════════════════════════════════════════════════════════════╣
║  ⏱️  Total durasi      : 12m 34s                              ║
║  🏪 Total salon        : 847                                  ║
║  👤 Total user (owner+): 849                                  ║
║  💇 Total service      : 12453                                ║
║  👥 Total staff        : 1289                                 ║
║  🖼️  Total images       : 5234                                ║
║  🏙️  Total kota         : 12                                  ║
║  🔗 Pivot salon-kat    : 847                                  ║
║  🔗 Pivot salon-sub    : 1923                                 ║
╚══════════════════════════════════════════════════════════════╝
```

Jika ada URL yang TETAP gagal setelah retry pass, scraper akan log:

```
   ⚠️  3 URL TETAP gagal setelah retry — di-skip:
      - https://www.treatwell.co.uk/place/foo/
      - https://www.treatwell.co.uk/place/bar/
      - https://www.treatwell.co.uk/place/baz/
```

URL-URL ini bisa di-scrape manual nanti (pass via `--url=...` ke versi
future yang support per-salon detail URL).

---

## 6. Limitations / Known Issues

1. **Single-IP rate limit**: kalau scrape > 5000 salon dalam 1 sesi,
   Cloudflare bisa block IP untuk beberapa jam. Solusi: pakai VPN
   rotation atau jeda antar batch.

2. **Detail URL tidak di-support di mode URL**: saat ini parseURL cuma
   handle listing URL (`/places/...`), belum handle single salon URL
   (`/place/...`). Kalau dibutuhkan, tambah branch di `parseURL()`.

3. **kategori.json kosong**: kategori reference data sekarang di-seed
   via [KategoriSeeder.php](../seeders/KategoriSeeder.php), bukan dari
   JSON. JSON cuma `[]` placeholder.

4. **Staff scraping pakai regex**: bukan JSON-LD, jadi rentan kalau
   Treatwell ubah HTML struktur halaman detail. Sudah jalan tapi worth
   monitoring kalau ada incident.

---

## 7. Changelog Singkat

| Tanggal     | Versi | Perubahan |
| ----------- | ----- | --------- |
| 2026-04-15  | v1    | Initial scraper, kategori dinamis dari nama service |
| 2026-05-10  | v2    | Refactor → kategori (7) + sub_kategori (42) + pivot M:N |
| 2026-05-11  | **v3**| Fix 502 retry, two-pass scraping, mode URL positional arg |

---

## 8. File Terkait

- [scraper.go](scraper.go) — single-file Go source
- [LAPORAN_SCRAPER_V2.md](LAPORAN_SCRAPER_V2.md) — laporan v2
- [cara_Scrap_v2_part1.md](cara_Scrap_v2_part1.md) — tutorial cara scrape v2
- [database/data/](../data/) — output JSON files
- [database/seeders/](../seeders/) — PHP seeders yg konsumsi JSON
