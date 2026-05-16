# Laporan Scraper VIYGO Part 4 — Reset Total + Audit Treatwell

> Tanggal: **2026-05-10**
> Versi: **v2 Part 4** (re-scrape dari nol, schema Part 3 sudah final)
> Binary: `database/scripts/scraper.exe` (Go, `scraper.go`)
> Panduan operasional: [`cara_Scrap_v2_part1.md`](cara_Scrap_v2_part1.md)

---

## 0. Tujuan Dokumen Ini

1. **Catatan audit** — apa saja yg sudah saya jelajahi di Treatwell.co.uk
2. **Spesifikasi field** — data apa yg di-extract per salon
3. **Justifikasi pilihan teknis** — kenapa pakai JSON-LD, kenapa dua mode scrape
4. **Schema mapping** — Treatwell field → VIYGO DB column
5. **Reset record** — JSON yg sudah dikosongkan

---

## 1. Reset Data — Status Sekarang

Per **2026-05-10**, semua 6 file JSON di `database/data/` sudah jadi
array kosong `[]` (5 bytes):

```
salon.json          5 bytes
service.json        5 bytes
staff.json          5 bytes
salon_images.json   5 bytes
kota.json           5 bytes
kategori.json       5 bytes
```

> `kategori.json` walaupun di-reset, **tidak dipakai** lagi — kategori
> 7 baris di-seed dari `KategoriSeeder.php`. File hanya disisakan agar
> compat dgn legacy scraper v1 yang mungkin masih ada di `_legacy/`.

Schema Part 3 di MySQL juga akan reset saat seeder dijalankan: setiap
seeder mulai dgn `TRUNCATE` (lihat `DatabaseSeeder.php`).

---

## 2. Audit Treatwell.co.uk

### 2.1 Hasil eksplorasi homepage

URL: `https://www.treatwell.co.uk/`

| Element navbar       | Tujuan                                                      |
| -------------------- | ----------------------------------------------------------- |
| **Hair**             | `/places/treatment-group-hair/in-{kota}-uk/`                |
| **Hair Removal**     | `/places/treatment-group-hair-removal/in-{kota}-uk/`        |
| **Massage**          | `/places/treatment-group-massage/in-{kota}-uk/`             |
| **Nails**            | `/places/treatment-group-nails/in-{kota}-uk/`               |
| **Face**             | `/places/treatment-group-face-beauty/in-{kota}-uk/`         |
| **Body**             | `/places/treatment-group-body-treatments/in-{kota}-uk/`     |
| **Men's**            | `/places/treatment-group-mens-grooming/in-{kota}-uk/`       |
| Gift Card / Lookbook | (skip — bukan data salon)                                   |

Per kategori utama ada dropdown dgn **6 sub treatment** + 1 link
"See all X treatments". Khusus Men's: 6 sub + 1 "Barbers".

### 2.2 URL pattern listing salon

Tiga format yang dikenali scraper:

1. **Per kategori utama (group)**:
   ```
   /places/treatment-group-{TreatwellSlug}/in-{kota}-uk/
   ```
   Contoh: `/places/treatment-group-hair/in-london-uk/`

2. **Per sub kategori (treatment spesifik)**:
   ```
   /places/treatment-{TreatwellSlug}/in-{kota}-uk/
   ```
   Contoh: `/places/treatment-blow-dry/in-london-uk/`

3. **Per place type**:
   ```
   /places/at-{tipe}/in-{kota}-uk/
   ```
   Contoh: `/places/at-barbershop/in-manchester-uk/`
   (Scraper saat ini tidak pakai pattern ini — `--filter=barbers` di
   VIYGO Mens dropdown bisa pakai pattern ini sebagai upgrade nanti.)

### 2.3 Pagination

Listing pages punya pagination dgn URL pattern:
```
/places/treatment-{slug}/in-{kota}-uk/page-{N}/
```

Scraper extract pagination links dari DOM (`<a href="...page-N/">`) lalu
breadth-first crawl sampai `maxPages` tercapai. URL diurut by page-N
ascending agar tidak zigzag.

### 2.4 Detail page (per salon)

URL format:
```
/place/{salon-slug}/
```

Treatwell embed **JSON-LD structured data** di setiap detail page —
ini sumber utama scraping. Data utama (`@type=LocalBusiness` atau
`HealthAndBeautyBusiness` atau `BeautySalon`):

