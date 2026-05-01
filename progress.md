# VIYGO — Progress Tracker: Duplikasi Treatwell.co.uk

> **Update terakhir:** 27 April 2026 — 22:00 WIB
> **Status branch aktif:** `go-fresh` (up-to-date dengan remote)
> **Referensi:** [treatwell.co.uk](https://www.treatwell.co.uk)

---

## 📊 Ringkasan Progress Keseluruhan

| Fase | Area | Status | Estimasi % |
|------|------|--------|-----------|
| ✅ | Infrastruktur & Setup | **SELESAI** | 100% |
| ✅ | Database Schema | **SELESAI** | 100% |
| ✅ | Data Scraping & Seeding | **SELESAI** | 95% |
| ✅ | Model Eloquent (13 model) | **SELESAI** | 100% |
| 🔴 | Backend / Controllers | **BELUM** | 0% |
| 🔴 | Frontend — Landing Page | **DEFAULT LARAVEL** | 5% |
| 🔴 | Frontend — Search & Filter | **BELUM** | 0% |
| 🔴 | Frontend — Halaman Salon | **BELUM** | 0% |
| 🔴 | Frontend — Booking Flow | **BELUM** | 0% |
| 🟡 | Auth — Login/Register | **SCAFFOLD ADA** | 30% |
| 🔴 | Dashboard User | **BELUM** | 0% |
| 🔴 | Dashboard Salon Owner | **BELUM** | 0% |
| 🔴 | Admin Panel | **BELUM** | 0% |
| 🔴 | Review & Rating | **BELUM** | 0% |
| 🔴 | Payment Flow | **BELUM** | 0% |

**Estimasi progress total: ~27%**

---

## ✅ SUDAH SELESAI

### 1. Infrastruktur Proyek
- [x] Laravel 13 + Livewire Flux starter kit terinstal
- [x] Konfigurasi `composer.json`, `package.json`, `vite.config.js`
- [x] `.env` dikonfigurasi (`DB_DATABASE=viygo-go`)
- [x] Git repository aktif, branch `go-fresh` sync dengan GitHub
- [x] TailwindCSS v4 terintegrasi via Vite

### 2. Database Schema (Migrasi)
- [x] `kota` — master data kota
- [x] `kategori` — kategori layanan
- [x] `users` — customer, salon owner, admin (+ 2FA columns, softDelete)
- [x] `salon` — profil salon (koordinat, jam buka, rating, soft delete)
- [x] `promo` — promo & diskon
- [x] `service` — layanan salon (harga, durasi, kategori)
- [x] `staff` — karyawan salon
- [x] `order` — transaksi booking
- [x] `review` — ulasan pelanggan
- [x] `salon_images` — galeri foto
- [x] `staff_schedule` — jadwal kerja staf
- [x] `staff_service` — pivot staf ↔ layanan
- [x] `user_promo` — pivot penggunaan promo
- [x] `order_detail` — detail layanan per order
- [x] `pembayaran` — record pembayaran
- [x] `sessions` — session table

### 3. Data Scraping & Seeding
- [x] Go scraper dibangun — concurrent, high-performance
- [x] Ekstraksi layanan via JSON-LD `hasOfferCatalog`
- [x] Data Hair: 1.011 salon, 42.961 layanan (UK)
- [x] Data Face: terscrape dan diimport
- [x] Data Nails: terscrape
- [x] Data Body: terscrape
- [x] JSON validator (`database/scripts/validate_json.php`)
- [x] Seluruh seeder idempotent (aman re-run)
  - `KotaSeeder`, `KategoriSeeder` (upsert by slug)
  - `UserSeeder`, `SalonSeeder`, `ServiceSeeder`
  - `StaffSeeder`, `SalonImagesSeeder`
- [x] `DatabaseSeeder` dengan urutan FK yang benar + truncate aman

**Data aktual di database (verified):**
| Tabel | Records |
|-------|---------|
| users | 5.769 |
| kota | 1.709 |
| kategori | 7.183 |
| salon | 5.767 |
| service | 190.594 |
| staff | 7.568 |
| salon_images | 50.492 |

### 4. Model Eloquent ✅ BARU SELESAI (27 Apr 2026)
- [x] `User.php` — diupdate: custom PK, relasi lengkap, SoftDeletes, accessor `fullName`
- [x] `Kota.php` — hasMany salons
- [x] `Kategori.php` — hasMany services, scope active
- [x] `Salon.php` — relasi lengkap (owner, kota, services, staff, images, orders, reviews, primaryImage), SoftDeletes, scope active/byKota
- [x] `Promo.php` — belongsToMany users (pivot), hasMany orders, scope active, SoftDeletes
- [x] `Service.php` — belongsTo salon & kategori, belongsToMany staff (pivot), SoftDeletes
- [x] `Staff.php` — belongsTo salon, hasMany schedules & orderDetails, belongsToMany services, SoftDeletes
- [x] `Order.php` — belongsTo user/salon/promo, hasMany details, hasOne review & pembayaran
- [x] `Review.php` — belongsTo user, salon, order; scope visible
- [x] `SalonImage.php` — belongsTo salon
- [x] `StaffSchedule.php` — belongsTo staff
- [x] `OrderDetail.php` — belongsTo order, service, staff (nullable)
- [x] `Pembayaran.php` — belongsTo order & user; scope completed/pending

**Verifikasi:** 13/13 model load OK ✅ | 40/40 relasi OK ✅ | 13/13 query count OK ✅

### 5. Auth Scaffold (Livewire Fortify)
- [x] Halaman login tersedia (`/login`)
- [x] Halaman register tersedia (`/register`)
- [x] Halaman settings tersedia (`/settings`)
- [x] Route auth dengan middleware `auth`, `verified`
- [x] 2FA (Two Factor Auth) — kolom tersedia di users

---

## 🔴 YANG PERLU DIKERJAKAN (Prioritas Tinggi → Rendah)

---

### 🔴 PRIORITAS 1 — Landing Page (welcome.blade.php)

**Status saat ini:** Masih halaman **default Laravel** — SVG logo Laravel, tidak ada konten VIYGO sama sekali.

**Referensi Treatwell:** https://www.treatwell.co.uk/

Treatwell homepage terdiri dari:

#### A. Navbar (Header)
- [ ] Logo VIYGO
- [ ] Search bar global (cari layanan + pilih kota)
- [ ] Tombol Login / Register
- [ ] Menu kategori: Hair, Nails, Face, Body, Massage

#### B. Hero Section
- [ ] Judul besar + tagline
- [ ] Search form: `[Jenis Layanan]` + `[Kota/Lokasi]` + `[Tombol Cari]`
- [ ] Foto hero / background gambar salon

#### C. Kategori Populer
- [ ] Grid ikon kategori: Hair, Nails, Massage, Face, Body, Brows & Lashes
- [ ] Klik → redirect ke halaman hasil pencarian

#### D. Salon Unggulan / Nearby
- [ ] Card salon: foto, nama, kota, rating, harga mulai dari
- [ ] Carousel atau grid (data dari DB sudah ada — 5.767 salon!)

#### E. Cara Kerja (How It Works)
- [ ] 3 langkah: Cari → Pilih → Booking

#### F. Footer
- [ ] Link navigasi, copyright, sosmed

**File yang perlu dibuat/diubah:**
```
resources/views/welcome.blade.php    ← UBAH TOTAL
resources/views/layouts/app.blade.php ← Tambahkan navbar + footer
resources/views/components/navbar.blade.php
resources/views/components/footer.blade.php
resources/views/components/salon-card.blade.php
resources/views/components/category-card.blade.php
```

**Controller:**
```bash
php artisan make:livewire HomePage
```

---

### 🔴 PRIORITAS 2 — Search & Filter Salon

**Referensi Treatwell:** https://www.treatwell.co.uk/place/london/

#### Fitur yang diperlukan:
- [ ] URL struktur: `/search?q=haircut&city=london`
- [ ] Filter sidebar: Kategori (checkbox), Harga range, Rating minimum, Jam buka
- [ ] Sort by: Relevansi, Rating, Harga
- [ ] Pagination (infinite scroll atau numbered)
- [ ] Jumlah hasil: "123 salon ditemukan"

**File yang perlu dibuat:**
```
app/Livewire/SalonSearch.php
resources/views/livewire/salon-search.blade.php
routes/web.php  ← Tambah route /search
```

**Query (Model sudah siap):**
```php
Salon::with(['kota', 'primaryImage'])
    ->when($this->category, fn($q) =>
        $q->whereHas('services.kategori', fn($q) =>
            $q->where('slug', $this->category)))
    ->when($this->city, fn($q) =>
        $q->whereHas('kota', fn($q) =>
            $q->where('nama_kota', 'like', "%{$this->city}%")))
    ->when($this->minRating, fn($q) =>
        $q->where('rating', '>=', $this->minRating))
    ->active()   // scope sudah ada di Salon model!
    ->paginate(12);
```

---

### 🔴 PRIORITAS 3 — Halaman Detail Salon

**Referensi Treatwell:** https://www.treatwell.co.uk/place/[salon-name]/

#### Konten yang diperlukan:
- [ ] Galeri foto salon (carousel) — data `salon_images` sudah ada (50k+)
- [ ] Nama salon, alamat, jam buka, rating
- [ ] Tab: Layanan | Staf | Review | Info
- [ ] Daftar layanan dengan harga & durasi (grouped by kategori)
- [ ] Profil staf dengan foto dan spesialisasi
- [ ] Peta lokasi salon (koordinat sudah ada di DB)
- [ ] Tombol "Pesan Sekarang" → redirect ke booking flow
- [ ] Review card (belum ada data review)

**File yang perlu dibuat:**
```
app/Livewire/SalonDetail.php
resources/views/livewire/salon-detail.blade.php
resources/views/components/service-list.blade.php
resources/views/components/staff-card.blade.php
resources/views/components/review-card.blade.php
resources/views/components/image-gallery.blade.php
```

**Route:**
```php
Route::get('/salon/{id_salon}', SalonDetail::class)->name('salon.detail');
```

---

### 🔴 PRIORITAS 4 — Booking Flow

**Referensi Treatwell:** https://www.treatwell.co.uk/book/[salon]/

#### Langkah booking (multi-step wizard):

**Step 1: Pilih Layanan**
- [ ] Daftar layanan salon (multi-select)
- [ ] Tampilkan durasi + harga, hitung total

**Step 2: Pilih Staf**
- [ ] List staf yang tersedia untuk layanan terpilih
- [ ] Opsi "Staf Mana Saja"

**Step 3: Pilih Tanggal & Waktu**
- [ ] Kalender pilih tanggal
- [ ] Slot waktu berdasarkan jadwal staf + cek double-booking

**Step 4: Konfirmasi & Bayar**
- [ ] Ringkasan pesanan
- [ ] Input kode promo (cek `user_promo`)
- [ ] Pilih metode pembayaran
- [ ] Tombol "Konfirmasi Booking"

**File yang perlu dibuat:**
```
app/Livewire/Booking/StepService.php
app/Livewire/Booking/StepStaff.php
app/Livewire/Booking/StepDateTime.php
app/Livewire/Booking/StepConfirm.php
resources/views/livewire/booking/*.blade.php
app/Http/Controllers/OrderController.php
```

**Logic slot availability:**
```php
// Cek staff tidak double-booked
OrderDetail::whereHas('order', fn($q) => $q->where('date_order', $date))
    ->where('id_staff', $staffId)
    ->where('start_time', '<', $timeEnd)
    ->where('end_time', '>', $timeStart)
    ->doesntExist();
```

---

### 🔴 PRIORITAS 5 — Dashboard User (Customer)

- [ ] Riwayat booking (upcoming & past) — query `Order` by `id_user`
- [ ] Tombol batalkan booking (jika status = pending)
- [ ] Tombol tulis review (jika status = success, belum ada review)
- [ ] Profil user (edit `first_name`, `last_name`, `phone_number`, `profile_url`)
- [ ] Promo / voucher yang dimiliki (via pivot `user_promo`)

---

### 🔴 PRIORITAS 6 — Dashboard Salon Owner

- [ ] Statistik salon (total booking hari ini/bulan ini)
- [ ] Manajemen layanan (CRUD `service`)
- [ ] Manajemen staf (CRUD `staff` + `staff_schedule`)
- [ ] Daftar order masuk (update status)
- [ ] Manajemen galeri foto (`salon_images`)
- [ ] Edit profil salon

---

### 🔴 PRIORITAS 7 — Admin Panel

- [ ] Manajemen semua salon (approve/reject `status = active`)
- [ ] Manajemen kategori & kota
- [ ] Manajemen promo global
- [ ] Laporan & statistik platform
- [ ] Moderasi review (`is_visible`)

---

### 🔴 PRIORITAS 8 — Middleware & Role-Based Access

- [ ] Middleware `CheckRole` untuk `salon_owner` dan `admin`
- [ ] Daftarkan di `bootstrap/app.php`

```bash
php artisan make:middleware CheckRole
```

---

### 🔴 PRIORITAS 9 — Fitur Tambahan (Nice to Have)

- [ ] Notifikasi email (booking konfirmasi, reminder H-1)
- [ ] Google Maps integration (peta interaktif berdasarkan `latitude`/`longitude`)
- [ ] Wishlist / Favorit salon
- [ ] Sistem referral
- [ ] Multi-bahasa (EN/ID)

---

## 🗂️ Checklist File (Status Terkini)

### Models `app/Models/`
```
[x] User.php          ← Updated: PK, relasi, SoftDeletes, accessor
[x] Kota.php          ← BARU
[x] Kategori.php      ← BARU
[x] Salon.php         ← BARU
[x] Promo.php         ← BARU
[x] Service.php       ← BARU
[x] Staff.php         ← BARU
[x] Order.php         ← BARU
[x] Review.php        ← BARU
[x] SalonImage.php    ← BARU
[x] StaffSchedule.php ← BARU
[x] OrderDetail.php   ← BARU
[x] Pembayaran.php    ← BARU
```

### Controllers `app/Http/Controllers/`
```
[ ] HomeController.php
[ ] OrderController.php
[ ] ReviewController.php
[x] Controller.php (base, ada tapi kosong)
```

### Livewire Components `app/Livewire/`
```
[ ] HomePage.php + view
[ ] SalonSearch.php + view
[ ] SalonDetail.php + view
[ ] Booking/StepService.php + view
[ ] Booking/StepStaff.php + view
[ ] Booking/StepDateTime.php + view
[ ] Booking/StepConfirm.php + view
[ ] User/Dashboard.php + view
[ ] User/Profile.php + view
[ ] User/OrderHistory.php + view
[ ] SalonOwner/Dashboard.php + view
[ ] SalonOwner/ManageService.php + view
[ ] SalonOwner/ManageStaff.php + view
[ ] Admin/Panel.php + view
```

### Routes `routes/web.php`
```php
// Saat ini hanya ada:
Route::view('/', 'welcome')->name('home');       // ← welcome masih default Laravel
Route::view('dashboard', 'dashboard')->name('dashboard');

// Yang perlu ditambahkan:
Route::get('/search', SalonSearch::class)->name('search');
Route::get('/salon/{id_salon}', SalonDetail::class)->name('salon.detail');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/booking/{salonId}', BookingWizard::class)->name('booking');
    Route::get('/orders', OrderHistory::class)->name('orders');
});
Route::middleware(['auth', 'role:salon_owner'])->prefix('owner')->group(...);
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(...);
```

### Blade Views `resources/views/`
```
[~] welcome.blade.php        ← ADA tapi masih default Laravel (perlu total redesign)
[~] dashboard.blade.php      ← ADA (boilerplate Flux)
[x] layouts/app.blade.php    ← ADA (Flux layout)
[ ] components/navbar.blade.php
[ ] components/footer.blade.php
[ ] components/salon-card.blade.php
[ ] components/service-item.blade.php
[ ] components/review-card.blade.php
[ ] components/staff-card.blade.php
[ ] livewire/home-page.blade.php
[ ] livewire/salon-search.blade.php
[ ] livewire/salon-detail.blade.php
[ ] livewire/booking/*.blade.php
[ ] pages/user/dashboard.blade.php
[ ] pages/owner/dashboard.blade.php
[ ] pages/admin/dashboard.blade.php
```

---

## 🧪 Testing Checklist

- [ ] Unit test: Model relasi (sudah manual-verified via tinker ✅)
- [ ] Feature test: Booking flow end-to-end
- [ ] Feature test: Search & filter salon
- [ ] Feature test: Auth (login, register, 2FA)
- [ ] Feature test: Role permission (customer ≠ owner ≠ admin)

---

## ⚡ Urutan Pengerjaan yang Disarankan

### Langkah selanjutnya (urgent):

```
[x] DONE — Buat semua 13 Model Eloquent ✅

[ ] NEXT 1: Redesign welcome.blade.php (Landing Page)
    → Ini yang dilihat pertama kali oleh penilai
    → Gunakan data dari DB (Salon::active()->with(['kota','primaryImage'])->take(6)->get())
    → Target: Navbar + Hero + Kategori + Card Salon + Footer

[ ] NEXT 2: Route web.php + SalonSearch Livewire
    → Tambah route /search dan /salon/{id}
    → Search + filter adalah fitur terpenting untuk demo

[ ] NEXT 3: SalonDetail page
    → Tampilkan layanan, staf, galeri foto
    → Data sudah ada di DB!

[ ] NEXT 4: Booking flow (minimal Step 1 + Step 3)
    → Pilih layanan → pilih waktu → konfirmasi

[ ] NEXT 5: Dashboard user (riwayat booking)
```

---

## 🐛 Known Issues / Catatan Teknis

1. **`welcome.blade.php`** — Masih halaman **default Laravel**. Perlu ditulis ulang total.
2. **Tidak ada Controller** — `app/Http/Controllers/` hanya ada `Controller.php` (base class kosong).
3. **Tidak ada Livewire Component** — `app/Livewire/` hanya berisi folder `Actions/` dari boilerplate.
4. **Route masih minimal** — `routes/web.php` hanya punya `/` (welcome) dan `/dashboard`.
5. **Booking slot logic** — Perlu implementasi cermat agar tidak ada double-booking.
6. **Middleware role** belum ada — `CheckRole` middleware belum dibuat.
7. **`staff_schedule`** — Data kosong (0 record). Perlu diisi sebelum fitur booking bisa berjalan sempurna.
8. **`order`, `review`, `pembayaran`** — Semua 0 record. Data transaksi belum ada (wajar, fitur belum jadi).
9. **User model** — `#[Fillable]` attribute lama sudah dihapus, diganti `$fillable` array yang benar.

---

## 📌 Referensi Halaman Treatwell yang Perlu Diduplikat

| Halaman Treatwell | URL Contoh | Prioritas |
|-------------------|-----------|-----------| 
| Landing Page | treatwell.co.uk | 🔴 Tinggi |
| Search Results | treatwell.co.uk/place/london/ | 🔴 Tinggi |
| Salon Detail | treatwell.co.uk/place/[salon]/ | 🔴 Tinggi |
| Booking Wizard | treatwell.co.uk/book/[salon]/ | 🔴 Tinggi |
| User Account | treatwell.co.uk/account/ | 🟡 Sedang |
| Login/Register | treatwell.co.uk/account/login/ | 🟢 Ada (30%) |
| Salon Dashboard | (owner portal) | 🟡 Sedang |
