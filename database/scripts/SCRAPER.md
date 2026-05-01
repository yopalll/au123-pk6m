# 🚀 Treatwell Scraper — Panduan Lengkap

> Scraper performa tinggi untuk mengumpulkan data salon dari [Treatwell.co.uk](https://www.treatwell.co.uk)  
> Ditulis dalam **Go** — 15x lebih cepat dari versi JavaScript

---

## 📁 Struktur File

```
database/
├── scripts/
│   ├── treatwell_scraper.go    ← Source code scraper
│   ├── treatwell_scraper.exe   ← Executable (di-build dari .go)
│   ├── go.mod
│   └── go.sum
├── data/                       ← Output scraper (auto-merge)
│   ├── salon.json
│   ├── service.json
│   ├── staff.json
│   ├── salon_images.json
│   ├── kota.json
│   └── kategori.json
└── seeders/                    ← Laravel seeders (baca JSON → MySQL)
```

---

## ⚙️ Setup Awal (Sekali Saja)

### Prasyarat
- **Go** versi 1.21+ → [Download](https://go.dev/dl/)
- **MySQL** sudah jalan (XAMPP/Laragon/dll)
- **Laravel** sudah dikonfigurasi (`.env` sudah benar)

### Install dependensi Go
```powershell
cd database\scripts
go mod tidy
```

### Build executable
```powershell
go build -o treatwell_scraper.exe treatwell_scraper.go
```

Atau pakai script yang sudah disediakan (double-click / jalankan di terminal):
```powershell
.\build.bat
```

> ⚠️ **PENTING — Setelah `git pull`:**  
> Kalau ada perubahan di `treatwell_scraper.go`, kamu **WAJIB build ulang** sebelum pakai!  
> Cukup jalankan `.\build.bat` atau perintah `go build` di atas.  
> Kalau tidak di-build ulang → scraper pakai versi lama → bisa error / no listings found.


---

## 🎯 Cara Mendapatkan URL

1. Buka [treatwell.co.uk](https://www.treatwell.co.uk)
2. Pilih **kategori** (Hair, Nails, Massage, dll) + **kota**
3. Copy URL dari address bar browser

### Format URL yang didukung

| Tipe | Contoh URL |
|---|---|
| **Category + Kota** (rekomen) | `https://www.treatwell.co.uk/places/treatment-group-hair/offer-type-local/in-london-uk/` |
| **Keyword + Kota** | `https://www.treatwell.co.uk/places/hair-salons-in-london/` |
| **Spesifik treatment** | `https://www.treatwell.co.uk/places/treatment-haircut/in-manchester-uk/` |

> 💡 **Tips:** URL dengan format `treatment-group-XXX` biasanya memberikan lebih banyak hasil daripada keyword biasa

---

## ▶️ Cara Menjalankan

### Sintaks dasar
```powershell
cd database\scripts
.\treatwell_scraper.exe "<URL>"
```

### Contoh lengkap per kategori

```powershell
# ── HAIR ──────────────────────────────────────────────────────
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-hair/offer-type-local/in-london-uk/"
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-hair/offer-type-local/in-manchester-uk/"
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-hair/offer-type-local/in-birmingham-uk/"

# ── NAILS ─────────────────────────────────────────────────────
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-nails/offer-type-local/in-london-uk/"
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-nails/offer-type-local/in-manchester-uk/"

# ── MASSAGE ───────────────────────────────────────────────────
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-massage/offer-type-local/in-london-uk/"

# ── FACE / BEAUTY ─────────────────────────────────────────────
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-face-beauty/offer-type-local/in-london-uk/"

# ── HAIR REMOVAL ──────────────────────────────────────────────
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-hair-removal/offer-type-local/in-london-uk/"

# ── EYEBROWS & LASHES ─────────────────────────────────────────
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-eyebrows-lashes/offer-type-local/in-london-uk/"

# ── MEN'S GROOMING ────────────────────────────────────────────
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-mens-grooming/offer-type-local/in-london-uk/"
```

---

## 🔄 Alur Kerja Lengkap

```
┌─────────────────────┐
│  1. Buka Treatwell  │  → Cari kategori + kota
│     di browser      │  → Copy URL listing
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  2. Jalankan        │  .\treatwell_scraper.exe "<URL>"
│     scraper         │
│                     │  Phase 1: Kumpulkan URL salon (listing pages)
│                     │  Phase 2: Scrape detail tiap salon (paralel)
│                     │  Phase 3: Merge ke database/data/*.json
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  3. Ulangi untuk    │  Jalankan lagi dengan URL berbeda
│     URL lain        │  → Data otomatis di-append (duplikat di-skip)
│     (opsional)      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  4. Seed database   │  php artisan db:seed
│                     │  → JSON → MySQL (auto truncate + insert)
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  5. Selesai! ✅     │  Data siap dipakai di aplikasi
└─────────────────────┘
```

---

## 📊 Output yang Dihasilkan

Setiap scrape otomatis **merge** ke file JSON berikut:

| File | Isi | Field utama |
|---|---|---|
| `salon.json` | Data salon | nama, alamat, kota, rating, jam buka |
| `service.json` | Layanan per salon | nama, harga, durasi, kategori |
| `staff.json` | Staff per salon | nama |
| `salon_images.json` | Foto per salon | url gambar |
| `kota.json` | Daftar kota unik | nama kota, provinsi |
| `kategori.json` | Kategori layanan unik | nama, slug |

> **Auto-merge:** Salon yang sudah ada (berdasarkan URL) akan **di-skip**, tidak duplikat

---

## ⚡ Performa

| Metric | Nilai |
|---|---|
| Speed per salon | ~200ms |
| Concurrency | 10 goroutine paralel |
| 1.000 salon | ~3-4 menit |
| vs JavaScript | **15x lebih cepat** |

---

## ⚙️ Konfigurasi (Opsional)

Edit konstanta di bagian atas `treatwell_scraper.go`:

```go
const (
    maxPages       = 50            // Maks halaman listing (20 salon/halaman = max 1000 salon)
    maxWorkers     = 10            // Goroutine paralel — turunkan jika kena rate limit
    requestDelay   = 500ms         // Jeda antar halaman listing
    maxRetries     = 3             // Retry jika request gagal
    requestTimeout = 15s           // Timeout per request
)
```

Setelah edit, **build ulang**:
```powershell
go build -o treatwell_scraper.exe treatwell_scraper.go
```

---

## 🗂️ Scrape Banyak Kategori Sekaligus

Buat file `scrape_all.bat` di folder `database/scripts/`:

```batch
@echo off
echo ============================================
echo   VIYGO - Full Treatwell Scrape
echo ============================================

echo.
echo [1/4] Hair - London...
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-hair/offer-type-local/in-london-uk/"

echo.
echo [2/4] Nails - London...
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-nails/offer-type-local/in-london-uk/"

echo.
echo [3/4] Massage - London...
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-massage/offer-type-local/in-london-uk/"

echo.
echo [4/4] Face - London...
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/treatment-group-face-beauty/offer-type-local/in-london-uk/"

echo.
echo ============================================
echo   Scraping selesai! Jalankan db:seed
echo ============================================
pause
```

Jalankan:
```powershell
.\scrape_all.bat
```

---

## 🌱 Seed ke Database

Setelah scraping selesai, masukkan data ke MySQL:

```powershell
# Dari root folder viygo-app
cd d:\VIYGO\viygo-app

php artisan db:seed
```

> ⚠️ **Peringatan:** `db:seed` akan **TRUNCATE** semua tabel terkait sebelum insert ulang.  
> Pastikan kamu tidak punya data custom yang ingin dipertahankan.

### Hasil seed (estimasi dari 1 run London Hair):
```
✅ kota         →    399 records
✅ kategori     →  2,130 records
✅ users        →  1,013 records  (1 admin + 1 customer + 1011 owners)
✅ salon        →  1,011 records
✅ service      → 42,961 records
✅ staff        →  1,434 records
✅ salon_images →  9,225 records
```

---

## 🔁 Workflow Reset Data

Kalau mau scrape ulang dari nol:

```powershell
# 1. Reset semua JSON ke array kosong
cd database\data
'[]' | Out-File salon.json -Encoding utf8
'[]' | Out-File service.json -Encoding utf8
'[]' | Out-File staff.json -Encoding utf8
'[]' | Out-File salon_images.json -Encoding utf8
'[]' | Out-File kota.json -Encoding utf8
'[]' | Out-File kategori.json -Encoding utf8

# 2. Scrape ulang
cd ..\scripts
.\treatwell_scraper.exe "<URL>"

# 3. Seed ulang
cd d:\VIYGO\viygo-app
php artisan db:seed
```

---

## 🔍 Membaca Output Scraper

```
╔══════════════════════════════════╗
║  🚀 Treatwell Scraper - Starting ║
╚══════════════════════════════════╝

📋 Base URL  : https://www.treatwell.co.uk/places/...
📁 Data dir  : D:\VIYGO\viygo-app\database\data
⚡ Workers   : 10 concurrent
📄 Max pages : 50

═══ Phase 1: Collecting salon listings ═══

📄 Page 1: https://...             ← URL halaman listing
   ✅ Found 20 salons (20 new, 20 total)   ← 20 salon ditemukan per halaman

═══ Phase 2: Scraping detail pages ═══

   [1/997] ✅ Salon Name (25 services)    ← [index/total] nama (jumlah service)
   [2/997] ❌ Error Salon: HTTP 429       ← Rate limited, scraper otomatis retry

═══ Phase 3: Merging into JSON database ═══

   [OK] salon.json     1011 records       ← Total record di file JSON

╔══════════════════════════════════╗
║  🎉 SCRAPING COMPLETE            ║
║  ⏱️  Duration : 3m16s            ║
║  🏪 New salons : 991             ║
║  💇 New services : 42463         ║
╚══════════════════════════════════╝

⚡ Average: 197ms per salon
```

---

## ❌ Troubleshooting

| Error | Penyebab | Solusi |
|---|---|---|
| `HTTP 429` | Rate limited oleh Treatwell | Scraper otomatis tunggu 10 detik. Jika terus, turunkan `maxWorkers` ke `5` |
| `HTTP 403` | IP di-block sementara | Tunggu 5-10 menit lalu coba lagi |
| `No listings found` | Format URL tidak dikenali | Pastikan URL valid — cek di browser dulu |
| `max retries reached` | Koneksi internet gagal | Cek koneksi, coba lagi |
| `0 services` | Salon tidak punya listing service | Normal — beberapa salon memang tidak tampilkan service |
| Seeder error: duplicate slug | Kategori duplikat di JSON | Sudah handled otomatis oleh `KategoriSeeder` |
| Seeder error: duplicate entry | Data sudah ada di DB | `db:seed` sudah auto-truncate, coba jalankan lagi |

---

## 💡 Tips

1. **Mulai dari kategori spesifik** — URL `treatment-group-XXX` lebih efisien dari keyword umum
2. **Scrape kota besar dulu** — London, Manchester, Birmingham punya paling banyak data
3. **Jangan interrupt di tengah** — Biarkan sampai `SCRAPING COMPLETE` muncul
4. **Data append otomatis** — Aman dijalankan berkali-kali, duplikat di-skip
5. **Build sekali, pakai berkali-kali** — `.exe` tidak perlu di-build ulang kecuali kode diubah