| JSON-LD key                  | Tipe          | Mapping ke VIYGO                       |
| ---------------------------- | ------------- | -------------------------------------- |
| `name`                       | string        | `salon.nama_salon`                     |
| `description`                | string        | `salon.deskripsi`                      |
| `telephone`                  | string        | `salon.phone_number`                   |
| `address.streetAddress`      | string        | bagian `salon.alamat`                  |
| `address.addressLocality`    | string        | `kota.nama_kota` (resolve ID)          |
| `address.postalCode`         | string        | bagian `salon.alamat`                  |
| `address.addressRegion`      | string        | `kota.provinsi`                        |
| `geo.latitude`               | number        | `salon.latitude`                       |
| `geo.longitude`              | number        | `salon.longitude`                      |
| `openingHoursSpecification[]`| array         | `salon.opening_time` & `closing_time`  |
| `aggregateRating.ratingValue`| number        | `salon.rating`                         |
| `aggregateRating.reviewCount`| number        | `salon.total_review`                   |
| `image`                      | string \| []  | `salon.image_url` (primary) + `salon_images.*` |
| `hasOfferCatalog`            | OfferCatalog  | `service.*` (loop nested catalog)      |
| `review[]`                   | array         | (tidak di-scrape — terlalu noisy)      |

### 2.5 OfferCatalog (service Treatwell)

Struktur nested:
```
hasOfferCatalog: {
  name: "Available Services",
  itemListElement: [
    {
      @type: "OfferCatalog",         ← kategori grouping
      name: "Hair Cut",
      itemListElement: [
        {
          @type: "Offer",
          price: 35.00,
          itemOffered: {
            @type: "Service",
            name: "Premium Bob Cut",
            additionalProperty: { name: "Duration", value: "PT45M" }
          }
        },
        ...
      ]
    },
    ...
  ]
}
```

Scraper handle recursion: tiap nested OfferCatalog → loop items →
Offer/AggregateOffer → extract `name`, `price`/`lowPrice`,
`additionalProperty.Duration` (ISO 8601, mis. "PT45M" = 45 menit).

`categoryName` dari OfferCatalog parent disimpan sebagai `CategoryHint`
— dipakai matcher untuk mapping ke sub_kategori VIYGO.

### 2.6 Staff (employee)

Treatwell **tidak** punya structured data Employee terpisah, tapi review
JSON-LD kadang punya `employeeDescription` dgn pattern:
```
"treatment by Heena" | "service by Ayesha" | "styled by ..."
```

Scraper extract via regex `(?i)(?:treatment by|service by|styled by|treated by)\s+(.+)`
dari raw HTML — best effort. Coverage tidak 100% (banyak salon tanpa
review terstruktur).

---

## 3. Spesifikasi Field Output JSON

Setelah scraping, 5 file JSON di `database/data/` akan terisi.

### 3.1 `salon.json`

```json
[
  {
    "id_salon": 1,
    "id_user": 1,
    "id_kota": 1,
    "nama_salon": "GHOST - Purley",
    "alamat": "12 High Street, Purley, CR8 2XA",
    "deskripsi": "GHOST - Purley is a professional beauty salon.",
    "phone_number": "+44 20 1234 5678",
    "opening_time": "08:45",
    "closing_time": "18:45",
    "image_url": "https://images.treatwell.co.uk/...",
    "maps_url": null,
    "latitude": 51.3382,
    "longitude": -0.1107,
    "rating": 4.7,
    "total_review": 32,
    "status": "active",
    "source_url": "https://www.treatwell.co.uk/place/ghost-purley/"
  }
]
```

### 3.2 `service.json`

```json
[
  {
    "id_service": 1,
    "id_salon": 1,
    "id_kategori": 1,
    "id_sub_kategori": 2,
    "nama": "Ladies' Wash & Blow Dry",
    "deskripsi": null,
    "durasi": 45,
    "harga": 30.00,
    "status": "active"
  }
]
```

`id_kategori` 1..7 selalu terisi (sesuai kategori utama yg di-scrape).
`id_sub_kategori` 1..42 terisi kalau matcher cocok, NULL kalau tidak.

### 3.3 `staff.json`

```json
[
  {
    "id_staff": 1,
    "id_salon": 1,
    "name": "Heena",
    "profile_url": null,
    "status": "active"
  }
]
```

### 3.4 `salon_images.json`

```json
[
  {
    "id_salon_image": 1,
    "id_salon": 1,
    "image_url": "https://images.treatwell.co.uk/...",
    "is_primary": true,
    "urutan": 1
  }
]
```

### 3.5 `kota.json`

```json
[
  {
    "id_kota": 1,
    "nama_kota": "London",
    "provinsi": "England"
  }
]
```

---

## 4. Algoritma Scraping

### 4.1 Phase 1 — Listing collection (per kota)

