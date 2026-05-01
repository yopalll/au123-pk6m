# VIYGO — Beauty & Wellness Booking Platform

> Platform pemesanan layanan salon & kecantikan berbasis web, terinspirasi dari **Treatwell.co.uk**.  
> Dibangun di atas **Laravel 13 + Livewire Flux** dengan data scraping otomatis dari Treatwell UK.

---

## 📋 Daftar Isi

- [Gambaran Proyek](#gambaran-proyek)
- [Tech Stack](#tech-stack)
- [Arsitektur Database](#arsitektur-database)
- [Struktur Folder](#struktur-folder)
- [Cara Instalasi](#cara-instalasi)
- [Menjalankan Scraper](#menjalankan-scraper)
- [Seeding Database](#seeding-database)
- [Branching Strategy](#branching-strategy)
- [Tim Pengembang](#tim-pengembang)

---

## 🎯 Gambaran Proyek

VIYGO adalah klon fungsional dari Treatwell.co.uk yang memungkinkan pengguna untuk:

- 🔍 **Mencari** salon & studio kecantikan berdasarkan lokasi, kategori, dan layanan
- 📅 **Memesan** layanan dengan sistem booking slot waktu
- ⭐ **Mengulas** layanan yang telah dinikmati
- 🏪 **Mengelola** profil salon bagi pemilik bisnis (salon owner)
- 🛡️ **Administrasi** seluruh platform via panel admin

Data katalog salon & layanan bersumber dari hasil scraping Treatwell UK (1.000+ salon, 40.000+ layanan).

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|-------|-----------|
| **Backend Framework** | Laravel 13 (PHP ^8.3) |
| **Frontend / UI** | Livewire Flux v2 + TailwindCSS v4 |
| **Auth** | Laravel Fortify (2FA support) |
| **Database** | MySQL |
| **Scraper** | Go (Golang) — high-performance concurrent scraper |
| **Package Manager** | Composer (PHP) + npm (JS) |
| **Testing** | PestPHP v4 |

---

## 🗄️ Arsitektur Database

### Entity Relationship (Ringkasan)

```
kota ──────────────────────────────────────────────────────┐
                                                            │
kategori ──────────────────────────────────────────────┐   │
                                                        │   │
users ─────────────────────────────────────────────┐   │   │
                                                   │   │   │
                                              salon ←──┘   │
                                               │  └─────────┘
                                    ┌──────────┤
                                    │          │
                              service        staff
                            (FK: salon,    (FK: salon)
                             kategori)
                                    │
                              staff_service
                            (pivot: staff ↔ service)
                                    
order ← order_detail ← service
  │
  └→ review
  └→ pembayaran
  └→ user_promo ← promo
```

### Tabel Utama

| Tabel | Deskripsi |
|-------|-----------|
| `users` | Customer, salon owner, dan admin. Role: `customer / salon_owner / admin` |
| `kota` | Master data kota (UK cities dari Treatwell) |
| `kategori` | Master kategori layanan (Hair, Face, Nails, Body, dll) |
| `salon` | Data salon: nama, alamat, koordinat GPS, jam buka, rating |
| `service` | Layanan per salon: nama, harga, durasi, kategori |
| `staff` | Karyawan tiap salon |
| `staff_service` | Pivot: staf mana yang bisa melayani layanan apa |
| `staff_schedule` | Jadwal kerja tiap staf |
| `salon_images` | Galeri foto salon |
| `promo` | Data promo/diskon |
| `order` | Transaksi pemesanan |
| `order_detail` | Detail layanan per order |
| `pembayaran` | Record pembayaran |
| `review` | Ulasan pelanggan (rating + komentar) |
| `user_promo` | Pivot pemakaian promo per user |

---

## 📁 Struktur Folder

```
VIYGO/
├── app/
│   ├── Http/
│   │   └── Controllers/         # Laravel controllers
│   ├── Livewire/
│   │   └── Actions/             # Livewire action classes
│   ├── Models/
│   │   └── User.php             # Eloquent models
│   └── Providers/               # Service providers
│
├── database/
│   ├── data/                    # JSON source data (hasil scraping)
│   │   ├── salon.json           # ~3.7 MB — 1.000+ salon
│   │   ├── service.json         # ~39 MB  — 40.000+ layanan
│   │   ├── staff.json           # ~0.9 MB — data staf
│   │   ├── salon_images.json    # ~9.7 MB — URL foto salon
│   │   ├── kategori.json        # ~1.9 MB — kategori layanan
│   │   └── kota.json            # ~0.15 MB — data kota
│   ├── migrations/              # 19 file migrasi database
│   ├── scripts/                 # PHP utility (validate_json.php, dll)
│   └── seeders/                 # 8 seeder class
│
├── resources/
│   ├── css/                     # Stylesheet sumber
│   ├── js/                      # JavaScript sumber
│   └── views/
│       ├── welcome.blade.php    # Halaman landing (in progress)
│       ├── dashboard.blade.php  # Dashboard (in progress)
│       ├── layouts/             # Layout utama
│       ├── components/          # Blade components
│       ├── pages/
│       │   ├── auth/            # Login, register, dll
│       │   └── settings/        # User settings
│       └── partials/
│
├── routes/
│   ├── web.php                  # Route web utama
│   └── settings.php             # Route settings
│
├── .env.example                 # Template konfigurasi environment
├── composer.json                # Dependensi PHP
├── package.json                 # Dependensi JavaScript
└── vite.config.js               # Konfigurasi Vite bundler
```

---

## ⚙️ Cara Instalasi

### Prasyarat

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL 8+
- Go 1.22+ *(untuk scraper)*

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/yopalll/VIYGO.git
cd VIYGO

# 2. Install dependensi PHP
composer install

# 3. Buat file .env
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi database di .env
# DB_DATABASE=viygo-go
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 5. Jalankan migrasi
php artisan migrate

# 6. Seed database
php artisan db:seed

# 7. Install dependensi JS & build assets
npm install
npm run dev
```

---

## 🤖 Menjalankan Scraper

Scraper dibangun dengan Go dan berlokasi terpisah dari project Laravel.

```bash
# Masuk ke direktori scraper
cd viygo-scraper

# Build scraper (Windows)
build.bat

# Jalankan scraper untuk kategori tertentu
./scraper.exe --category hair --pages 50

# Output: file JSON di database/data/
```

> Lihat `SCRAPER.md` untuk panduan lengkap scraper.

---

## 🌱 Seeding Database

Setelah JSON data tersedia di `database/data/`, jalankan seeder:

```bash
# Seed semua tabel (idempotent — aman dijalankan ulang)
php artisan db:seed

# Validasi JSON sebelum seeding
php database/scripts/validate_json.php
```

**Urutan Seeder:**
1. `KotaSeeder` → master kota
2. `KategoriSeeder` → master kategori (upsert by slug)
3. `UserSeeder` → akun demo (admin + salon owner)
4. `SalonSeeder` → data salon
5. `ServiceSeeder` → layanan per salon
6. `StaffSeeder` → data karyawan
7. `SalonImagesSeeder` → foto salon

---

## 🌿 Branching Strategy

| Branch | Keterangan |
|--------|-----------|
| `main` | Production-ready (stable) |
| `go-fresh` | Branch aktif pengembangan (current) |
| `branch-viter` | Eksperimen UI Vite |
| `seeder-hair` | Pengembangan seeder data hair |

---

## 👥 Tim Pengembang

Proyek ini dikembangkan sebagai proyek akademik untuk mereplikasi platform **Treatwell.co.uk**.

---

## 📄 Lisensi

MIT License — lihat file `LICENSE` untuk detail.
