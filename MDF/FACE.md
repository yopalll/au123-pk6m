# 📊 Face Category Excel — Import Guide

> **File sumber:** `database/SCRAP/face_allcategory_uk.xlsx`  
> **Script:** `database/scripts/parse_face_category.go`  
> **Tanggal import:** 2026-04-18

---

## Struktur Excel Face

File `face_allcategory_uk.xlsx` memiliki **7.766 baris data** × **78 kolom**.  
Struktur kolomnya identik dengan Excel body dan nail (CSS class names dari Web Scraper).

### Pemetaan Kolom

```
Col 1   → URL salon (treatwell.co.uk/place/...)
Col 2   → Nama salon
Col 3   → Rating (0.0–5.0)
Col 4   → Jumlah review ("X reviews")
Col 5   → Lokasi ("City, Region")
Col 6   → Nama service utama #1
Col 7   → Durasi service #1 ("30 mins", "1 hr")
Col 8   → Harga service #1 ("£45", "from")
Col 9   → Harga diskon (opsional)
Col 10  → Nama service #2
Col 11  → Durasi service #2
Col 12  → Harga service #2
Col 13  → Harga diskon (opsional)
Col 14  → Nama service #3
Col 15  → Durasi service #3
Col 16  → Harga service #3
Col 17–63 → Jam buka per hari (Mon–Sun × open/close/AM/PM)
Col 64–70 → Deskripsi (transport, team, atmosphere, dll.)
Col 65–69 → Service tambahan (dicampur dengan deskripsi)
Col 72–77 → Extended description
```

---

## Modifikasi dari Script Body/Nail

Script `parse_face_category.go` di-adaptasi dari `parse_body_category.go` dengan perubahan berikut:

| Aspek | Body/Nail | Face |
|---|---|---|
| **Excel path** | `body_bodyallcategory_uk.xlsx` | `face_allcategory_uk.xlsx` |
| **Default category** | `"Body"` / `"Nails"` | `"Face"` |
| **Default deskripsi** | "...beauty and wellness salon" | "...facial and beauty salon" |
| **Category priority** | Body/Nails pertama | Face pertama di keyword map |
| **Service slots** | 1 slot inline + 2 extra | **3 slot inline** + 2 extra |
| **Extra svc filter** | Minimal | Filter deskripsi (nearest, team, dll.) |
| **Row padding** | 74 cols | **78 cols** (Face punya lebih banyak kolom) |
| **Face keywords** | Standard | + `hydrafacial`, `skin`, `complexion`, `rejuvenat` |

### Perubahan Detail

#### 1. Prioritas Category — Face di urutan pertama
```go
// Face diprioritaskan di urutan pertama (sebelum Body/Hair/dll)
var categoryMap = []struct{ ... }{
    {"Face", []string{"facial", "face", "dermaplaning", "hydrafacial", "skin", ...}},
    {"Eyebrows & Lashes", ...},
    {"Medical Aesthetics", ...},
    ...
}
```

#### 2. Parse 3 service slots inline (bukan hanya 1)
```go
// Slot 1: col 6-8 (idx 5-7)
if n := row[5]; n != "" { svcs = append(svcs, ...) }
// Slot 2: col 10-12 (idx 9-11)  ← BARU
if n := row[9]; n != "" { svcs = append(svcs, ...) }
// Slot 3: col 14-16 (idx 13-15) ← BARU
if n := row[13]; n != "" { svcs = append(svcs, ...) }
```

#### 3. Filter deskripsi dari kolom extra service
```go
// Kolom 65-69 kadang berisi deskripsi, bukan service
// Filter berdasarkan panjang dan keyword
if n != "" && len(n) < 80 &&
    !strings.Contains(n, "nearest") &&
    !strings.Contains(n, "the team") &&
    !strings.Contains(n, "public transport") { ... }
```

---

## Cara Menjalankan

### Build & Run
```powershell
cd d:\VIYGO\viygo-app\database\scripts
go build -o parse_face_category.exe parse_face_category.go
.\parse_face_category.exe
```

### Seed ke Database
```powershell
cd d:\VIYGO\viygo-app
php artisan migrate:fresh --seed
```

