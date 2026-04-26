# VIYGO — Progress Tracker: Duplikasi Treatwell.co.uk

> **Update terakhir:** 27 April 2026  
> **Status branch aktif:** `go-fresh` (up-to-date dengan remote)  
> **Referensi:** [treatwell.co.uk](https://www.treatwell.co.uk)

---

## 📊 Ringkasan Progress Keseluruhan

| Fase | Area | Status | Estimasi % |
|------|------|--------|-----------|
| ✅ | Infrastruktur & Setup | **SELESAI** | 100% |
| ✅ | Database Schema | **SELESAI** | 100% |
| ✅ | Data Scraping & Seeding | **SELESAI** | 95% |
| 🔴 | Backend / API / Controllers | **BELUM** | 5% |
| 🔴 | Frontend — Halaman Utama | **BELUM** | 10% |
| 🔴 | Frontend — Search & Filter | **BELUM** | 0% |
| 🔴 | Frontend — Halaman Salon | **BELUM** | 0% |
| 🔴 | Frontend — Booking Flow | **BELUM** | 0% |
| 🔴 | Auth — Login/Register | **SCAFFOLD ADA** | 30% |
| 🔴 | Dashboard User | **BELUM** | 5% |
| 🔴 | Dashboard Salon Owner | **BELUM** | 0% |
| 🔴 | Admin Panel | **BELUM** | 0% |
| 🔴 | Review & Rating | **BELUM** | 0% |
| 🔴 | Payment Flow | **BELUM** | 0% |

**Estimasi progress total: ~20%**

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
- [x] `users` — customer, salon owner, admin (+ 2FA columns)
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

### 4. Auth Scaffold (Livewire Fortify)
- [x] Halaman login tersedia (`/login`)
- [x] Halaman register tersedia (`/register`)
- [x] Halaman settings tersedia (`/settings`)
- [x] Route auth dengan middleware `auth`, `verified`
- [x] 2FA (Two Factor Auth) — kolom tersedia di users

---

## 🔴 YANG PERLU DIKERJAKAN (Prioritas Tinggi → Rendah)

---

### 🔴 PRIORITAS 1 — Model Eloquent (Blokir semua fitur lain)

Semua model harus dibuat sebelum controller dan view bisa bekerja.

**File yang perlu dibuat:** `app/Models/`

| Model | Tabel | Relasi yang Diperlukan |
|-------|-------|----------------------|
| `Salon.php` | `salon` | belongsTo(User), belongsTo(Kota), hasMany(Service), hasMany(Staff), hasMany(SalonImage), hasMany(Order) |
| `Service.php` | `service` | belongsTo(Salon), belongsTo(Kategori), belongsToMany(Staff) |
| `Kategori.php` | `kategori` | hasMany(Service) |
| `Kota.php` | `kota` | hasMany(Salon) |
| `Staff.php` | `staff` | belongsTo(Salon), belongsToMany(Service), hasMany(StaffSchedule) |
| `StaffSchedule.php` | `staff_schedule` | belongsTo(Staff) |
| `SalonImage.php` | `salon_images` | belongsTo(Salon) |
| `Order.php` | `order` | belongsTo(User), belongsTo(Salon), hasMany(OrderDetail), hasOne(Pembayaran), hasOne(Review) |
| `OrderDetail.php` | `order_detail` | belongsTo(Order), belongsTo(Service), belongsTo(Staff) |
| `Review.php` | `review` | belongsTo(User), belongsTo(Salon), belongsTo(Order) |
| `Promo.php` | `promo` | belongsToMany(User) |
| `Pembayaran.php` | `pembayaran` | belongsTo(Order) |

**Cara cepat:**
```bash
# Generate semua model sekaligus
php artisan make:model Salon
php artisan make:model Service
php artisan make:model Kategori
php artisan make:model Kota
php artisan make:model Staff
php artisan make:model StaffSchedule
php artisan make:model SalonImage
php artisan make:model Order
php artisan make:model OrderDetail
php artisan make:model Review
php artisan make:model Promo
php artisan make:model Pembayaran
```

> ⚠️ **Penting:** Tiap model harus set `$primaryKey` sesuai nama kolom custom (e.g. `$primaryKey = 'id_salon'`), dan `$table` eksplisit karena nama tabel tidak mengikuti konvensi Laravel standar.

---

### 🔴 PRIORITAS 2 — Halaman Utama / Landing Page

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
- [ ] Card salon: foto, nama, kota, rating, harga mulai dari Rp/£
- [ ] Carousel atau grid

#### E. Cara Kerja (How It Works)
- [ ] 3 langkah: Cari → Pilih → Booking

#### F. Footer
- [ ] Link navigasi
- [ ] Copyright, sosmed

**File yang perlu dibuat/diubah:**
```
resources/views/welcome.blade.php    ← UBAH TOTAL (saat ini default Laravel)
resources/views/layouts/app.blade.php ← Tambahkan navbar + footer
resources/views/components/navbar.blade.php
resources/views/components/footer.blade.php
resources/views/components/salon-card.blade.php
resources/views/components/category-card.blade.php
```

**Controller/Livewire:**
```bash
php artisan make:livewire HomePage
# atau
php artisan make:controller HomeController
```

---

### 🔴 PRIORITAS 3 — Search & Filter Salon

**Referensi Treatwell:** https://www.treatwell.co.uk/place/london/

#### Fitur yang diperlukan:
- [ ] URL struktur: `/search?q=haircut&city=london`
- [ ] Filter sidebar:
  - Kategori (checkbox)
  - Harga range (slider)
  - Rating minimum
  - Jam buka (waktu)
- [ ] Sort by: Relevansi, Rating, Harga
- [ ] Pagination (infinite scroll atau numbered)
- [ ] Map view (Google Maps embed, opsional)
- [ ] Jumlah hasil: "123 salon ditemukan"

**File yang perlu dibuat:**
```
app/Livewire/SalonSearch.php         ← Livewire component dengan filter reaktif
resources/views/livewire/salon-search.blade.php
routes/web.php                       ← Tambah route /search
```

**Query yang diperlukan di `SalonSearch.php`:**
```php
Salon::query()
    ->when($this->category, fn($q) => $q->whereHas('services.kategori', fn($q) => $q->where('slug', $this->category)))
    ->when($this->city, fn($q) => $q->whereHas('kota', fn($q) => $q->where('nama_kota', 'like', "%{$this->city}%")))
    ->when($this->minRating, fn($q) => $q->where('rating', '>=', $this->minRating))
    ->where('status', 'active')
    ->paginate(12);
```

---

### 🔴 PRIORITAS 4 — Halaman Detail Salon

**Referensi Treatwell:** https://www.treatwell.co.uk/place/[salon-name]/

#### Konten yang diperlukan:
- [ ] Galeri foto salon (carousel)
- [ ] Nama salon, alamat, jam buka, rating
- [ ] Tab: Layanan | Staf | Review | Info
- [ ] Daftar layanan dengan harga & durasi (grouped by kategori)
- [ ] Profil staf dengan foto dan spesialisasi
- [ ] Peta lokasi salon
- [ ] Tombol "Pesan Sekarang" → redirect ke booking flow
- [ ] Review card (nama user, rating bintang, komentar, tanggal)

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
// routes/web.php
Route::get('/salon/{id_salon}', SalonDetail::class)->name('salon.detail');
```

---

### 🔴 PRIORITAS 5 — Booking Flow

**Referensi Treatwell:** https://www.treatwell.co.uk/book/[salon]/

#### Langkah booking (wizard / multi-step):

**Step 1: Pilih Layanan**
- [ ] Daftar layanan salon yang bisa dipilih (multi-select)
- [ ] Tampilkan durasi + harga per layanan
- [ ] Hitung total durasi & total harga

**Step 2: Pilih Staf**
- [ ] List staf yang tersedia untuk layanan terpilih
- [ ] Opsi "Staf Mana Saja" (tidak pilih spesifik)

**Step 3: Pilih Tanggal & Waktu**
- [ ] Kalender pilih tanggal
- [ ] Slot waktu tersedia berdasarkan jadwal staf
- [ ] Validasi: jangan tampilkan slot yang sudah dipesan

**Step 4: Konfirmasi & Bayar**
- [ ] Ringkasan pesanan (layanan, staf, waktu, total)
- [ ] Input kode promo
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

**Logic yang perlu diimplementasi:**
```php
// Cek ketersediaan slot — logika kritis!
// Staf tidak double-booked pada waktu yang sama
Order::whereHas('orderDetail', fn($q) => 
    $q->where('id_staff', $staffId)
      ->where('date_order', $date)
      ->where('time_start', '<', $timeEnd)
      ->where('time_end', '>', $timeStart)
)->doesntExist();
```

---

### 🔴 PRIORITAS 6 — Dashboard User (Customer)

**Referensi Treatwell:** https://www.treatwell.co.uk/account/

- [ ] Riwayat booking (upcoming & past)
- [ ] Tombol batalkan booking (jika belum dilayani)
- [ ] Tombol tulis review (jika sudah selesai)
- [ ] Profil user (edit nama, email, foto, nomor HP)
- [ ] Promo / voucher yang dimiliki

---

### 🔴 PRIORITAS 7 — Dashboard Salon Owner

- [ ] Statistik salon (total booking hari ini/bulan ini)
- [ ] Manajemen layanan (CRUD service)
- [ ] Manajemen staf (CRUD staff + jadwal)
- [ ] Daftar order masuk
- [ ] Manajemen galeri foto salon
- [ ] Edit profil salon

---

### 🔴 PRIORITAS 8 — Admin Panel

- [ ] Manajemen semua salon (approve/reject pendaftaran)
- [ ] Manajemen kategori & kota
- [ ] Manajemen promo global
- [ ] Laporan & statistik platform
- [ ] Moderasi review

---

### 🔴 PRIORITAS 9 — Fitur Tambahan (Nice to Have)

- [ ] Notifikasi email (booking konfirmasi, reminder H-1)
- [ ] SMS/WhatsApp reminder
- [ ] Google Maps integration (peta interaktif)
- [ ] Wishlist / Favorit salon
- [ ] Sistem referral
- [ ] Multi-bahasa (EN/ID)
- [ ] PWA (Progressive Web App)
- [ ] Dark mode

---

## 🗂️ File yang Perlu Dibuat / Diubah (Checklist Teknis)

### Models (app/Models/)
```
[ ] Salon.php
[ ] Service.php
[ ] Kategori.php
[ ] Kota.php
[ ] Staff.php
[ ] StaffSchedule.php
[ ] SalonImage.php
[ ] Order.php
[ ] OrderDetail.php
[ ] Review.php
[ ] Promo.php
[ ] Pembayaran.php
```

### Livewire Components (app/Livewire/)
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

### Routes (routes/web.php)
```php
// Tambahkan semua route berikut:
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', SalonSearch::class)->name('search');
Route::get('/salon/{id}', SalonDetail::class)->name('salon.detail');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/booking/{salonId}', BookingWizard::class)->name('booking');
    Route::get('/dashboard', UserDashboard::class)->name('dashboard');
    Route::get('/profile', UserProfile::class)->name('profile');
    Route::get('/orders', OrderHistory::class)->name('orders');
});