```
for setiap kota di kotaList:
  pageQueue = [base URL]
  visited = {}
  while pageQueue tidak kosong dan |visited| < maxPages:
    pageURL = pop dari pageQueue
    fetch HTML(pageURL)
    parse JSON-LD ItemList → extract salon URLs
    fallback: scan all <a href="/place/..."> → extract salon URLs
    extract pagination links → push ke pageQueue
    delay 600-1000ms
```

Dedup salon URL across kota via `seenSalonURLs` map.

### 4.2 Phase 2 — Detail scraping (parallel, 20 workers)

```
for setiap salon URL dari Phase 1:
  goroutine (max 20 concurrent):
    fetch HTML
    parse JSON-LD (LocalBusiness/HealthAndBeautyBusiness/BeautySalon)
    extract: nama, alamat, geo, rating, foto, jam buka, telp, deskripsi
    parse hasOfferCatalog (recursive) → list of ScrapedSvc
    parse raw HTML regex → list of staff names
```

Rate limit handling:
- HTTP 429 → wait 10s, retry
- HTTP 4xx/5xx (non-429) → mark error, skip
- Max retries: 3 per fetch
- Random jitter 0-300ms antar worker

### 4.3 Phase 3 — Merge (single-threaded, atomic)

```
load JSON existing (kota, salon, service, staff, salon_images)
build lookup map: cityName → id_kota, salon source_url → exists?
assign incremental ID utk salon/service/staff/image yg baru

for setiap salon hasil Phase 2:
  if source_url sudah ada di salonURLs: skip (dedup)
  else:
    resolve id_kota (create new kota kalau belum ada)
    push ke salons[]
    for setiap service di hasOfferCatalog:
      resolve id_sub_kategori via matchSubKategori()
      push ke services[] dgn id_kategori (primary) + id_sub_kategori
    for setiap staff name: push ke staffs[]
    for setiap image URL: push ke salonImages[] (is_primary=true untuk i=0)

save JSON kembali ke disk
```

Save dilakukan **setelah setiap kategori/sub** selesai (bukan di akhir
saja), agar interrupt-safe.

### 4.4 Sub-kategori matcher

```go
matchSubKategori(svcName, hint, idKategori int) int {
  // Pass 1: keyword di nama service
  for sub in subKategoriRegistry where sub.IDKategori == idKategori:
    for kw in subKategoriKeywords[sub.IDSubKategori]:
      if strings.Contains(lower(svcName), kw):
        return sub.IDSubKategori
  // Pass 2: keyword di CategoryHint (dari OfferCatalog parent)
  same loop using `hint` instead
  // No match
  return 0  // id_sub_kategori = NULL
}
```

Keyword tiap sub_kategori di `subKategoriKeywords` map (43 entries,
masing-masing 1-5 keyword). Tuneable.

---

## 5. Schema Mapping Lengkap (Treatwell → VIYGO)

| Treatwell JSON-LD                             | VIYGO Field                              | Tabel        |
| --------------------------------------------- | ---------------------------------------- | ------------ |
| `name`                                        | `nama_salon`                             | salon        |
| `description`                                 | `deskripsi`                              | salon        |
| `telephone`                                   | `phone_number`                           | salon        |
| `address.streetAddress`                       | bagian `alamat`                          | salon        |
| `address.addressLocality`                     | resolve → `id_kota`                      | salon, kota  |
| `address.postalCode`                          | bagian `alamat`                          | salon        |
| `address.addressRegion`                       | `provinsi`                               | kota         |
| `geo.latitude`                                | `latitude`                               | salon        |
| `geo.longitude`                               | `longitude`                              | salon        |
| `openingHoursSpecification[0].opens`          | `opening_time`                           | salon        |
| `openingHoursSpecification[0].closes`         | `closing_time`                           | salon        |
| `aggregateRating.ratingValue`                 | `rating`                                 | salon        |
| `aggregateRating.reviewCount`                 | `total_review`                           | salon        |
| `image` (first)                               | `image_url`                              | salon        |
| `image[]`                                     | rows di `salon_images`                   | salon_images |
| `hasOfferCatalog → Offer.itemOffered.name`    | `nama`                                   | service      |
| `hasOfferCatalog → Offer.price`               | `harga`                                  | service      |
| `hasOfferCatalog → Offer.additionalProperty.value` (ISO 8601) | `durasi` (minutes) | service      |
| Listing URL → `kategori.treatwell_slug`       | `id_kategori` (primary)                  | service      |
| service.nama + CategoryHint → matcher         | `id_sub_kategori`                        | service      |
| `review[].employeeDescription` regex          | rows di `staff`                          | staff        |
| (computed) URL listing                        | `source_url`                             | salon        |

---

## 6. Estimasi & Asumsi

### 6.1 Volume

Per scraping run (`--kategori=hair --kota=london --max-pages=2`):