> Script otomatis membaca data JSON yang sudah ada dan **merge** (append) data baru.  
> Duplikat dideteksi via URL salon (tanpa query params) — salon yang sudah ada dilewati.

---

## Hasil Import

| Data | Jumlah |
|---|---|
| Salon baru ditambahkan (dari Face Excel) | **3.111** |
| Service baru ditambahkan | **8.597** |
| Duplikat dilewati | **4.655** |

### Total Kumulatif (Setelah Hair + Body + Nail + Face)

| Tabel | Jumlah Record |
|---|---|
| **Kota** | 236 |
| **Kategori** | 10 |
| **Users** | 9.337 (1 admin + 1 customer + 9.335 owners) |
| **Salon** | **9.335** |
| **Service** | **20.954** |
| **Staff** | 254 |
| **Salon Images** | 529 |
| **GRAND TOTAL** | **~40.655 records** |

### Waktu Eksekusi

| Proses | Waktu |
|---|---|
| Parse Excel (Go) | ~2 detik |
| `php artisan migrate:fresh --seed` | ~4 detik |
| **Total** | **~6 detik** |

---

## Duplikat yang Tinggi (4.655)

Angka duplikat tinggi karena banyak salon Face juga sudah ter-scrape di kategori Body dan Nail sebelumnya. Ini **normal** — banyak salon menawarkan layanan lintas kategori (face + nail + body).

Contoh: Salon "Anchal Massage & Beauty Salon" menawarkan facial, massage, dan nail — muncul di semua Excel.

Script menangani ini dengan membandingkan **base URL** salon:
```go
baseURL := strings.Split(salonURL, "?")[0]
if salonURLs[baseURL] {
    skipped++  // sudah ada, skip
    continue
}
```

---

## Category Mapping (Face Priority)

Service di-map ke kategori berdasarkan keyword, dengan **Face diprioritaskan**:

| Kategori | Keyword | Contoh Service dari Excel |
|---|---|---|
| **Face** | facial, face, dermaplaning, hydrafacial, skin, peel | Hydrafacial - Classic, Dermaplaning Facial |
| **Eyebrows & Lashes** | brow, lash, lvl, lamination, lash lift | Lash Lift & Tint, Brow Tidy & Shape |
| **Medical Aesthetics** | aesthetic, filler, botox, prp, meso | Micro-Needling Facial By Dermapen |
| **Hair Removal** | wax, threading, laser, sugaring | Eyebrow Waxing |
| **Massage** | massage, spa, reflexology, hot stone | Full Body Massage |
| **Body** | body, scrub, wrap, tanning | Body Scrub Treatment |
| **Hair** | hair, haircut, blowdry, balayage | Wash & Blow Dry |
| **Nails** | nail, manicure, pedicure, gel | Gel Manicure |
| **Counselling & Holistic** | reiki, meditation, acupuncture | Crystal Healing Session |

Default fallback: **Face** (sesuai konteks file ini).

---

## File yang Dihasilkan / Dimodifikasi

```
database/
├── scripts/
│   ├── parse_face_category.go       ← Script Go BARU
│   └── parse_face_category.exe      ← Binary hasil build
├── data/
│   ├── kota.json          → 236 records  (+13 kota baru)
│   ├── kategori.json      → 10 records   (tidak berubah)
│   ├── salon.json         → 9.335 records (+3.111 baru)
│   ├── service.json       → 20.954 records (+8.597 baru)
│   ├── staff.json         → 254 records   (tidak berubah)
│   └── salon_images.json  → 529 records   (tidak berubah)
└── SCRAP/
    └── face_allcategory_uk.xlsx     ← Source data (5.3 MB)
```

---

## Urutan Import (Jika Mengulang dari Awal)

Jika ingin rebuild semua data dari nol:

```powershell
cd d:\VIYGO\viygo-app\database\scripts

# 1. Hapus data lama (opsional)
# Remove-Item ..\data\*.json

# 2. Parse per kategori (urutan bebas, karena merge otomatis)
.\parse_body_category.exe    # Body → +3.652 salon
.\parse_nail_category.exe    # Nail → +2.392 salon
.\parse_face_category.exe    # Face → +3.111 salon

# 3. Seed database
cd ..\..
php artisan migrate:fresh --seed
```
