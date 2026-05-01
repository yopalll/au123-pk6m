# 🔗 VIYGO Frontend Integration Guide

**Tanggal**: May 1, 2026
**Status**: ✅ **COMPLETED — May 1, 2026**
**Sumber**: `/update/` folder (sekarang archived)
**Target**: Integrasi ke project Laravel VIYGO ← SUDAH DIINTEGRASIKAN

---

## ✅ Ringkasan Eksekusi (May 1, 2026)

Semua langkah di guide ini sudah dijalankan. Lihat [PROGRESS_REPORT.md](PROGRESS_REPORT.md) untuk log per-fase, [LAPORAN_PROYEK.md](LAPORAN_PROYEK.md) untuk laporan akhir, dan [progress.md](progress.md) untuk progress tracker keseluruhan.

**Hasil ringkas:**
- ✅ 3 migrasi baru (`add_slug_to_salon_table`, `add_catatan_to_order_detail_table`, `add_unique_index_to_salon_slug`)
- ✅ 1 seeder backfill (`SalonSlugBackfillSeeder`) — 5.767 salon punya slug unik
- ✅ 3 model di-update (`Salon`, `SalonImage`, `Kota`)
- ✅ 10 controllers dibuat di `app/Http/Controllers/` dengan patch
- ✅ 18 named routes di `routes/web.php`
- ✅ 1 layout publik + 5 components (1 baru: `<x-leaflet-map>`)
- ✅ 14 page views — UI sudah dialihbahasakan ke Bahasa Inggris
- ✅ Mata uang dialihkan dari Rp ke £ GBP (data UK)
- ✅ Leaflet 1.9.4 (OpenStreetMap, CDN) menggantikan placeholder peta statis di 3 halaman
- ✅ README.md, progress.md, dan dokumen ini disinkronkan

---

## ⚠️ Deviations from Original Guide

Bagian-bagian guide di bawah ini **diadaptasi** saat eksekusi karena ketidakcocokan dengan skema database / model yang sudah ada:

| Topik | Guide bilang | Yang sebenarnya dijalankan | Alasan |
|-------|--------------|----------------------------|--------|
| `salon.slug` | 1 migrasi (langsung unique) | 3-step: nullable → backfill → unique index | 5.767 baris harus dibackfill **sebelum** unique constraint bisa diterapkan dengan aman |
| `OrderDetail` field | `harga`, `qty`, `catatan` | Tetap pakai schema existing: `harga_at_order`, `subtotal`, `start_time`, `end_time`, `id_staff`, + `catatan` (baru) | Kolom `harga`/`qty` tidak ada di migrasi `2026_04_12_000014_create_order_detail_table.php`. `BookingController::store` di-rewrite untuk map ke field existing |
| `Kota.nama` | Field DB | Accessor (alias ke `nama_kota`) | Migrasi existing pakai `nama_kota`. Accessor cukup untuk Blade; query SQL tetap pakai `nama_kota` (perhatikan di `SearchController`) |
| `SalonImage.url` | Field DB | Accessor (alias ke `image_url`) | Migrasi existing pakai `image_url`. Views existing yang merefer `$image->url` tetap jalan via accessor |
| `User.name` | Validasi `name` di `AkunController` | `first_name + last_name + email` | User model pakai `first_name`/`last_name` (custom PK `id_user`). Form dan controller di-rewrite |
| Kategori navbar | Pakai slug Indonesia (`rambut`, `facial`, `pijat`, dll) di `kategori.show` | Pakai search-based navigation (`/cari?q=hair`, dll) | DB punya 7.183 kategori granular Treatwell UK seperti `ladies-haircuts-hairdressing`. Tidak ada slug "rambut" yang match |
| Map placeholder | Static placeholder div | **Leaflet** (OpenStreetMap, CDN) di 3 halaman: `cari/index`, `kategori/show`, `salon/show` | User minta minimap interaktif |
| Bahasa UI | Bahasa Indonesia | Penuh Bahasa Inggris (single-locale) | Data UK + permintaan user; tidak pakai Laravel i18n |
| Currency | Rp (IDR) | £ GBP, format `en-GB` | Data dari Treatwell UK |
| Title layout | "Library Salon Indonesia" | "VIYGO — Beauty & Wellness Marketplace" | Sama alasan dengan currency |
| `welcome.blade.php` | (tidak dibahas) | Tetap di disk, route `/` dialihkan ke `HomeController@index` | Konfirmasi user — file dibiarkan |
| `KategoriController` `orderBy('services.harga')` | Sebagaimana di guide | Diganti `withMin('services as min_harga', 'harga')` + `orderBy('min_harga')` | Tanpa join, query asli akan crash |
| `SearchController` `where('nama', ...)` di kota | Sebagaimana di guide | Diganti `where('nama_kota', ...)` | Accessor tidak menyentuh SQL |

---



## 📋 Daftar Isi

