# 📊 Laporan Audit & Perbaikan Data Scraping — VIYGO (Treatwell Clone)

**Tanggal Audit:** 28 April 2026  
**Direktori Data:** `database/data/`  
**Total File Diaudit:** 6 file JSON  
**Status:** ✅ **SEMUA ANOMALI TELAH DIPERBAIKI**

---

## 📁 Ringkasan File Data

| No | File | Ukuran | Total Record |
|----|------|--------|--------------|
| 1 | `salon.json` | ~5.9 MB | 8,750 salon |
| 2 | `service.json` | ~54 MB | ~100,000+ service |
| 3 | `salon_images.json` | ~14.3 MB | ~73,000+ gambar |
| 4 | `kategori.json` | ~2.6 MB | ~11,000+ kategori |
| 5 | `staff.json` | ~1.4 MB | 10,906 staff |
| 6 | `kota.json` | ~132 KB | 1,517 kota *(setelah dedup)* |

---

## 🔧 PERBAIKAN FASE 1: Unicode Escape Sequences

Seluruh Unicode escape sequence pada 5 file JSON telah dikonversi ke karakter aslinya.

| Escape Code | Karakter Asli | Deskripsi | Total Diperbaiki |
|-------------|---------------|-----------|:----------------:|
| `\u0026` | `&` | Ampersand | **59,676** |
| `\u003c` | `<` | Kurung sudut buka | **39** |
| `\u003e` | `>` | Kurung sudut tutup | **50** |
| `\u002F` | `/` | Forward slash | **39** |
| `\u2028` | *(spasi)* | Line separator tersembunyi | **1** |
| | | **TOTAL FASE 1** | **59,805** |

### Detail Per File

| File | `&` | `<` | `>` | `/` | `\u2028` | Total |
|------|:---:|:---:|:---:|:---:|:--------:|:-----:|
| `service.json` | 50,793 | 23 | 34 | 0 | 0 | **50,850** |
| `kategori.json` | 5,320 | 16 | 16 | 0 | 0 | **5,352** |
| `salon.json` | 3,482 | 0 | 0 | 0 | 1 | **3,483** |
| `staff.json` | 60 | 0 | 0 | 39 | 0 | **99** |
| `kota.json` | 21 | 0 | 0 | 0 | 0 | **21** |
| `salon_images.json` | 0 | 0 | 0 | 0 | 0 | **0** |

### Contoh Perbaikan

```diff
- "nama_salon": "Plump \u0026 Pout Aesthetics"
+ "nama_salon": "Plump & Pout Aesthetics"

- "nama": "Brows And Lashes\u003cwomen Only\u003e"
+ "nama": "Brows And Lashes<women Only>"

- "name": "Lead Stylist \u002F Nicolae"
+ "name": "Lead Stylist / Nicolae"

- "alamat": "5, Bell Parade\u2028Glebe Way, West Wickham, BR4 0RH"
+ "alamat": "5, Bell Parade Glebe Way, West Wickham, BR4 0RH"
```

---

## 🔧 PERBAIKAN FASE 2: Trim & Normalisasi Teks (`kota.json`)

| Jenis Perbaikan | Jumlah Diperbaiki |
|-----------------|:-----------------:|
| Trailing/leading spaces dihapus | **558** |
| Normalisasi huruf besar/kecil | **115** |

### Contoh Perbaikan

```diff
  Trailing/Leading Spaces:
- "nama_kota": "London "         →  "nama_kota": "London"
- "nama_kota": " London"         →  "nama_kota": "London"
- "nama_kota": "Sutton, "        →  "nama_kota": "Sutton"
- "provinsi": " Grater London,  " →  "provinsi": "Grater London"

  Normalisasi Huruf:
- "nama_kota": "HORNCHURCH"      →  "nama_kota": "Hornchurch"
- "nama_kota": "ROMFORD"         →  "nama_kota": "Romford"
- "nama_kota": "islington"       →  "nama_kota": "Islington"
- "provinsi": "london "          →  "provinsi": "London"
```

---

## 🔧 PERBAIKAN FASE 3: Alamat Sebagai Nama Kota (`kota.json`)

Sebanyak **1,111 record** memiliki alamat/lokasi detail yang tercatat sebagai `nama_kota`. Semua telah diperbaiki dengan strategi berikut:

- Jika `provinsi` berisi nama kota yang valid → gunakan `provinsi` sebagai `nama_kota`
- Jika keduanya berupa alamat → set `nama_kota` = `"Unknown"`

### Contoh Perbaikan

| id_kota | SEBELUM (nama_kota) | SEBELUM (provinsi) | SESUDAH (nama_kota) |
|:-------:|---------------------|--------------------|--------------------|
| 7 | Unit 8, Flagstaff House | Vauxhall | **Vauxhall** |
| 10 | Inside GYMNATION | London | **London** |
| 21 | 5 Carriage Way, Market Yard | Deptford | **Deptford** |
| 32 | (92 White Post Lane) | Hackney Wick | **Hackney Wick** |
| 34 | 22 White Conduit Street | London | **London** |
| 37 | 100 Liverpool Street | London | **London** |
| 42 | The High Road, Ilford, IG11AS | Grater London | **Grater London** |
| 56 | 159 High Street | Wood Green | **Wood Green** |
| 58 | Eden Walk, shopping centre | Kingston Upon Thames | **Kingston Upon Thames** |
| 89 | 8 Harrow place | London | **London** |
| 92 | 151 Sydney St | London | **London** |
| 96 | Unit 4, 10 Portman Square | London | **London** |
| 97 | Studio 27 | 8 Aerodrome Road | **Unknown** |
| 99 | 3, John Sessions Square... | Aldgate East Tube... | **Unknown** |
| 141 | 11 Lansdowne Close | Tolworth | **Tolworth** |

