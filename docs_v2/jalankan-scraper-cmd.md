# ⚙️ Status Go & Cara Pakai Scraper di Command Prompt (CMD)

> Panduan ringkas: status instalasi Go + cara menjalankan scraper lewat **Command Prompt (cmd.exe)**.
> Penjelasan alur lengkap scraper ada di [penjelasanscraper.md](penjelasanscraper.md).

---

## 1. Status Go ✅ SUDAH OKE

```
go version go1.26.4 windows/amd64
GOROOT = C:\Program Files\Go
```

- Go **1.26.4** terpasang & berfungsi (toolchain compile bersih, dependency lengkap).
- Awalnya folder `C:\Program Files\Go\bin` **belum masuk PATH** (winget memasang Go tapi tidak menambah PATH), jadi CMD belum kenal perintah `go`.
- **Sudah diperbaiki** — `C:\Program Files\Go\bin` ditambahkan ke **User PATH**. Terverifikasi: terminal/CMD baru akan mengenali `go`.

> [!IMPORTANT]
> **CMD yang sudah terbuka SEBELUM perbaikan PATH tidak akan kenal `go`.**
> Selalu **buka jendela CMD BARU** supaya PATH terbaru kebaca.
> Kalau masih "not recognized" setelah buka CMD baru → restart komputer.

---

## 2. Cara Pakai Scraper di Command Prompt

### Langkah 1 — Buka CMD baru
- Tekan **`Win + R`** → ketik **`cmd`** → **Enter**
- Atau: di File Explorer buka folder proyek → klik kolom alamat → ketik **`cmd`** → **Enter**

### Langkah 2 — Cek Go terbaca
```cmd
go version
```
Harus muncul: `go version go1.26.4 windows/amd64`
Kalau *"not recognized"* → tutup CMD, buka CMD baru lagi (atau restart komputer).

### Langkah 3 — Masuk ke folder scraper
```cmd
cd /d Y:\VIYGO\au123-pk6m\scripts\scraper
```
> `/d` wajib karena pindah ke drive **Y:**

### Langkah 4 — Unduh dependency (sekali; aman diulang)
```cmd
go mod tidy
```

### Langkah 5 — Jalankan scraper
```cmd
go run fresh_scraper.go
```

### Langkah 6 — Masukkan hasil ke database
```cmd
cd /d Y:\VIYGO\au123-pk6m
php artisan db:seed --class=FreshProductSeeder
```

---

## 3. Ringkasan Copy-Paste (urut)

```cmd
go version
cd /d Y:\VIYGO\au123-pk6m\scripts\scraper
go mod tidy
go run fresh_scraper.go
cd /d Y:\VIYGO\au123-pk6m
php artisan db:seed --class=FreshProductSeeder
```

---

## 4. ⚠️ Penting Sebelum Run Live

**fresh.com diproteksi Akamai Bot Manager (anti-bot).** Saat `go run fresh_scraper.go`
benar-benar menembak fresh.com:

| Hal | Status |
|-----|--------|
| Toolchain Go (compile, deps) | ✅ 100% oke |
| Hasil scrape **live** fresh.com | ⚠️ kemungkinan **kosong/diblok** (Akamai 403) |
| Selector di `fresh_scraper.go` | ⚠️ masih placeholder, belum cocok DOM asli fresh.com |

**Untuk data demo yang sudah jalan:** cukup **Langkah 6 saja** — seed dari 40 produk dummy
terkurasi yang sudah ada di `scripts/scraper/output/*.json`. Tidak perlu run scraper live.

### 🧪 Hasil Uji Coba Live (sudah dicoba, 6 Jun 2026)

Scraper **benar-benar dijalankan** ke `/collections/black-tea-collection`. Hasilnya:

```
  gagal detail https://www.onetrust.com/products/cookie-consent/: context canceled
  export 0 produk -> output\black-tea-collection.json
output: null
```

**Apa yang terjadi:**
- Chrome (chromedp) berhasil jalan & navigasi ke fresh.com.
- **Grid produk asli TIDAK ter-render** — kena tembok Akamai + cookie-consent (OneTrust).
- Satu-satunya link `/products/` yang ketemu malah **link vendor cookie-consent OneTrust**
  (selector `a[href*='/products/']` terlalu luas).
- Muncul juga noise `cookiePartition/partitionKey` = ketidakcocokan versi cdproto (chromedp v0.9.3) vs Chrome 1.26 — tidak fatal.
- **Hasil akhir: 0 produk, output `null`.**

**Kesimpulan:** scrape live fresh.com **tidak viable apa adanya**. Untuk berhasil perlu:
(1) menangani consent/Akamai, (2) menyesuaikan selector ke DOM asli, (3) kemungkinan mode
non-headless + teknik stealth. Karena itu VIYGO **memakai data dummy terkurasi** — itu yang
sudah ter-seed & dipakai aplikasi.

> Mau menambah produk dummy sendiri? Buat/edit file `.json` di `scripts\scraper\output\`
> (ikuti format di [penjelasanscraper.md](penjelasanscraper.md)), lalu ulangi Langkah 6.

---

## 5. Verifikasi Hasil

Di CMD (folder proyek `Y:\VIYGO\au123-pk6m`):
```cmd
php artisan tinker
```
Lalu di dalam tinker:
```php
App\Models\Product::count();
App\Models\ProductCategory::count();
App\Models\ProductImage::count();
exit
```

Atau buka di browser setelah `php artisan serve`:
```
http://127.0.0.1:8000/shop
```
(halaman `/shop` baru aktif penuh setelah Phase 2B dikerjakan)