1. [Ringkasan](#ringkasan)
2. [Struktur Folder Update](#struktur-folder-update)
3. [Analisis Data & Model](#analisis-data--model)
4. [Relasi Antar Tabel](#relasi-antar-tabel)
5. [Frontend Components](#frontend-components)
6. [Routes & Controllers](#routes--controllers)
7. [Prasyarat Integrasi](#prasyarat-integrasi)
8. [Langkah-Langkah Integrasi (Lengkap)](#langkah-langkah-integrasi-lengkap)
9. [Validasi Setelah Integrasi](#validasi-setelah-integrasi)
10. [Troubleshooting](#troubleshooting)

---

## 📖 Ringkasan

Folder `/update/` berisi **frontend lengkap untuk platform marketplace salon VIYGO** dengan konsep mirip Treatwell. Fitur utama:

✅ **Homepage** — Hero dengan search salon/layanan + featured salons  
✅ **Search** — Cari salon berdasarkan nama, layanan, atau lokasi  
✅ **Kategori/Library** — Browse salon per kategori layanan (rambut, facial, dll)  
✅ **Detail Salon** — Profil lengkap salon dengan rating, review, staff, jam operasional  
✅ **Booking** — 3-step booking form (pilih layanan → pilih waktu → konfirmasi)  
✅ **Akun User** — Dashboard dengan booking history, favorit, rewards  
✅ **Components Reusable** — Navbar, footer, salon cards, logo  

**Data Source**: 2.400+ salon dari Treatwell UK (JSON di `/database/data/`)

---

## 📂 Struktur Folder Update

```
update/
├── README.md                                    ← Installation guide awal
├── app/Http/Controllers/                        
│   ├── HomeController.php                       ← Homepage (featured salons)
│   ├── SearchController.php                     ← Search salon + layanan
│   ├── KategoriController.php                   ← Browse per kategori
│   ├── SalonController.php                      ← Detail salon
│   ├── BookingController.php                    ← Form & simpan booking
│   ├── AkunController.php                       ← Dashboard akun, bookings, profil
│   ├── GiftCardController.php                   ← Gift card (stub)
│   ├── LookbookController.php                   ← Lookbook gallery (stub)
│   ├── TreatmentFilesController.php             ← Treatment files (stub)
│   └── MitraController.php                      ← Partner program (stub)
├── routes/
│   └── web.php                                  ← Semua public & auth routes
└── resources/views/
    ├── layouts/
    │   └── public.blade.php                     ← Layout wrapper utama
    ├── components/
    │   ├── viygo-logo.blade.php                 ← Logo dengan cross-fade Alpine.js
    │   ├── viygo-navbar.blade.php               ← Navbar 2-baris (Treatwell-style)
    │   ├── viygo-footer.blade.php               ← Footer
    │   └── salon-card.blade.php                 ← Reusable salon card (list/grid)
    ├── home.blade.php                           ← Homepage
    ├── cari/
    │   └── index.blade.php                      ← Search results page
    ├── kategori/
    │   └── show.blade.php                       ← Category library view
    ├── salon/
    │   └── show.blade.php                       ← Salon detail page
    ├── booking/
    │   ├── create.blade.php                     ← 3-step booking form
    │   └── konfirmasi.blade.php                 ← Confirmation message
    ├── akun/
    │   ├── index.blade.php                      ← Account dashboard
    │   ├── bookings.blade.php                   ← Booking history (3 tabs)
    │   ├── favorit.blade.php                    ← Favorite salons/services
    │   ├── pengaturan.blade.php                 ← Edit profile
    │   └── reward.blade.php                     ← VIYGO Rewards program
    ├── gift-card/
    │   └── index.blade.php
    ├── lookbook/
    │   └── index.blade.php
    ├── treatment-files/
    │   └── index.blade.php
    └── mitra/
        └── index.blade.php
```

---

## 📊 Analisis Data & Model

### Data JSON di `/database/data/`

**1. salon.json** (343 MB, ~2.400 salons)
```json
{
  "id_salon": 1,
  "id_user": 1,
  "id_kota": 1,
  "nama_salon": "Novoblanc London",
  "alamat": "Unit 3, Brentford Lock, High Street, Brentford, TW8 8AQ",
  "deskripsi": "Professional beauty salon",
  "phone_number": null,
  "opening_time": "09:00",
  "closing_time": "18:00",
  "image_url": "https://cdn1.treatwell.net/images/...",
  "maps_url": "https://www.google.com/maps?q=...",
  "latitude": 51.4828532,
  "longitude": -0.309824,
  "rating": 5.0,
  "total_review": 7,
  "status": "active",
  "source_url": "https://www.treatwell.co.uk/place/..."
}
```

**Field Penting:**
- `id_salon` — Primary key
- `id_user` — FK ke owner salon
- `id_kota` — FK ke lokasi
- `slug` — **HARUS DITAMBAH** untuk URL-friendly routing
- `opening_time`, `closing_time` — Jam operasional
- `rating`, `total_review` — Social proof
- `status` — active/inactive

**2. service.json** (>50 MB, structured query)
```json
{
  "id_service": 1,
  "id_salon": 1,
  "id_kategori": 1,
  "nama": "Haircut",
  "deskripsi": "Professional haircut",
  "harga": 5000,
  "durasi": 30,
  "status": "active"
}
```

**Field Penting:**
- `id_kategori` — Link ke treatment category
- `harga`, `durasi` — Untuk booking & pricing
- `status` — Filter di queries

**3. kategori.json** (90 KB, ~450 categories)
```json
{
  "id_kategori": 1,
  "name": "Ladies' - Haircuts & Hairdressing",
  "deskripsi": "Services related to...",
  "slug": "ladies-haircuts-hairdressing",
  "icon_url": null,
  "is_active": true
}
```

**Field Penting:**
- `slug` — URL routing (`/kategori/{slug}`)
- Semua field sudah ada, tidak perlu migrasi

**4. kota.json** (7.8 KB, cities/areas)
```json
{
  "id_kota": 1,
  "nama": "London",
  "negara": "United Kingdom",
  "latitude": 51.5074,
  "longitude": -0.1278
}
```

**5. staff.json** (75 KB, stylists/therapists)
```json
{
  "id_staff": 1,
  "id_salon": 1,
  "nama": "John Doe",
  "spesialisasi": "Hair Styling",
  "rating": 4.8,
  "total_review": 45,
  "photo_url": "...",
  "status": "active"
}
```

**6. salon_images.json** (515 MB, gallery images)
```json
{
  "id_image": 1,
  "id_salon": 1,
  "url": "https://cdn1.treatwell.net/images/...",
  "is_primary": true,
  "kategori": "interior/staff/result"
}
```

---

## 🔗 Relasi Antar Tabel

```
User (id_user)
├── 1:N Salon (id_user → owner)
│   ├── 1:N Service (id_salon)
│   │   └── N:1 Kategori (id_kategori)
│   ├── 1:N Staff (id_salon)
│   ├── 1:N SalonImage (id_salon)
│   └── 1:N Review (id_salon)
└── 1:N Order (id_user → customer)
    └── 1:N OrderDetail (id_order)
        └── N:1 Service (id_service)

Kategori (id_kategori)
└── 1:N Service (id_kategori)

Kota (id_kota)
└── 1:N Salon (id_kota)
```

---

## 🎨 Frontend Components

### 1. **viygo-navbar.blade.php** 
**Navbar 2-baris style Treatwell:**
- Baris 1: Logo + Search bar + Akun link
- Baris 2: Category navigation links (Rambut, Facial, Pijat, dll)
- Sticky top dengan shadow
- Search dengan auto-complete (perlu JavaScript)

**Props**: None (menggunakan `request()` helpers)

**Dependencies**:
- Alpine.js (untuk interaktif)
- Tailwind CSS (styling)
- SVG icons (built-in)

### 2. **viygo-logo.blade.php**
**Cross-fade logo animation:**
- Displays 2 logos alternating dengan fade effect
- Default: VIYGO text + icon
- Responsive sizing
- Alpine.js untuk animasi

**Props**: None  
**Dependencies**: Alpine.js, Tailwind CSS

### 3. **salon-card.blade.php**
**Reusable salon card component:**
- Props: `$salon` (Salon model), `$layout` ('list' atau 'grid')
- **List layout** (default): Treatwell-style dengan foto 48x36, info, rating, services
- **Grid layout**: Versi compact untuk gallery
- Favorite button (interaktif dengan Alpine.js)
- Primary image fallback ke `$salon->image_url`

**Data Requirement:**
```php
$salon->slug              // URL routing
$salon->nama_salon        // Salon name
$salon->rating            // Float 1-5
$salon->total_review      // Integer
$salon->kota->nama        // Location
$salon->services          // Collection of Services
$salon->primaryImage->url // Main image
```

### 4. **public.blade.php**
**Master layout untuk semua halaman publik:**
- Navbar + content + footer
- Meta tags (SEO)
- CSS/JS loading
- `@slot('title')` untuk page titles
- Tailwind + Alpine.js setup

---

## 🛣️ Routes & Controllers

### Routes yang Ditambahkan (di `/update/routes/web.php`)

```
GET  /                        → HomeController@index
GET  /cari                    → SearchController@index
GET  /kategori/{slug}         → KategoriController@show
GET  /salon/{slug}            → SalonController@show
GET  /gift-card               → GiftCardController@index
GET  /lookbook                → LookbookController@index
GET  /treatment-files         → TreatmentFilesController@index
GET  /mitra                   → MitraController@index

[AUTH PROTECTED]
GET  /booking/{salon_slug}    → BookingController@create
POST /booking/{salon_slug}    → BookingController@store
GET  /akun                    → AkunController@index
GET  /akun/bookings           → AkunController@bookings
GET  /akun/favorit            → AkunController@favorit
GET  /akun/pengaturan         → AkunController@pengaturan
PUT  /akun/pengaturan         → AkunController@updatePengaturan
GET  /akun/reward             → AkunController@reward
POST /logout                  → LogoutController@logout
```

### Controller Analysis

#### **HomeController**
```php
// Menampilkan 8 salon terbaik (sorted by rating)
// Eager load: kota, services.kategori, primaryImage, images, reviews count

public function index() {
    $salons = Salon::active()
        ->with(['kota', 'services.kategori', 'primaryImage', 'images'])
        ->withCount('reviews')
        ->orderByDesc('rating')
        ->take(8)
        ->get();
    return view('home', compact('salons'));
}
```

**Queries**: 1 query + N+1 prevention via eager load  
**Response**: View dengan `$salons`

#### **SearchController**
```php
// Full-text search dengan filtering
// Filter by: service name (q) + location (lokasi)
// Sort by: rating, harga, atau terbaru

public function index(Request $request) {
    $q      = $request->input('q', '');
    $lokasi = $request->input('lokasi', '');

    $salons = Salon::active()
        ->with(['kota', 'services.kategori', 'primaryImage'])
        ->when($q, fn ($q) => $q->where('nama_salon', 'like', "%{$q}%")
                               ->orWhereHas('services', fn ($s) => $s->where('nama', 'like', "%{$q}%")))
        ->when($lokasi, fn ($q) => $q->whereHas('kota', fn ($k) => $k->where('nama', 'like', "%{$lokasi}%")))
        ->when(request('sort') === 'rating-tertinggi', fn ($q) => $q->orderByDesc('rating'))
        ->paginate(10)
        ->withQueryString();

    return view('cari.index', compact('salons'));
}
```

**Query Optimization**: Gunakan `select()` untuk limit columns  
**Performance Note**: `orWhereHas` bisa slow di dataset besar, pertimbangkan full-text index

#### **KategoriController**
```php
// Browse salon per kategori dengan sorting/filtering

public function show(string $slug) {
    $kategori = Kategori::active()->where('slug', $slug)->firstOrFail();

    $salons = Salon::active()
        ->whereHas('services', fn ($q) => $q->where('id_kategori', $kategori->id_kategori))
        ->with(['kota', 'services' => fn ($q) => $q->where('id_kategori', $kategori->id_kategori), 'primaryImage'])
        ->when(request('sort') === 'rating-tertinggi', fn ($q) => $q->orderByDesc('rating'))
        ->paginate(10)
        ->withQueryString();

    return view('kategori.show', compact('kategori', 'salons'));
}
```

**Key Feature**: Filter services hanya yang sesuai kategori  
**Performance**: Selective eager load (only services in category)

#### **SalonController**
```php
// Detail salon dengan support slug + id fallback

public function show(string $slug) {
    $salon = Salon::active()
        ->where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('id_salon', $slug);
        })
        ->with([
            'kota',
            'images',
            'primaryImage',
            'services' => fn ($q) => $q->active()->with('kategori'),
            'reviews'  => fn ($q) => $q->with('user')->latest()->take(10),
            'staff',
        ])
        ->firstOrFail();

    return view('salon.show', compact('salon'));
}
```

**Note**: Support both `slug` dan `id_salon` untuk backward compatibility  
**Load**: Reviews terbaru 10, sorted DESC

#### **BookingController**
```php
// Form booking (create) + simpan order (store)

// Step 1: Tampilkan salon + services
public function create(string $slug) { ... }

// Step 2: Validate & create order + order details
public function store(Request $request, string $slug) {
    // Buat Order record dengan status 'pending'
    // Buat OrderDetail record dengan id_service + qty + harga
    // Generate kode_order unik: VYG-{8 random chars}
}
```

**Important**: Perlu field `catatan` di OrderDetail model  
**Transaction**: Gunakan DB::transaction() untuk consistency

#### **AkunController**
```php
// Dashboard akun, booking history, profil

public function index() {
    // Hitung pending orders untuk badge
    $upcomingCount = Order::where('id_user', auth()->id())
        ->where('status', 'pending')
        ->count();
    return view('akun.index', compact('upcomingCount'));
}

public function bookings(Request $request) {
    // 3 tabs: mendatang (pending), selesai (success), dibatalkan (canceled)
    $statusMap = ['mendatang' => 'pending', 'selesai' => 'success', 'dibatalkan' => 'canceled'];
    $orders = Order::where('id_user', auth()->id())
        ->when($tab, fn ($q) => $q->where('status', $statusMap[$tab]))
        ->with(['salon.kota', 'details.service'])
        ->latest()
        ->paginate(10);
    return view('akun.bookings', compact('orders', 'tab'));
}

public function favorit() { ... }
public function pengaturan() { ... }
public function updatePengaturan(Request $request) { ... }
```

---

## ✅ Prasyarat Integrasi

Pastikan semua kondisi terpenuhi sebelum mulai integrasi:

### **Database**
- [x] `salon` table dengan field: `id_salon, id_user, id_kota, nama_salon, alamat, deskripsi, phone_number, opening_time, closing_time, image_url, maps_url, latitude, longitude, rating, total_review, status, source_url`
- [x] `service` table dengan field: `id_service, id_salon, id_kategori, nama, deskripsi, harga, durasi, status`
- [x] `kategori` table dengan field: `id_kategori, name, deskripsi, slug, icon_url, is_active`
- [x] `kota` table dengan field: `id_kota, nama, negara, latitude, longitude`
- [x] `staff` table dengan field: `id_staff, id_salon, nama, spesialisasi, rating, total_review, photo_url, status`
- [x] `salon_image` table dengan field: `id_image, id_salon, url, is_primary, kategori`
- [x] `order` table dengan field: `id_order, id_user, id_salon, kode_order, date_order, total_pembayaran, status` 
- [x] `order_detail` table dengan field: `id_order, id_service, qty, harga, catatan` ← **PERLU PERHATIAN: field `catatan`**
- [x] `review` table dengan field: `id_review, id_salon, id_user, rating, review_text, created_at`

### **Models Harus Ada**
- [x] `App\Models\Salon` dengan relations: `belongsTo(User), belongsTo(Kota), hasMany(Service), hasMany(Staff), hasMany(SalonImage), hasMany(Review), hasMany(Order)`
- [x] `App\Models\Service` dengan relations: `belongsTo(Salon), belongsTo(Kategori)`
- [x] `App\Models\Kategori` dengan relations: `hasMany(Service)`
- [x] `App\Models\Kota` dengan relations: `hasMany(Salon)`
- [x] `App\Models\Staff` dengan relations: `belongsTo(Salon)`
- [x] `App\Models\SalonImage` dengan relations: `belongsTo(Salon)`
- [x] `App\Models\Order` dengan relations: `belongsTo(User), belongsTo(Salon), hasMany(OrderDetail)`
- [x] `App\Models\OrderDetail` dengan relations: `belongsTo(Order), belongsTo(Service)`
- [x] `App\Models\Review` dengan relations: `belongsTo(Salon), belongsTo(User)`

### **Migrations (jika belum ada)**
- [ ] Migration untuk menambah `slug` ke table `salon` (CRITICAL!)
- [ ] Migration untuk menambah `slug` ke table `kategori` (jika belum ada)
- [ ] Migration untuk field `catatan` di table `order_detail`
- [ ] Migration untuk field `is_primary` di table `salon_image`

### **Frontend Assets**
- [ ] Tailwind CSS sudah dikonfigurasi (verify: `tailwind.config.js`)
- [ ] Alpine.js sudah included di layout utama (verify: `resources/views/layouts/app.blade.php`)
- [ ] Font "DM Serif Display" sudah loaded via Google Fonts atau local
- [ ] SVG icons built-in (tidak perlu external library)

### **Authentication**
- [x] Laravel Fortify sudah setup untuk registrasi/login
- [x] `auth()` helper berfungsi dengan baik
- [x] User model memiliki relationship yang tepat

### **Env Variables**
```
APP_URL=http://localhost:8000 (or production URL)
DB_CONNECTION=mysql
DB_DATABASE=viygo
```

---

## 🔧 Langkah-Langkah Integrasi (Lengkap)

### **FASE 1: Database Preparation**

#### Step 1.1 - Cek Field `slug` di Salon
```bash
# Di terminal, check current schema
php artisan tinker
>>> Schema::getColumnListing('salon')
>>> // Jika slug belum ada, lanjut ke 1.2
```

#### Step 1.2 - Buat Migration untuk `slug` (jika belum ada)
```bash
php artisan make:migration add_slug_to_salon_table --table=salon
```

Edit file migration (cari di `/database/migrations/`):
```php
public function up(): void
{
    Schema::table('salon', function (Blueprint $table) {
        $table->string('slug')->unique()->nullable()->after('nama_salon');
    });
}

public function down(): void
{
    Schema::table('salon', function (Blueprint $table) {
        $table->dropColumn('slug');
    });
}
```

#### Step 1.3 - Populate `slug` dari `nama_salon`
```bash
php artisan tinker
>>> use App\Models\Salon;
>>> use Illuminate\Support\Str;
>>> Salon::all()->each(fn ($s) => $s->update(['slug' => Str::slug($s->nama_salon)]));
>>> exit
```

#### Step 1.4 - Pastikan Kategori punya `slug`
```bash
php artisan tinker
>>> Schema::getColumnListing('kategori')
>>> // Jika slug ada dan terisi, lanjut ke step 1.5
```

#### Step 1.5 - Cek field `catatan` di `order_detail`
```bash
php artisan tinker
>>> Schema::getColumnListing('order_detail')
>>> // Jika belum ada, buat migration baru
```

Jika tidak ada:
```bash
php artisan make:migration add_catatan_to_order_detail_table --table=order_detail
```

Edit migration:
```php
public function up(): void
{
    Schema::table('order_detail', function (Blueprint $table) {
        $table->text('catatan')->nullable()->after('harga');
    });
}
```

#### Step 1.6 - Jalankan Migrations
```bash
php artisan migrate
```

---

### **FASE 2: Model Updates**

#### Step 2.1 - Update Model `Salon`

**Lokasi**: `app/Models/Salon.php`

**Tambahkan/Verifikasi di class**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Salon extends Model
{
    use SoftDeletes;

    protected $table = 'salon';
    protected $primaryKey = 'id_salon';

    protected $fillable = [
        'id_user',
        'id_kota',
        'nama_salon',
        'slug',              // ← ADD JIKA BELUM ADA
        'alamat',
        'deskripsi',
        'phone_number',
        'opening_time',
        'closing_time',
        'image_url',
        'maps_url',
        'latitude',
        'longitude',
        'rating',
        'total_review',
        'status',
        'source_url',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ── Relations ─────────────────────────────────────────────────────────
    public function kota()
    {
        return $this->belongsTo(Kota::class, 'id_kota', 'id_kota');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'id_salon', 'id_salon');
    }

    public function staff()
    {
        return $this->hasMany(Staff::class, 'id_salon', 'id_salon');
    }

    public function images()
    {
        return $this->hasMany(SalonImage::class, 'id_salon', 'id_salon');
    }

    public function primaryImage()
    {
        return $this->hasOne(SalonImage::class, 'id_salon', 'id_salon')
                    ->where('is_primary', true)
                    ->latest();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'id_salon', 'id_salon');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'id_salon', 'id_salon');
    }
}
```

#### Step 2.2 - Update Model `Kategori`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'kategori';
    protected $primaryKey = 'id_kategori';

    protected $fillable = [
        'name',
        'deskripsi',
        'slug',
        'icon_url',
        'is_active',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'id_kategori', 'id_kategori');
    }
}
```

#### Step 2.3 - Verify Model `Service`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'service';
    protected $primaryKey = 'id_service';

    protected $fillable = [
        'id_salon',
        'id_kategori',
        'nama',
        'deskripsi',
        'harga',
        'durasi',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class, 'id_salon', 'id_salon');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }
}
```

#### Step 2.4 - Verify Model `Order` & `OrderDetail`
```php
// Order model
public function orderDetails()
{
    return $this->hasMany(OrderDetail::class, 'id_order', 'id_order');
}

public function salon()
{
    return $this->belongsTo(Salon::class, 'id_salon', 'id_salon');
}

// OrderDetail model
public function order()
{
    return $this->belongsTo(Order::class, 'id_order', 'id_order');
}

public function service()
{
    return $this->belongsTo(Service::class, 'id_service', 'id_service');
}
```

#### Step 2.5 - Verify Model `SalonImage`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonImage extends Model
{
    protected $table = 'salon_image';
    protected $primaryKey = 'id_image';

    protected $fillable = [
        'id_salon',
        'url',
        'is_primary',
        'kategori',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class, 'id_salon', 'id_salon');
    }
}
```

---

### **FASE 3: Controllers & Routes**

#### Step 3.1 - Copy Controllers dari `/update/app/Http/Controllers/`

**File-file untuk copy**:
- `HomeController.php`
- `SearchController.php`
- `KategoriController.php`
- `SalonController.php`
- `BookingController.php`
- `AkunController.php`
- `GiftCardController.php`
- `LookbookController.php`
- `TreatmentFilesController.php`
- `MitraController.php`

**Copy via PowerShell**:
```powershell
# Dari root project
Copy-Item "update/app/Http/Controllers/*.php" "app/Http/Controllers/" -Force
```

**Atau manual**: Salin satu per satu dari `/update/` ke `app/Http/Controllers/`

#### Step 3.2 - Merge Routes

**Lokasi**: `routes/web.php`

**Current routes/web.php** mungkin punya:
```php
Route::get('/admin', [AdminController::class, 'dashboard']);
// ... admin routes
```

**Tambahkan di akhir file, sebelum closing brace**:
```php
/*
|--------------------------------------------------------------------------
| VIYGO Public Frontend Routes
|--------------------------------------------------------------------------
*/

// Publik routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/cari', [SearchController::class, 'index'])->name('cari');
Route::get('/kategori/{slug}', [KategoriController::class, 'show'])->name('kategori.show');
Route::get('/salon/{slug}', [SalonController::class, 'show'])->name('salon.show');
Route::get('/gift-card', [GiftCardController::class, 'index'])->name('gift-card');
Route::get('/lookbook', [LookbookController::class, 'index'])->name('lookbook');
Route::get('/treatment-files', [TreatmentFilesController::class, 'index'])->name('treatment-files');
Route::get('/mitra', [MitraController::class, 'index'])->name('mitra');

// Auth protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/booking/{slug}', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/booking/{slug}', [BookingController::class, 'store'])->name('booking.store');
    
    Route::prefix('akun')->group(function () {
        Route::get('', [AkunController::class, 'index'])->name('akun.index');
        Route::get('/bookings', [AkunController::class, 'bookings'])->name('akun.bookings');
        Route::get('/favorit', [AkunController::class, 'favorit'])->name('akun.favorit');
        Route::get('/pengaturan', [AkunController::class, 'pengaturan'])->name('akun.pengaturan');
        Route::put('/pengaturan', [AkunController::class, 'updatePengaturan'])->name('akun.pengaturan.update');
        Route::get('/reward', [AkunController::class, 'reward'])->name('akun.reward');
    });
});
```

**Jangan lupa tambah imports di atas file**:
```php
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\LookbookController;
use App\Http\Controllers\TreatmentFilesController;
use App\Http\Controllers\MitraController;
```

---

### **FASE 4: Views & Components**

#### Step 4.1 - Copy Layout & Components

**Dari `/update/resources/views/`** ke `resources/views/`:

```powershell
# Copy layout
Copy-Item "update/resources/views/layouts/public.blade.php" "resources/views/layouts/" -Force

# Copy components
Copy-Item "update/resources/views/components/viygo-*.blade.php" "resources/views/components/" -Force
Copy-Item "update/resources/views/components/salon-card.blade.php" "resources/views/components/" -Force

# Copy pages
Copy-Item "update/resources/views/home.blade.php" "resources/views/" -Force
Copy-Item "update/resources/views/cari" "resources/views/" -Recurse -Force
Copy-Item "update/resources/views/kategori" "resources/views/" -Recurse -Force
Copy-Item "update/resources/views/salon" "resources/views/" -Recurse -Force
Copy-Item "update/resources/views/booking" "resources/views/" -Recurse -Force
Copy-Item "update/resources/views/akun" "resources/views/" -Recurse -Force
Copy-Item "update/resources/views/gift-card" "resources/views/" -Recurse -Force
Copy-Item "update/resources/views/lookbook" "resources/views/" -Recurse -Force
Copy-Item "update/resources/views/treatment-files" "resources/views/" -Recurse -Force
Copy-Item "update/resources/views/mitra" "resources/views/" -Recurse -Force
```

#### Step 4.2 - Verify Layout & Asset Dependencies

**File**: `resources/views/layouts/public.blade.php`

**Harus include**:
```html
<!-- Tailwind CSS -->
@vite(['resources/css/app.css'])

<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
```

#### Step 4.3 - Verify Tailwind Config

**File**: `tailwind.config.js`

Harus punya:
```javascript
module.exports = {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        // Warna VIYGO brand
        'viygo-dark': '#1B2D6B',
        'viygo-blue': '#4BA3CC',
        'viygo-light': '#E8F4FB',
      },
    },
  },
  plugins: [],
}
```

---

### **FASE 5: Testing & Validation**

#### Step 5.1 - Clear Cache & Rebuild Assets
```bash
php artisan cache:clear
php artisan config:clear
npm run build
# or
php artisan vite:install
```

#### Step 5.2 - Jalankan Development Server
```bash
php artisan serve
# Terminal baru:
npm run dev
```

#### Step 5.3 - Test Routes
```
✓ http://localhost:8000/                        → Homepage
✓ http://localhost:8000/cari?q=potong&lokasi=jakarta
✓ http://localhost:8000/kategori/ladies-haircuts-hairdressing
✓ http://localhost:8000/salon/novoblanc-london  (jika slug ada di data)
✓ http://localhost:8000/booking/novoblanc-london (harus login)
✓ http://localhost:8000/akun (harus login)
```

#### Step 5.4 - Database Seeding (opsional tapi recommended)
```bash
# Jika data belum ada, seed dari JSON
php artisan db:seed SalonSeeder
```

---

## ✔️ Validasi Setelah Integrasi

Setelah integrasi selesai, pastikan:

### **Database**
- [ ] `salon` table punya `slug` & terisi
- [ ] `kategori` table punya `slug` & terisi
- [ ] `order_detail` punya field `catatan`
- [ ] Semua relasi FK berfungsi

### **Frontend Routes**
- [ ] Homepage render dengan 8 featured salons
- [ ] Search bekerja (keyword + lokasi)
- [ ] Kategori show page load dengan pagination
- [ ] Salon detail page menampilkan services, reviews, staff, images
- [ ] Booking form hanya accessible jika logged in
- [ ] Akun dashboard menampilkan booking history & rewards

### **Models & Relations**
- [ ] `Salon::find(1)->services` return Services collection
- [ ] `Service::find(1)->kategori` return Kategori instance
- [ ] `Salon::find(1)->primaryImage` return SalonImage atau null
- [ ] `Order::find(1)->orderDetails` return OrderDetail collection

### **Components**
- [ ] Navbar render di setiap page
- [ ] Navbar search form berfungsi
- [ ] Salon card display (list + grid layout)
- [ ] Logo cross-fade animation berjalan
- [ ] Footer render dengan correct links

### **CSS/JS**
- [ ] Tailwind classes applied correctly
- [ ] Alpine.js directives working (`x-data`, `x-show`, etc)
- [ ] Responsive design working (mobile/tablet/desktop)
- [ ] Fonts loaded (DM Serif Display untuk headings)

### **Performance**
- [ ] Lazy load images
- [ ] No N+1 queries (check Laravel Debugbar)
- [ ] Page load < 3s
- [ ] Search results load within 1s

---

## 🐛 Troubleshooting

### **Error 1: `Class HomeController not found`**
```
Solusi:
- Pastikan HomeController.php sudah di app/Http/Controllers/
- Cek use statement di routes/web.php
- Jalankan: php artisan composer:dump-autoload
```

### **Error 2: `Column not found: 1054 Unknown column 'salon.slug'`**
```
Solusi:
- Jalankan: php artisan migrate
- Verify dengan: php artisan tinker > Schema::getColumnListing('salon')
- Populate slug jika NULL: Salon::all()->each(fn ($s) => $s->update(['slug' => Str::slug($s->nama_salon)]))
```

### **Error 3: `Relationship method services does not exist`**
```
Solusi:
- Verify model Salon punya relation: public function services()
- Check: public function salon() di Service model
- Run: php artisan tinker > App\Models\Salon::first()->services()->get()
```

### **Error 4: `Route [salon.show] not defined`**
```
Solusi:
- Verify routes/web.php punya: Route::get('/salon/{slug}', ...)->name('salon.show');
- Jalankan: php artisan route:list | grep salon
- Clear cache: php artisan route:cache
```

### **Error 5: Navbar/Components tidak render**
```
Solusi:
- Pastikan layout public.blade.php ada di resources/views/layouts/
- Verify child views menggunakan: <x-layouts.public>
- Check Alpine.js loaded: browser DevTools → Scripts tab → cari alpine
```

### **Error 6: Styling tidak muncul**
```
Solusi:
- Run: npm run build (atau npm run dev untuk watch mode)
- Verify tailwind.config.js content path benar
- Clear browser cache: Ctrl+Shift+Delete
- Jalankan: php artisan vite:install
```

### **Error 7: CORS/Asset issues**
```
Solusi:
- Verify APP_URL di .env sesuai domain
- Jalankan: php artisan storage:link (jika ada local storage)
- Check public/index.php exist dan readable
```

### **Error 8: Booking tidak simpan**
```
Solusi:
- Verify OrderDetail model punya field 'catatan'
- Check Order model punya relation: hasMany(OrderDetail)
- Jalankan: php artisan migrate (jika migration pending)
- Test: php artisan tinker > Order::create([...])
```

### **Error 9: Images tidak show**
```
Solusi:
- Verify SalonImage model & primaryImage relation
- Check URL di salon_images.json valid (accessible)
- Jika using local storage: php artisan storage:link
- Fallback emoji/icon appear: good sign, image URL invalid
```

### **Error 10: Search slow/timeout**
```
Solusi:
- Add index ke columns: 'nama_salon', 'id_kategori'
- Migration: $table->index('nama_salon')
- Optimize query: use select() untuk limit columns
- Consider: Full-text search index atau Elasticsearch
```

---

## 📌 Summary Integration Checklist

```
[ ] Database migrations (slug, catatan fields)
[ ] Models updated (relations, scopes)
[ ] Controllers copied ke app/Http/Controllers/
[ ] Routes merged ke routes/web.php
[ ] Views copied ke resources/views/
[ ] Components copied ke resources/views/components/
[ ] Tailwind config verified
[ ] Layout public.blade.php ada
[ ] Alpine.js & fonts included
[ ] npm run build executed
[ ] Cache cleared (php artisan cache:clear)
[ ] Test homepage loads
[ ] Test search functionality
[ ] Test kategori filtering
[ ] Test salon detail page
[ ] Test booking form (auth required)
[ ] Test akun dashboard
[ ] Verify images load correctly
[ ] Check database seeding complete
[ ] Performance check (no N+1 queries)
[ ] Mobile responsive test
```

---

## 🚀 Setelah Integration Complete

1. **Monitoring**: Setup Laravel Telescope untuk debug
2. **Caching**: Implement Redis caching untuk search results
3. **Analytics**: Setup Google Analytics tracking
4. **SEO**: Add meta tags ke views, setup sitemap
5. **Testing**: Create feature tests untuk critical flows
6. **Deployment**: Setup CI/CD pipeline (GitHub Actions)

---

**Document Version**: 1.0  
**Last Updated**: May 1, 2026  
**Status**: ✅ Ready for LLM Integration  

---