| Metrik                     | Estimasi               |
| -------------------------- | ---------------------- |
| Salon listing pages        | 2 page × 1 kota = 2    |
| Salon URLs unique          | ~30 (15/page)          |
| Service per salon          | 10-40 (rata-rata 20)   |
| Service tersub-tagged      | ~70% (tergantung match)|
| Staff per salon            | 0-3 (banyak null)      |
| Foto per salon             | 3-15 (rata-rata 6)     |

Per `--kategori=all --max-pages=3` (full default — 7 × 3 kota × 3 page):

| Metrik     | Estimasi   |
| ---------- | ---------- |
| Total salon | ~1.500     |
| Total service | ~30.000  |
| Total staff   | ~800     |
| Total foto    | ~9.000   |
| Durasi        | 30-60 mnt |

### 6.2 Asumsi yg dipakai

- Setiap salon punya **1 owner user** baru (di-create otomatis di SalonSeeder/UserSeeder kalau belum ada)
- Service yg tidak match keyword sub_kategori → `id_sub_kategori = NULL` tapi tetap masuk DB (bisa di-tag manual nanti)
- `opening_time` & `closing_time` ambil dari `openingHoursSpecification[0]` — kalau salon punya jam beda per hari, hanya 1 yg disimpan
- Foto: `is_primary = true` hanya untuk image[0]
- Kota yg tidak ada di `kota.json` akan di-create otomatis dgn nama dari `address.addressLocality`

---

## 7. Hasil Verifikasi Build

Per **2026-05-10**:

- ✅ `go build -o scraper.exe scraper.go` → BUILD OK
- ✅ Semua 15 file PHP (5 migration + 6 seeder + 4 model/controller) → syntax OK
- ✅ Legacy Go files (`treatwell_scraper.go`, `parse_*.go`) sudah aman di `database/scripts/_legacy/`
- ✅ JSON data reset ke `[]` (6 file)
- ✅ Migration Part 3 sudah final (kategori 7, sub_kategori 42, pivot 42, salon_kategori derived)

---

## 8. Limitasi & Catatan

1. **`employeeDescription` regex fragile** — kalau Treatwell ubah text
   pattern ("treatment by X" jadi "served by X"), regex perlu diupdate.
2. **Tidak parse review individual** — sengaja, terlalu noisy. Kalau
   butuh, tambah blok extract di `scrapeDetailPage()`.
3. **Tidak ada handling captcha** — Treatwell biasanya tidak pakai
   captcha untuk public listing/detail. Kalau muncul, scraper akan
   dapat HTTP 403 dan mark error.
4. **Single-locale (UK)** — domain `.co.uk` saja. Kalau mau handle
   `.de`/`.es`, perlu adjust `kategoriRegistry.TreatwellSlug` &
   `defaultKotaList`.
5. **Rate limit Treatwell** — observed: HTTP 429 setelah ~50 detail
   requests dalam 10 detik. Default `maxWorkers=20` aman. Kalau pas
   weekend rate limit lebih ketat, turunkan ke 10.
6. **Tidak ada parse working-hours per hari** — hanya simpan satu pair
   open/close. Kalau salon "Mon Closed, Tue 9-18, ..." → simpan jam
   dari first day yg open.

---

## 9. Apa yg Bisa Di-Improve Nanti

| Improvement                                       | Effort | Impact                              |
| ------------------------------------------------- | ------ | ----------------------------------- |
| Parse opening_hours per hari → tabel baru         | Sedang | Akurat untuk booking flow           |
| Pattern URL `/places/at-barbershop/...` utk Barbers filter Mens | Kecil  | Hasil filter Barbers lebih akurat |
| Fuzzy match keyword (Levenshtein) di matcher      | Kecil  | Coverage `id_sub_kategori` naik    |
| Resume support (`--resume`)                       | Sedang | Auto-continue setelah Ctrl+C       |
| Parallel per kategori (saat ini sequential)       | Kecil  | ~5x faster di mode `--kategori=all`|
| Webhook ke MySQL (skip JSON intermediate)         | Besar  | Real-time, tapi kompleks           |
| Captcha detection + auto-throttle                 | Sedang | Future-proof                       |

---

## 10. Referensi

- **Schema decision history**: lihat `LAPORAN_SCRAPER_V2.md` (Part 1) → `laporan_scrapper_v2_paprt3.md` (Part 3 final)
- **Cara menjalankan**: `cara_Scrap_v2_part1.md`
- **Audit kategori awal** (data lama 2.000+ kategori): `database/data/LAPORAN_AUDIT_KATEGORI_V2.md`
- **Scraper source**: `database/scripts/scraper.go`
- **Legacy scrapers**: `database/scripts/_legacy/` (treatwell_scraper.go + parse_*.go)