Route::middleware(['auth', 'role:salon_owner'])->prefix('owner')->group(function () {
    Route::get('/dashboard', OwnerDashboard::class)->name('owner.dashboard');
    Route::resource('/services', OwnerServiceController::class);
    Route::resource('/staff', OwnerStaffController::class);
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', AdminPanel::class)->name('admin.dashboard');
    Route::resource('/salons', AdminSalonController::class);
    Route::resource('/categories', AdminCategoryController::class);
});
```

### Blade Views (resources/views/)
```
[x] layouts/app.blade.php (ada tapi perlu redesign)
[ ] welcome.blade.php (perlu TOTAL redesign — saat ini default Laravel)
[ ] components/navbar.blade.php
[ ] components/footer.blade.php
[ ] components/salon-card.blade.php
[ ] components/service-item.blade.php
[ ] components/review-card.blade.php
[ ] components/staff-card.blade.php
[ ] components/booking-wizard.blade.php
[ ] livewire/home-page.blade.php
[ ] livewire/salon-search.blade.php
[ ] livewire/salon-detail.blade.php
[ ] livewire/booking/*.blade.php
[ ] pages/user/dashboard.blade.php
[ ] pages/owner/dashboard.blade.php
[ ] pages/admin/dashboard.blade.php
```

### Middleware
```bash
# Perlu middleware role-based:
php artisan make:middleware CheckRole
# Daftarkan di app/Http/Kernel.php atau bootstrap/app.php
```

---

## 🧪 Testing Checklist

- [ ] Unit test: Model relasi (Salon hasMany Service, dll)
- [ ] Feature test: Booking flow end-to-end
- [ ] Feature test: Search & filter salon
- [ ] Feature test: Auth (login, register, 2FA)
- [ ] Feature test: Role permission (customer ≠ owner ≠ admin)

```bash
# Jalankan semua test
php artisan test
```

---

## ⚡ Urutan Pengerjaan yang Disarankan

Karena **tenggat sudah dekat**, berikut urutan prioritas yang paling efisien:

### Minggu ini (urgent):

```
[ ] Hari 1-2: Buat semua 12 Model Eloquent
    → Tanpa ini, TIDAK ADA yang bisa jalan

[ ] Hari 2-3: Redesign welcome.blade.php (Landing Page)
    → Ini yang dilihat pertama kali oleh penilai
    → Gunakan data statis dulu, belum perlu controller

[ ] Hari 3-4: SalonSearch Livewire component
    → Search + filter paling penting untuk demo

[ ] Hari 4-5: SalonDetail page
    → Tampilkan layanan, staf, review

[ ] Hari 5-6: Booking flow (minimal Step 1 + Step 3)
    → Pilih layanan → pilih waktu → konfirmasi
```

### Setelah itu (jika ada waktu):
```
[ ] Dashboard user (riwayat booking)
[ ] Auth fine-tuning (pastikan role berfungsi)
[ ] Dashboard salon owner (manajemen layanan)
```

---

## 🐛 Known Issues / Catatan Teknis

1. **`welcome.blade.php`** — Saat ini masih halaman **default Laravel**, bukan halaman VIYGO. Perlu ditulis ulang total.
2. **Tidak ada Model** — Folder `app/Models/` hanya berisi `User.php`. Semua 12 model lain belum ada.
3. **Tidak ada Controller** — Folder `app/Http/Controllers/` kosong. Semua logic belum diimplementasi.
4. **Tidak ada Livewire Component** — `app/Livewire/` hanya berisi folder `Actions/` dari boilerplate.
5. **Route masih minimal** — `routes/web.php` hanya punya `/` (welcome) dan `/dashboard`.
6. **`sessions` migration** belum di-commit — file ada di lokal tapi belum di-track git.
7. **Booking slot logic** — Perlu implementasi yang cermat agar tidak ada double-booking.

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
