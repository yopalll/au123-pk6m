# 📋 Panduan Scraping & Seeding Data VIYGO

> Dokumen ini menjelaskan **apa saja yang sudah dilakukan**, **bagaimana sistem ini bekerja**, dan **cara menggunakannya kembali** untuk menambah data baru.

---

## Daftar Isi

1. [Ringkasan Apa yang Sudah Dilakukan](#1-ringkasan-apa-yang-sudah-dilakukan)
2. [Struktur File](#2-struktur-file)
3. [Penjelasan Tiap File](#3-penjelasan-tiap-file)
4. [Cara Menggunakan Data yang Sudah Ada](#4-cara-menggunakan-data-yang-sudah-ada)
5. [Cara Scrape Data Baru](#5-cara-scrape-data-baru)
6. [Cara Memasukkan Data Baru ke Database](#6-cara-memasukkan-data-baru-ke-database)
7. [Koreksi Scraping Chrome Extension](#7-koreksi-scraping-chrome-extension)
8. [Login Akun Testing](#8-login-akun-testing)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Ringkasan Apa yang Sudah Dilakukan

### Masalah Awal
Kalian scrape data dari Treatwell.co.uk menggunakan Chrome Extension (Web Scraper), hasilnya file Excel dengan **105 kolom yang tidak terstruktur** — kolom bernama `data`, `data2`, `data3`... sampai `data35`, data tercampur dalam satu sel, dan banyak kolom yang isinya salah.

### Yang Sudah Dikerjakan

| No | Yang Dikerjakan | Hasil |
|----|----------------|-------|
| 1 | **Analisis Excel** — Memetakan 105 kolom ke field yang benar | Identifikasi 15+ kolom penting dari 105 kolom |
| 2 | **Python Parser (Excel)** — Script untuk membersihkan data Excel | `parse_treatwell_excel.py` |
| 3 | **JavaScript Scraper** — Script scraping yang lebih baik | `treatwell_scraper.js` |
| 4 | **Python Parser (JSON)** — Parser untuk hasil scraping baru | `parse_treatwell_json.py` |
| 5 | **6 File JSON** — Data bersih siap masuk database | `database/data/*.json` |
| 6 | **8 Laravel Seeders** — File seeder untuk semua tabel | `database/seeders/*.php` |
| 7 | **Testing** — Semua data berhasil masuk database | ✅ 3,200+ records |

### Data yang Sudah Masuk Database

| Tabel | Jumlah Record | Keterangan |
|-------|:------------:|------------|
| `kota` | 87 | Kota-kota di UK (area Birmingham & sekitar) |
| `kategori` | 10 | Hair, Face, Nails, Massage, Body, dll |
| `users` | 182 | 1 admin + 1 customer + 180 salon owners |
| `salon` | 180 | Salon dari Treatwell (nama, alamat, rating, dll) |
| `service` | 1.962 | Layanan tiap salon (nama, harga, durasi) |
| `staff` | 254 | Staff/karyawan tiap salon |
| `salon_images` | 529 | Foto-foto salon (URL CDN Treatwell) |
| **TOTAL** | **3.204** | |

---

## 2. Struktur File

```
database/
│
├── 📁 data/                              ← Data bersih (JSON)
│   ├── kota.json                         (87 records)
│   ├── kategori.json                     (10 records)
│   ├── salon.json                        (180 records)
│   ├── service.json                      (1.962 records)
│   ├── staff.json                        (254 records)
│   └── salon_images.json                 (529 records)
│
├── 📁 scripts/                           ← Tools scraping & parsing
│   ├── treatwell_scraper.js              ← Script scraping (Chrome Console)
│   ├── parse_treatwell_excel.py          ← Parser Excel → JSON
│   └── parse_treatwell_json.py           ← Parser JSON scraper → JSON database
│
├── 📁 seeders/                           ← Laravel Seeders
│   ├── DatabaseSeeder.php                ← Master (menjalankan semua)
│   ├── KotaSeeder.php
│   ├── KategoriSeeder.php
│   ├── UserSeeder.php
│   ├── SalonSeeder.php
│   ├── ServiceSeeder.php
│   ├── StaffSeeder.php
│   └── SalonImagesSeeder.php
│
└── treatwell-co-uk-2026-04-14.xlsx       ← Data Excel asli (sudah di-parse)
```

---

## 3. Penjelasan Tiap File

### 🔵 Scripts (Tools)

#### `database/scripts/treatwell_scraper.js`
**Apa ini?** Script JavaScript untuk scraping data dari website Treatwell.co.uk.

**Bagaimana cara kerjanya?**
1. Kamu paste script ini di Chrome Console (F12 → Console)
2. Script membaca semua salon di halaman listing
3. Untuk tiap salon, script membuka halaman detailnya
4. Di halaman detail, script mengambil **JSON-LD** (structured data yang sudah rapi)
5. Hasilnya di-download otomatis sebagai file `.json`

**Kenapa lebih baik dari Chrome Extension?**
- Data sudah **terstruktur** karena ambil dari JSON-LD (data SEO yang pasti ada)
- Tidak ada kolom campur aduk seperti `data`, `data2`, `data3`
- Otomatis navigate ke halaman detail tiap salon

---

#### `database/scripts/parse_treatwell_excel.py`
**Apa ini?** Parser Python yang membersihkan data dari Excel hasil Chrome Extension.

**Kapan dipakai?** Hanya jika kalian scrape pakai Chrome Extension dan hasilnya Excel.

**Apa yang dilakukan?**
- Membaca 105 kolom Excel dan memetakan ke field yang benar
- Mem-parse data tercampur (contoh: kolom `Discount_Information` berisi nama service + harga + durasi dalam 1 sel)
- Mengekstrak nama staff dari teks yang disambung tanpa spasi
- Output: 6 file JSON di `database/data/`

**Cara pakai:**
```powershell
# Pastikan openpyxl sudah terinstall
pip install openpyxl

# Letakkan file Excel di database/
# Jalankan:
python database/scripts/parse_treatwell_excel.py
```

---

#### `database/scripts/parse_treatwell_json.py`
**Apa ini?** Parser Python untuk mengkonversi hasil `treatwell_scraper.js` ke format database.

**Kapan dipakai?** Setelah scraping baru menggunakan `treatwell_scraper.js`.

**Cara pakai:**
```powershell
# Letakkan file JSON hasil scraping di database/data/
python database/scripts/parse_treatwell_json.py database/data/treatwell_scrape_2026-04-17.json
```

---

### 🟢 Seeders (Laravel)

#### `database/seeders/DatabaseSeeder.php`
**Master seeder** — menjalankan semua seeder dalam urutan yang benar sesuai foreign key:

```
1. KotaSeeder       ← Tidak ada FK
2. KategoriSeeder   ← Tidak ada FK
3. UserSeeder       ← Tidak ada FK
4. SalonSeeder      ← FK ke users + kota
5. ServiceSeeder    ← FK ke salon + kategori
6. StaffSeeder      ← FK ke salon
7. SalonImagesSeeder ← FK ke salon
```

> ⚠️ **PENTING:** Urutan ini TIDAK BOLEH diubah! Kalau diubah, akan error karena foreign key constraint.

#### Seeder Lainnya
Setiap seeder:
1. Membaca file JSON dari `database/data/`
2. Menggunakan **chunked insert** (100 records per batch) untuk efisiensi
3. Menampilkan jumlah record yang berhasil di-seed

---

### 🟡 Data Files (JSON)

Semua file JSON di `database/data/` sudah siap pakai. Formatnya:

**kota.json:**
```json
{
  "id_kota": 1,
  "nama_kota": "Birmingham",
  "provinsi": "West Midlands"
}
```

**salon.json:**
```json
{
  "id_salon": 1,
  "id_user": 1,
  "id_kota": 48,
  "nama_salon": "Bamboo Hair & Aesthetic",
  "alamat": "191 Church Road, Yardley, Birmingham, B25 8UR",
  "deskripsi": "Welcome to Bamboo Hair & Aesthetic...",
  "opening_time": "10:00",
  "closing_time": "17:30",
  "rating": 4.4,
  "total_review": 103,
  "status": "active"
}
```

**service.json:**
```json
{
  "id_service": 1,
  "id_salon": 1,
  "id_kategori": 5,
  "nama": "Children - Wash, Haircut & Blow Dry",
  "durasi": 45,
  "harga": 25.0,
  "status": "active"
}
```

---

## 4. Cara Menggunakan Data yang Sudah Ada

### Seed Database (Data Sudah Ada)

Data sudah di-parse dan tersimpan di `database/data/*.json`. Tinggal jalankan:

```powershell
cd d:\VIYGO\viygo-app

# Opsi 1: Reset database + isi data (MENGHAPUS SEMUA DATA LAMA!)
php artisan migrate:fresh --seed

# Opsi 2: Seed saja tanpa reset migration
php artisan db:seed

# Opsi 3: Seed tabel tertentu saja
php artisan db:seed --class=SalonSeeder
php artisan db:seed --class=ServiceSeeder
```

> ⚠️ `migrate:fresh` akan **MENGHAPUS SEMUA TABEL** dan membuatnya ulang. Gunakan hanya saat development!

### Waktu Eksekusi
- Migration: ~2 detik
- Seeding: ~35 detik (sebagian besar untuk hashing 180 password user)
- **Total: ~37 detik**

---

## 5. Cara Scrape Data Baru

### Step-by-Step

#### Langkah 1: Buka Halaman Listing Treatwell

Buka Chrome, navigasi ke URL listing. Contoh URL per kota:

```
# HAIR
https://www.treatwell.co.uk/places/treatment-group-hair/in-london/
https://www.treatwell.co.uk/places/treatment-group-hair/in-manchester/
https://www.treatwell.co.uk/places/treatment-group-hair/in-leeds/
https://www.treatwell.co.uk/places/treatment-group-hair/in-edinburgh/

# NAILS
https://www.treatwell.co.uk/places/treatment-group-nails/in-london/
https://www.treatwell.co.uk/places/treatment-group-nails/in-manchester/

# MASSAGE
https://www.treatwell.co.uk/places/treatment-group-massage/in-london/

# FACE / BEAUTY
https://www.treatwell.co.uk/places/treatment-group-face/in-london/
```

#### Langkah 2: Buka Console

Tekan **F12** → klik tab **Console**

#### Langkah 3: Paste Script

1. Buka file `database/scripts/treatwell_scraper.js`
2. Copy **SEMUA** isi file
3. Paste di Console
4. Tekan **Enter**

#### Langkah 4: Tunggu

- Script akan berjalan otomatis
- Kamu bisa lihat progress di Console:
  ```
  🚀 Starting Treatwell Scraper...
  📄 Page 1: https://www.treatwell.co.uk/places/...
     Found 20 salons
     [1/20] Scraping: Bamboo Hair & Aesthetic
     [2/20] Scraping: Polish & Glow Shirley
     ...
  ✅ Page 1 done. Total salons collected: 20
  ```
- **Jangan tutup tab!** Biarkan sampai selesai.

#### Langkah 5: Download Otomatis

Setelah selesai, file JSON akan otomatis ter-download dengan nama seperti:
```
treatwell_scrape_2026-04-17.json
```

#### Langkah 6: Konfigurasi (Opsional)

Di bagian atas script, kamu bisa ubah setting:

```javascript
const MAX_PAGES = 50;          // Jumlah halaman maksimum (1 halaman ≈ 20 salon)
const DELAY_MS = 2000;         // Delay antar halaman (ms)
const DETAIL_DELAY_MS = 3000;  // Delay antar detail page (ms)
```

> 💡 **Tips:** Untuk coba-coba, set `MAX_PAGES = 2` dulu (≈ 40 salon). Kalau sudah berhasil, naikkan ke 50.

### Tips Anti-Block

| ❌ Jangan | ✅ Lakukan |
|-----------|-----------|
| Scrape 500 salon sekaligus | Scrape 50-100 per sesi, jeda 30 menit |
| Delay 0-1 detik | Delay minimal 3 detik |
| Scrape dari 1 IP terus menerus | Ganti VPN/koneksi jika di-rate-limit |
| Scrape halaman yang sama berulang | Simpan hasil, jangan scrape ulang |

---

## 6. Cara Memasukkan Data Baru ke Database

### Alur Lengkap: Scrape → Parse → Seed

```
┌─────────────────┐     ┌──────────────────┐     ┌──────────────┐
│  1. SCRAPE       │     │  2. PARSE         │     │  3. SEED      │
│                  │     │                   │     │               │
│  Chrome Console  │ ──► │  Python Script    │ ──► │  Laravel      │
│  treatwell_      │     │  parse_treatwell_ │     │  php artisan  │
│  scraper.js      │     │  json.py          │     │  db:seed      │
│                  │     │                   │     │               │
│  Output:         │     │  Output:          │     │  Output:      │
│  .json file      │     │  6 JSON files     │     │  Database     │
│  (download)      │     │  di data/         │     │  terisi!      │
└─────────────────┘     └──────────────────┘     └──────────────┘
```

### Command Lengkap

```powershell
# 1. Pindahkan file hasil scraping ke folder data
move C:\Users\[username]\Downloads\treatwell_scrape_2026-04-17.json d:\VIYGO\viygo-app\database\data\

# 2. Parse ke format database
cd d:\VIYGO\viygo-app
python database/scripts/parse_treatwell_json.py database/data/treatwell_scrape_2026-04-17.json

# 3. Cek hasil (opsional)
# Buka database/data/ dan periksa file JSON yang dihasilkan

# 4. Masukkan ke database
php artisan migrate:fresh --seed
```

### Kalau Mau Gabungkan Data Lama + Baru

Jika kalian sudah punya data di `database/data/` dan mau **menambahkan** data baru tanpa kehilangan yang lama, kalian bisa gabungkan manual:

```powershell
# Gabungkan JSON (pakai Python)
python -c "
import json
old = json.load(open('database/data/salon.json'))
new = json.load(open('database/data/treatwell_scrape_new.json'))
# ... gabungkan dan simpan
"
```

> 💡 **Rekomendasi saat development:** Lebih mudah `migrate:fresh --seed` setiap kali. Gabungkan data nanti saat sudah siap production.

---

## 7. Koreksi Scraping Chrome Extension

### Masalah Utama

File Excel kalian (`treatwell-co-uk-2026-04-14.xlsx`) punya masalah ini:

#### 1. Kolom Tidak Bernama Jelas
```
Kolom Excel:  data, data2, data3, data4, ... data35
Seharusnya:   alamat, rating, opening_time, price, ... deskripsi
```

#### 2. Data Tercampur dalam Satu Sel
Contoh isi kolom `Discount_Information`:
```
Ladies - Haircuts & Blow Drys(5)from £30Ladies - Hair Treatments & Styling(7)from £40Ladies - Hair Colouring(7)from £15...Children - Wash, Haircut & Blow Dry  45 minsShow Details£25Select
```
Ini **satu sel** yang berisi nama layanan, jumlah varian, harga, durasi — semuanya disambung tanpa separator!

#### 3. Kolom Salah Isi
```
Kolom: streetAddress (seharusnya alamat jalan)
Isi:   "Credit card accepted"  ← INI BUKAN ALAMAT!
```

#### 4. Solusi yang Sudah Diterapkan
Script `parse_treatwell_excel.py` menangani semua masalah ini:
- **Regex parsing** untuk memisahkan services dari blob Discount_Information
- **Heuristic mapping** untuk 105 kolom ke field yang benar
- **Fallback logic** kalau data di satu kolom salah, cari di kolom lain
- **Category guesser** berdasarkan keyword di nama service

---

## 8. Login Akun Testing

Setelah menjalankan `php artisan migrate:fresh --seed`:

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@viygo.com` | `password` |
| **Customer** | `customer@viygo.com` | `password` |
| **Salon Owner** | `owner_1@viygo.com` | `password` |
| **Salon Owner** | `owner_2@viygo.com` | `password` |
| ... | ... | ... |
| **Salon Owner** | `owner_180@viygo.com` | `password` |

> Owner ke-N = pemilik salon ke-N. Contoh: `owner_1@viygo.com` adalah pemilik salon "Bamboo Hair & Aesthetic".

---

## 9. Troubleshooting

### ❌ Error: "Module not found: openpyxl"
```powershell
pip install openpyxl
```

### ❌ Error: "Column count doesn't match"
Ini terjadi kalau struktur JSON tidak cocok dengan tabel. Pastikan:
1. Jalankan parser dulu sebelum seed
2. Gunakan `migrate:fresh --seed` (bukan `db:seed` saja)

### ❌ Error: "Foreign key constraint fails"
Urutan seeding salah. Pastikan `DatabaseSeeder.php` tidak diubah urutannya.

### ❌ Scraper di Console tidak jalan
- Pastikan kamu di halaman **listing** Treatwell (bukan homepage)
- Coba ketik `allow pasting` di Console kalau ada peringatan
- Pastikan URL mengandung `/places/`

### ❌ Seeding sangat lama (>1 menit)
Normal! `UserSeeder` hashing 180 password dengan bcrypt 12 rounds. Ini memang berat. Kalau mau lebih cepat saat development, ubah di `.env`:
```
BCRYPT_ROUNDS=4
```

### ❌ Mau reset dan seed ulang
```powershell
php artisan migrate:fresh --seed
```
Ini akan menghapus semua tabel, membuat ulang, dan mengisi data dari JSON.

---

## Catatan Akhir

- **Data saat ini** baru dari **area Birmingham** (180 salon). Kalian bisa scrape kota lain (London, Manchester, Leeds, dll) untuk menambah data.
- **Target realistis**: 1.000-1.500 salon dari 10+ kota UK
- **Scrape bertahap**: 50-100 salon per sesi, 2-3 sesi per hari
- Semua file yang dibuat sudah **ter-commit-ready** — tinggal `git add` dan `git commit`
