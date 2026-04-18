# 🚀 Treatwell Scraper — Panduan Penggunaan

> **High-performance web scraper** untuk mengumpulkan data salon dari [Treatwell.co.uk](https://www.treatwell.co.uk)  
> Ditulis dalam **Go** — menggantikan versi JavaScript yang lambat.

---

## 📊 Perbandingan: JS vs Go

| Aspek | JS (Browser Console) | Go (CLI) |
|---|---|---|
| **Execution** | Sequential, 1 salon/waktu | **10 goroutine paralel** |
| **Speed per salon** | ~3 detik | **~0.3 detik** |
| **Environment** | Harus buka Chrome DevTools | **Standalone executable** |
| **Dependency** | Browser harus terbuka | **Tidak ada dependency** |
| **Error handling** | Basic try/catch | **Retry + rate limit handling** |
| **Output** | Download file manual | **Langsung merge ke database** |
| **Estimasi 500 salon** | ~25 menit | **~2-3 menit** |

---

## ⚡ Quick Start

### 1. Build

```powershell
cd database\scripts
go build -o treatwell_scraper.exe treatwell_scraper.go
```

### 2. Jalankan

```powershell
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/hair-salons-in-london/"
```

### 3. Seed ke Database

```powershell
cd ..\..
php artisan db:seed
```

---

## 📁 File yang Dihasilkan

Scraper ini **langsung merge** ke file JSON di `database/data/`:

| File | Isi |
|---|---|
| `salon.json` | Data salon (nama, alamat, rating, dll) |
| `service.json` | Layanan per salon (nama, harga, durasi) |
| `staff.json` | Staff/team member per salon |
| `salon_images.json` | URL gambar per salon |
| `kota.json` | Daftar kota (auto-detect dari data) |
| `kategori.json` | Kategori layanan (auto-detect dari nama service) |

> **PENTING:** Data baru akan **di-append** ke JSON yang sudah ada — **tidak menimpa** data lama. Duplikat otomatis di-skip berdasarkan URL.

---

## 🌍 Contoh URL Listing

```powershell
# Hair salons
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/hair-salons-in-london/"
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/hair-salons-in-manchester/"

# Beauty salons
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/beauty-salons-in-birmingham/"

# Barbershops
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/barbers-in-leeds/"

# Nail salons
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/nail-salons-in-edinburgh/"

# Massage
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/massage-in-bristol/"
```

Cara mendapatkan URL:
1. Buka [treatwell.co.uk](https://www.treatwell.co.uk)
2. Search kategori + kota
3. Copy URL dari address bar

---

## 🔧 Konfigurasi

Edit constants di bagian atas `treatwell_scraper.go`:

```go
const (
    maxPages       = 50            // Maks halaman listing yang di-scrape
    maxWorkers     = 10            // Jumlah goroutine paralel
    requestDelay   = 500ms         // Delay antar halaman listing
    maxRetries     = 3             // Retry jika gagal
    requestTimeout = 15s           // Timeout per request
)
```

> **TIP:** Jika sering kena **rate limit (429)**, turunkan `maxWorkers` ke `5` dan naikkan `requestDelay`.

Setelah edit, rebuild:
```powershell
go build -o treatwell_scraper.exe treatwell_scraper.go
```

---

## 🔄 Scrape Banyak Kota Sekaligus

Buat file `scrape_all.bat` di folder `database/scripts/`:

```batch
@echo off
echo === Starting batch scrape ===

echo [1/5] London...
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/hair-salons-in-london/"

echo [2/5] Manchester...
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/hair-salons-in-manchester/"

echo [3/5] Birmingham...
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/hair-salons-in-birmingham/"

echo [4/5] Edinburgh...
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/hair-salons-in-edinburgh/"

echo [5/5] Leeds...
.\treatwell_scraper.exe "https://www.treatwell.co.uk/places/hair-salons-in-leeds/"

echo === Done! ===
pause
```

> Setiap eksekusi akan **auto-merge** ke JSON yang sama. Duplikat otomatis di-skip.

---

## 📋 Workflow Lengkap

```
┌──────────────────────────┐
│  1. Build scraper         │  go build -o treatwell_scraper.exe treatwell_scraper.go
└──────────┬───────────────┘
           ▼
┌──────────────────────────┐
│  2. Jalankan scraper      │  .\treatwell_scraper.exe <url>
│     (bisa berulang kali)  │  → Output: database/data/*.json
└──────────┬───────────────┘
           ▼
┌──────────────────────────┐
│  3. Seed database         │  php artisan db:seed
│                           │  → JSON → MySQL
└──────────┬───────────────┘
           ▼
┌──────────────────────────┐
│  4. Selesai! ✅            │  Data siap dipakai di app
└──────────────────────────┘
```

---

## 📂 Struktur File

```
database/
├── scripts/
│   ├── treatwell_scraper.go       ← Scraper utama (Go)
│   ├── parse_face_category.go     ← Parser Excel: Face
│   ├── parse_body_category.go     ← Parser Excel: Body
│   ├── parse_nail_category.go     ← Parser Excel: Nails
│   ├── go.mod
│   └── go.sum
├── data/
│   ├── salon.json                 ← Output scraper
│   ├── service.json
│   ├── staff.json
│   ├── salon_images.json
│   ├── kota.json
│   └── kategori.json
├── migrations/                    ← Laravel migrations
└── seeders/                       ← Laravel seeders (baca JSON)
```

---

## 🏷️ Data yang Di-Scrape per Salon

| Field | Source | Deskripsi |
|---|---|---|
| `nama_salon` | JSON-LD / `<h1>` | Nama salon |
| `alamat` | JSON-LD `address` | Alamat lengkap |
| `kota` | JSON-LD `addressLocality` | Kota |
| `rating` | JSON-LD `aggregateRating` | Rating (0-5) |
| `total_review` | JSON-LD `reviewCount` | Jumlah review |
| `opening_time` | JSON-LD `openingHours` | Jam buka |
| `closing_time` | JSON-LD `openingHours` | Jam tutup |
| `deskripsi` | JSON-LD `description` | Deskripsi salon |
| `phone_number` | JSON-LD `telephone` | Nomor telepon |
| `latitude` | JSON-LD `geo` | Koordinat |
| `longitude` | JSON-LD `geo` | Koordinat |
| `services[]` | DOM elements | Nama, harga, durasi, kategori |
| `staff[]` | DOM elements | Nama team member |
| `images[]` | JSON-LD + DOM gallery | URL gambar |

---

## ⚠️ Troubleshooting

| Problem | Solusi |
|---|---|
| `HTTP 429` (rate limited) | Scraper otomatis tunggu 10 detik. Jika terus, kurangi `maxWorkers` |
| `HTTP 403` (forbidden) | Treatwell block IP. Tunggu beberapa menit |
| Tidak ada listing ditemukan | Pastikan URL valid — cek di browser dulu |
| `max retries reached` | Koneksi internet bermasalah |
| Data tidak muncul di app | Jalankan `php artisan db:seed` setelah scraping |
