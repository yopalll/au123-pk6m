# 📊 Body Category Excel — Import Guide

> **File sumber:** `database/body_bodyallcategory_uk.xlsx`  
> **Script:** `database/scripts/parse_body_category.go`  
> **Tanggal import:** 2026-04-17

---

## Kenapa Python Terlalu Lambat?

Script Python lama (`parse_treatwell_excel.py`) menggunakan `openpyxl` dengan akses sel **satu per satu** (`ws.cell(row, col)`). Pada file 2.5 MB dengan 3720 baris × 74 kolom, ini menghasilkan **~274.000 operasi baca individual** — sangat lambat.

Solusinya adalah menggantinya dengan **Go + excelize** yang:
- Membaca seluruh sheet sekaligus ke memory (`f.GetRows(sheet)`)
- Tidak ada overhead interpreter
- Selesai dalam **< 2 detik** untuk 3720 baris

---

## Kenapa Script Lama Tidak Bisa Dipakai Langsung?

| Aspek | Excel Lama (`treatwell-co-uk-*.xlsx`) | Excel Baru (`body_bodyallcategory_uk.xlsx`) |
|---|---|---|
| **Header kolom** | Nama bermakna (`title`, `rating_value`, dll.) | CSS class names (`Text-module_body__2lxF8`) |
| **Struktur data** | Multi-baris per salon (JSON-LD blob) | Satu baris per salon — lebih bersih |
| **Jam buka** | 1 kolom string (`"Open Today: 10:00 AM"`) | 47 kolom terpisah (per hari, open, AM/PM) |
| **Service** | Blob concatenated di `Discount_Information` | Kolom terpisah (name, duration, price) |
| **Deskripsi** | 1 kolom | 8+ kolom bagian (transport, team, atmosphere) |

Script lama sama sekali tidak mengenali struktur baru ini → perlu script baru.

---

## Format Excel Baru — Pemetaan Kolom

```
Col 1   → URL salon (treatwell.co.uk/place/...)
Col 2   → Nama salon
Col 3   → Rating (0.0–5.0)
Col 4   → Jumlah review ("X reviews")
Col 5   → Lokasi ("City, Region")
Col 6   → Nama service utama
Col 7   → Durasi service ("30 mins", "1 hr")
Col 8   → Harga service ("£90", "from")
Col 9–55 → Jam buka per hari (Mon–Sun × open/close)
Col 56–63 → Deskripsi (bagian-bagian: transport, team, atmosphere, dll.)
Col 64, 67, 70 → Harga service tambahan
Col 65–66, 68–69 → Nama & durasi service tambahan
Col 72–73 → Deskripsi extended
```

---

## Cara Menjalankan

### Prasyarat
```powershell
# Pastikan Go terinstall
go version

# Install dependencies (sudah dilakukan, tidak perlu ulang)
cd database/scripts
go get github.com/xuri/excelize/v2
```

### Jalankan Parser
```powershell
cd database/scripts
go build -o parse_body_category.exe parse_body_category.go
.\parse_body_category.exe
```

> Script otomatis mendeteksi path berdasarkan lokasi eksekusi.
> Data lama tidak dihapus — data baru di-merge (append) ke JSON yang ada.
> Duplikat dideteksi via URL salon (tanpa query params).

### Seed ke Database
```powershell
cd ../..
php artisan migrate:fresh --seed
```

---

## Hasil Import

| Data | Jumlah |
|---|---|
| Salon baru ditambahkan | **3.652** |
| Service baru ditambahkan | **7.277** |
| Duplikat dilewati | 68 |
| **Total salon** | **3.832** |
| **Total service** | **9.239** |
| **Total kota** | **211** |
| **Total kategori** | **10** |

### Waktu Eksekusi
| Proses | Waktu |
|---|---|
| Parse Excel (Go) | ~2 detik |
| `php artisan migrate:fresh --seed` | ~8 detik |
| **Total** | **~10 detik** |

---

## Optimasi Seeder: Fix `UserSeeder.php`

`bcrypt` (yang dipakai `Hash::make()`) memang **dirancang lambat** untuk keamanan (~100–200ms per hash). Memanggil ini 3.832 kali = **6–12 menit**.

**Solusi:** Hash sekali, reuse untuk semua owner:

```php
// Sebelum: Hash::make() dipanggil 3832 kali = sangat lambat
foreach ($chunk as $salon) {
    $ownerRows[] = ['password' => Hash::make('password'), ...];
}

// Sesudah: Hash sekali, reuse
$hashedPassword = Hash::make('password');
foreach ($chunk as $salon) {
    $ownerRows[] = ['password' => $hashedPassword, ...];
}
```

> **Default password semua salon owner:** `password`

---

## Category Mapping

Service dari Excel body di-map ke kategori berdasarkan keyword:

| Kategori | Keyword Contoh |
|---|---|
| `Body` | body, scrub, wrap, tanning, hifu, cellulite, exfoliation |
| `Massage` | massage, spa, reflexology, hot stone, cupping, thai |
| `Hair` | hair, haircut, blowdry, balayage, keratin, braid |
| `Face` | facial, microneedling, dermaplaning, peel |
| `Nails` | nail, manicure, pedicure, gel, acrylic |
| `Hair Removal` | wax, threading, laser, sugaring, bikini |
| `Eyebrows & Lashes` | brow, lash, lamination, lash lift |
| `Medical Aesthetics` | botox, filler, prp, chemical peel |
| `Counselling & Holistic` | reiki, chakra, acupuncture, meditation |

Default fallback: **Body** (sesuai konteks Excel ini adalah category "Body").

---

## File yang Dihasilkan / Dimodifikasi

```
database/
├── scripts/
│   ├── parse_body_category.go       ← Script Go baru
│   ├── parse_body_category.exe      ← Binary hasil build
│   └── parse_body_category_excel.py ← Script Python lama (tidak dipakai)
├── data/
│   ├── kota.json          → 211 records
│   ├── kategori.json      → 10 records
│   ├── salon.json         → 3832 records (+3652 baru)
│   ├── service.json       → 9239 records (+7277 baru)
│   ├── staff.json         → 254 records (tidak berubah)
│   └── salon_images.json  → 529 records (tidak berubah)
└── seeders/
    └── UserSeeder.php     ← Dioptimasi (pre-hash bcrypt)
```
