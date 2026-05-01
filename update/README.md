# VIYGO — Laravel File Installation Guide

## Struktur File yang Disediakan

```
viygo-laravel/
├── routes/
│   └── web.php                          ← Semua routes publik & auth
├── app/Http/Controllers/
│   ├── HomeController.php
│   ├── KategoriController.php
│   ├── SalonController.php
│   ├── SearchController.php
│   ├── BookingController.php
│   ├── AkunController.php
│   ├── GiftCardController.php
│   ├── LookbookController.php
│   ├── TreatmentFilesController.php
│   └── MitraController.php
└── resources/views/
    ├── layouts/
    │   └── public.blade.php             ← Layout utama halaman publik
    ├── components/
    │   ├── viygo-logo.blade.php         ← Logo cross-fade Alpine.js ⭐
    │   ├── viygo-navbar.blade.php       ← Navbar 2 baris (Treatwell-style)
    │   ├── viygo-footer.blade.php       ← Footer
    │   └── salon-card.blade.php         ← Kartu salon (list & grid)
    ├── home.blade.php                   ← Beranda
    ├── cari/index.blade.php             ← Hasil pencarian + map
    ├── kategori/show.blade.php          ← Library per kategori
    ├── salon/show.blade.php             ← Detail salon
    ├── booking/
    │   ├── create.blade.php             ← Form booking 3 langkah
    │   └── konfirmasi.blade.php         ← Booking berhasil
    ├── akun/
    │   ├── index.blade.php              ← Dashboard akun
    │   ├── bookings.blade.php           ← Riwayat booking
    │   ├── favorit.blade.php            ← Salon favorit
    │   ├── pengaturan.blade.php         ← Edit profil
    │   └── reward.blade.php             ← VIYGO Rewards
    ├── gift-card/index.blade.php
    ├── lookbook/index.blade.php
    ├── treatment-files/index.blade.php
    └── mitra/index.blade.php
```

## Cara Install

### 1. Copy semua file ke project Laravel

```bash
# Copy routes (MERGE dengan web.php yang sudah ada)
# Tambahkan isi viygo-laravel/routes/web.php ke routes/web.php milikmu

# Copy controllers
cp -r viygo-laravel/app/Http/Controllers/* app/Http/Controllers/

# Copy views
cp -r viygo-laravel/resources/views/layouts/public.blade.php resources/views/layouts/
cp -r viygo-laravel/resources/views/components/viygo-* resources/views/components/
cp -r viygo-laravel/resources/views/components/salon-card.blade.php resources/views/components/
cp -r viygo-laravel/resources/views/home.blade.php resources/views/
cp -r viygo-laravel/resources/views/cari resources/views/
cp -r viygo-laravel/resources/views/kategori resources/views/
cp -r viygo-laravel/resources/views/salon resources/views/
cp -r viygo-laravel/resources/views/booking resources/views/
cp -r viygo-laravel/resources/views/akun resources/views/
cp -r viygo-laravel/resources/views/gift-card resources/views/
cp -r viygo-laravel/resources/views/lookbook resources/views/
cp -r viygo-laravel/resources/views/treatment-files resources/views/
cp -r viygo-laravel/resources/views/mitra resources/views/
```

### 2. Copy logo ke public/images/

```bash
mkdir -p public/images
cp path/to/logo1.jpeg public/images/logo1.jpeg
cp path/to/logo2.jpeg public/images/logo2.jpeg
```

### 3. Update Model Salon (tambah field slug jika belum ada)

```php
// Migration baru (opsional jika belum ada slug)
php artisan make:migration add_slug_to_salon_table --table=salon
```

```php
// Di dalam migration up():
$table->string('slug')->unique()->nullable()->after('nama_salon');
```

### 4. Update OrderDetail model

Pastikan model `OrderDetail` memiliki field `catatan`:

```bash
php artisan make:migration add_catatan_to_order_detail_table --table=order_detail
```

```php
$table->text('catatan')->nullable()->after('harga');
```

### 5. Jalankan migration

```bash
php artisan migrate
```

### 6. Update routes/web.php (PENTING)

Ganti baris `Route::view('/', 'welcome')` dengan:

```php
Route::get('/', [HomeController::class, 'index'])->name('home');
```

Dan tambahkan semua use statement di bagian atas.

### 7. Pastikan Alpine.js tersedia

Alpine.js sudah include otomatis via **Livewire 3**.  
Jika menggunakan Alpine.js standalone, tambahkan di `public.blade.php`:

```html
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

### 8. Seeder Kategori (opsional)

```php
// database/seeders/KategoriSeeder.php
$kategoriList = [
    ['name'=>'Rambut',   'slug'=>'rambut',   'is_active'=>true],
    ['name'=>'Facial',   'slug'=>'facial',   'is_active'=>true],
    ['name'=>'Pijat',    'slug'=>'pijat',    'is_active'=>true],
    ['name'=>'Kuku',     'slug'=>'kuku',     'is_active'=>true],
    ['name'=>'Alis',     'slug'=>'alis',     'is_active'=>true],
    ['name'=>'Makeup',   'slug'=>'makeup',   'is_active'=>true],
    ['name'=>'Tubuh',    'slug'=>'tubuh',    'is_active'=>true],
    ['name'=>"Pria's",   'slug'=>'pria',     'is_active'=>true],
];
```

---

## Routes Summary

| Method | URL | Name | Deskripsi |
|--------|-----|------|-----------|
| GET | `/` | home | Beranda |
| GET | `/cari` | cari | Pencarian salon |
| GET | `/kategori/{slug}` | kategori.show | Library per kategori |
| GET | `/salon/{slug}` | salon.show | Detail salon |
| GET | `/salon/{slug}/booking` | booking.create | Form booking *(auth)* |
| POST | `/salon/{slug}/booking` | booking.store | Simpan booking *(auth)* |
| GET | `/booking/{kode}/konfirmasi` | booking.konfirmasi | Konfirmasi *(auth)* |
| POST | `/booking/{kode}/batal` | booking.batal | Batalkan *(auth)* |
| GET | `/akun` | akun.index | Dashboard akun *(auth)* |
| GET | `/akun/bookings` | akun.bookings | Riwayat booking *(auth)* |
| GET | `/akun/favorit` | akun.favorit | Favorit *(auth)* |
| GET | `/akun/pengaturan` | akun.pengaturan | Edit profil *(auth)* |
| GET | `/gift-card` | gift-card | Halaman gift card |
| GET | `/lookbook` | lookbook | Lookbook |
| GET | `/treatment-files` | treatment-files | Treatment files |
| GET | `/mitra` | mitra | Halaman mitra/partner |