---

## 🔧 PERBAIKAN FASE 4: Deduplikasi Nama Kota (`kota.json`)

| Metrik | Nilai |
|--------|:-----:|
| Jumlah kota SEBELUM | **2,700** |
| Set duplikat ditemukan | **333** |
| Record yang di-remap | **1,183** |
| Jumlah kota SESUDAH | **1,517** |
| **Record dihapus** | **1,183 (43.8%)** |

### Strategi Merging
- Untuk setiap set duplikat, entry dengan **provinsi paling lengkap** dan **id_kota terkecil** dipertahankan sebagai canonical
- Semua referensi `id_kota` di `salon.json` di-update ke entry canonical

### Contoh Duplikat yang Di-merge

| Nama Kota | ID Sebelum (duplikat) | ID Canonical |
|-----------|:---------------------:|:------------:|
| London | 3, 121, 142, 649, 682, 981 | **3** |
| Romford | 79, 158, 1531 | **79** |
| Birmingham | 462, 521 | **462** |
| Manchester | 396, 450, 459, 1077 | **396** |
| Bristol | 2158, 2184, 2199, 2215 | **2158** |
| Glasgow | 2314, 2343, 2406 | **2314** |
| Brighton | 2111, 2115, 2136 | **2111** |
| Bermondsey | 91, 575, 1016 | **91** |
| Enfield | 71, 973, 1980 | **71** |
| Islington | 147, 200, 690 | **147** |

### Update Referensi di `salon.json`

| Metrik | Nilai |
|--------|:-----:|
| Total salon yang `id_kota` di-update | **4,550** |
| Total salon (tidak berubah) | 4,200 |

---

## 🔧 PERBAIKAN FASE 5: Pengisian Provinsi Kosong (`kota.json`)

Sebanyak **308 record** dengan `provinsi` kosong telah diisi dengan logika berikut:

| Kondisi | Nilai Provinsi | Jumlah |
|---------|:--------------:|:------:|
| Nama kota = "London" / "City Of London" | `England` | otomatis |
| Nama kota dikenali sebagai area London | `London` | mayoritas |
| Nama kota lainnya (UK) | `GB` | sisanya |

**Hasil: 0 record dengan provinsi kosong tersisa.**

---

## 🔧 PERBAIKAN FASE 6: Generate `maps_url` (`salon.json`)

Field `maps_url` yang sebelumnya **100% null** pada semua 8,750 salon kini telah diisi otomatis menggunakan data `latitude` dan `longitude` yang sudah tersedia.

| Metrik | Nilai |
|--------|:-----:|
| Maps URL di-generate | **8,750 (100%)** |
| Format URL | `https://www.google.com/maps?q={lat},{lng}` |

### Contoh Hasil

```json
{
  "id_salon": 1,
  "maps_url": "https://www.google.com/maps?q=51.4828532,-0.309824"
}
{
  "id_salon": 4,
  "maps_url": "https://www.google.com/maps?q=51.4693591,-0.2547794"
}
```

---

## ℹ️ CATATAN: Field `phone_number`

Field `phone_number` tetap `null` pada seluruh 8,750 salon karena data ini **tidak tersedia dari sumber scraping** (Treatwell tidak menampilkan nomor telepon salon secara publik). Field ini tidak dapat diperbaiki tanpa sumber data tambahan.

---

## ✅ VERIFIKASI AKHIR

| Anomali | Status |
|---------|:------:|
| Unicode escape sequences | ✅ **0 tersisa** (semua file) |
| Trailing/leading spaces | ✅ **0 tersisa** |
| Inkonsistensi huruf besar/kecil | ✅ **Diperbaiki** |
| Alamat sebagai nama kota | ✅ **0 tersisa** |
| Duplikat nama kota | ✅ **0 tersisa** |
| Provinsi kosong | ✅ **0 tersisa** |
| `maps_url` null | ✅ **0 tersisa** (di-generate dari lat/lng) |
| `phone_number` null | ℹ️ Tidak dapat diperbaiki (data tidak tersedia) |

---

## 📋 Ringkasan Eksekutif

| # | Perbaikan | Jumlah |
|:-:|-----------|:------:|
| 1 | Unicode escape → karakter asli | **59,805** |
| 2 | Trim trailing/leading spaces | **558** |
| 3 | Normalisasi huruf besar/kecil | **115** |
| 4 | Alamat → nama kota yang benar | **1,111** |
| 5 | Duplikat kota di-merge | **1,183 record dihapus** |
| 6 | Referensi `id_kota` di salon di-update | **4,550** |
| 7 | Provinsi kosong diisi | **308** |
| 8 | `maps_url` di-generate | **8,750** |
| | **TOTAL PERBAIKAN** | **~76,380** |

### Perubahan Ukuran File

| File | Sebelum | Sesudah | Perubahan |
|------|:-------:|:-------:|:---------:|
| `kota.json` | 250 KB (2,700 record) | 132 KB (1,517 record) | **-47%** |
| `salon.json` | 5.6 MB | 5.9 MB | +5% *(karena penambahan maps_url)* |
| `kategori.json` | 2.6 MB | 2.6 MB | ~ *(unicode fix saja)* |
| `service.json` | 54 MB | 54 MB | ~ *(unicode fix saja)* |
| `staff.json` | 1.4 MB | 1.4 MB | ~ *(unicode fix saja)* |
| `salon_images.json` | 14.3 MB | 14.3 MB | Tidak berubah |

---

*Laporan ini dibuat pada 28 April 2026.*  
*Script yang digunakan: `fix_all_anomalies.ps1`, `fix_postprocess.ps1`*
