# 📊 Nail Category Excel — Import Report

> **File sumber:** `database/SCRAP/nail_allcategory_uk.xlsx`  
> **Script:** `database/scripts/parse_nail_category.go`  
> **Tanggal import:** 2026-04-17

---

## Tentang Script

Script Go `parse_nail_category.go` diadaptasi dari `parse_body_category.go` yang sudah ada. Modifikasi:

| Aspek | Body Script | Nail Script |
|---|---|---|
| **Excel path** | `body_bodyallcategory_uk.xlsx` | `SCRAP/nail_allcategory_uk.xlsx` |
| **Default category** | `Body` | `Nails` |
| **Category priority** | Body pertama | Nails pertama |
| **Default deskripsi** | "...beauty and wellness salon" | "...nail and beauty salon" |

Format kolom Excel **identik** dengan body — tidak perlu modifikasi parsing logic.

---

## Cara Menjalankan

### Prasyarat
```powershell
# Pastikan Go terinstall
go version

# Install dependencies (sudah dilakukan via go.mod)
cd database/scripts
```

### Jalankan Parser
```powershell
cd database/scripts
go build -o parse_nail_category.exe parse_nail_category.go
.\parse_nail_category.exe
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

| Data | Sebelum (body only) | Ditambahkan (nail) | **Total** |
|---|---|---|---|
| Salon | 3.832 | +2.392 | **6.224** |
| Service | 9.239 | +3.118 | **12.357** |
| Kota | 211 | +12 | **223** |
| Kategori | 10 | +0 | **10** |
| Duplikat dilewati | — | 1.436 | — |

### Info Excel
| Properti | Nilai |
|---|---|
| File | `nail_allcategory_uk.xlsx` |
| Sheet | `Recovered_Sheet1` |
| Total baris (excl. header) | 3.828 |
| Baris diproses (salon baru) | 2.392 |
| Baris dilewati (duplikat) | 1.436 |

### Waktu Eksekusi
| Proses | Waktu |
|---|---|
| Parse Excel (Go) | ~2 detik |
| `php artisan migrate:fresh --seed` | ~4 detik |
| **Total** | **~6 detik** |

---

## Category Mapping

Service dari Excel nail di-map ke kategori berdasarkan keyword. **Nails diprioritaskan pertama** karena ini Excel kategori nail:

| Kategori | Keyword Contoh |
|---|---|
| `Nails` ⭐ | nail, manicure, pedicure, gel, acrylic, shellac, polish, dipping |
| `Body` | body, scrub, wrap, tanning, hifu, cellulite, exfoliation |
| `Massage` | massage, spa, reflexology, hot stone, cupping, thai |
| `Hair` | hair, haircut, blowdry, balayage, keratin, braid |
| `Face` | facial, microneedling, dermaplaning, peel |
| `Hair Removal` | wax, threading, laser, sugaring, bikini |
| `Eyebrows & Lashes` | brow, lash, lamination, lash lift |
| `Medical Aesthetics` | botox, filler, prp, chemical peel |
| `Counselling & Holistic` | reiki, chakra, acupuncture, meditation |

Default fallback: **Nails** (sesuai konteks Excel ini adalah category "Nails").

---

## File yang Dihasilkan / Dimodifikasi

```
database/
├── SCRAP/
│   └── nail_allcategory_uk.xlsx   ← Source file
├── scripts/
│   ├── parse_nail_category.go     ← Script Go baru
│   └── parse_nail_category.exe    ← Binary hasil build
├── data/
│   ├── kota.json          → 223 records (+12 baru)
│   ├── kategori.json      → 10 records (tidak berubah)
│   ├── salon.json         → 6224 records (+2392 baru)
│   ├── service.json       → 12357 records (+3118 baru)
│   ├── staff.json         → 254 records (tidak berubah)
│   └── salon_images.json  → 529 records (tidak berubah)
└── seeders/
    └── (tidak berubah)
```

---

## File Excel yang Belum Diimport

| File | Status |
|---|---|
| `body_bodyallcategory_uk.xlsx` | ✅ Sudah diimport |
| `nail_allcategory_uk.xlsx` | ✅ **Baru diimport** |
| `face_allcategory_uk.xlsx` | ❌ Belum diimport |
| `treatwell-co-uk-2026-04-14.xlsx` | ❌ Belum diimport (format berbeda) |
