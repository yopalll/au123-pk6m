# VIYGO Admin Panel Documentation

Dokumen ini menjelaskan arsitektur, fungsionalitas, dan logika di balik Panel Admin VIYGO yang dibangun menggunakan **Filament v5.6** pada framework **Laravel 11.x**.

---

## 1. Arsitektur & Keamanan (Roles & Access Control)

Panel admin dilindungi oleh sistem otentikasi bawaan Laravel dan Filament. Tidak semua user bisa mengakses panel ini.

### 1.1 Logika Akses (`canAccessPanel`)
Hanya user dengan spesifikasi tertentu yang diizinkan masuk ke rute `/admin`. Logika ini diimplementasikan langsung pada model `User` (`app/Models/User.php`) dengan mengimplementasikan interface `FilamentUser`:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    // ...
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            // HANYA user dengan role 'admin' dan status 'is_active' = true yang bisa masuk.
            return $this->role === 'admin' && $this->is_active;
        }
        return false;
    }
}
```

### 1.2 Adaptasi untuk Filament v5
Filament v5 membutuhkan properti `name` pada model user untuk ditampilkan di UI panel (karena memanggil `$user->name`). Karena VIYGO menggunakan `first_name` dan `last_name`, kita menambahkan sebuah **Accessor** sebagai alias:

```php
protected function name(): Attribute
{
    return Attribute::make(
        get: fn () => $this->full_name,
    );
}
```

---

## 2. Struktur Navigasi & Resources

Navigasi di sidebar dikelompokkan menjadi 3 grup utama untuk memudahkan manajemen data berskala besar.

### A. Navigation Group: `Marketplace`
Grup ini menangani entitas utama pembentuk marketplace VIYGO.
1. **Salons (`SalonResource`)**
   - **Fitur Utama**: CRUD Salon, moderasi pendaftaran salon (Approve/Reject actions).
   - **Relation Managers**:
     - *Services*: Melihat dan mengelola layanan yang ditawarkan salon tersebut.
     - *Staff*: Mengelola karyawan salon.
     - *Images*: Mengelola galeri foto salon.
2. **Services (`ServiceResource`)**
   - **Fitur Utama**: Manajemen seluruh layanan (haircut, spa, dll) lintas salon.
   - **Optimasi Data Besar**: Global Search dimatikan (`protected static bool $isGloballySearchable = false;`) karena tabel layanan bisa mencapai ratusan ribu baris, yang mana global search bisa membebani database.
3. **Categories (`KategoriResource`)**
   - **Fitur Utama**: Manajemen kategori layanan dengan fitur bulk Activate/Deactivate.
4. **Cities (`KotaResource`)**
   - **Fitur Utama**: Manajemen wilayah operasional (Read & Edit Only, disable create untuk mencegah duplikasi wilayah secara tidak sengaja).

### B. Navigation Group: `Transactions`
Grup ini menangani aliran uang, promosi, dan interaksi pengguna.
1. **Orders (`OrderResource`)**
   - **Fitur Utama**: Read & Edit Only. Admin bisa melihat detail order, membatalkan order, atau menandai sukses secara manual.
   - **Relation Managers**:
     - *OrderDetails*: Melihat rincian layanan spesifik yang dipesan dalam satu order (Read-only).
2. **Reviews (`ReviewResource`)**
   - **Fitur Utama**: Moderasi ulasan. Admin bisa menyembunyikan (Hide) ulasan yang melanggar ketentuan melalui toggle atau bulk action.
3. **Promos (`PromoResource`)**
   - **Fitur Utama**: Manajemen kode diskon (Fixed/Percentage), kuota stok, dan validitas tanggal.

### C. Navigation Group: `Users`
1. **Users (`UserResource`)**
   - **Fitur Utama**: CRUD User (Admin, Customer, Salon Owner).
   - **Keamanan**: Saat edit, kolom password akan meng-hash password baru jika diisi, dan akan membiarkan password lama jika dikosongkan.

---

## 3. Dashboard & Monitoring

File konfigurasi utama provider ada di `app/Providers/Filament/AdminPanelProvider.php`. Panel ini dimodifikasi tampilannya agar sesuai dengan brand VIYGO (Navy `#1B2D6B` dan Info Blue `#4BA3CC`).

Dashboard memiliki dua widget utama:
1. **`StatsOverview` (Statistik Makro)**
   - Menampilkan total Salons (dan yang aktif), Users (dan jumlah customer), Services, Orders (dan yang pending), Reviews, dan Total Revenue.
2. **`LatestOrders` (Tabel Transaksi)**
   - Menampilkan 10 order terakhir secara real-time.
   - **Optimasi Query**: Menggunakan Eager Loading (`Order::with(['user', 'salon'])`) untuk mencegah N+1 query problem pada dashboard.

---

## 4. Best Practices: Menangani Skala Pengguna & Data Besar

VIYGO dirancang sebagai marketplace, yang berarti volume datanya (terutama tabel `services`, `orders`, dan `users`) akan bertambah dengan cepat (saat ini saja terdapat ~190K services dan ribuan user). 

Berikut adalah strategi arsitektur admin untuk menjaga performa:

### 1. Hindari N+1 Queries di Tabel
Setiap kolom relasional di Filament (seperti `TextColumn::make('salon.nama_salon')`) secara otomatis melakukan *eager loading* di balik layar oleh Filament. Namun, pada widget custom (seperti `LatestOrders`), pastikan secara eksplisit menggunakan `->query(Order::with([...]))`.

### 2. Batasi Global Search
Filament memiliki fitur pencarian global (icon *search* di header atas) yang mencari ke semua resource. 
- **Rule of Thumb**: Matikan Global Search (`$isGloballySearchable = false;`) pada tabel dengan jumlah data sangat masif (seperti `ServiceResource`) atau batasi kolom yang dicari hanya kolom terindeks (ID, Code). Pencarian global menggunakan klausul `LIKE %...%` yang lambat pada jutaan baris tanpa index khusus (seperti Full-Text Search).

### 3. Pagination & Default Sort
- Semua resource memiliki default sorting berdasarkan Primary Key secara descending (`defaultSort('id_xxx', 'desc')`). Ini sangat cepat karena menggunakan primary key index.
- Hindari sorting default pada kolom yang tidak terindeks (seperti `nama` atau `deskripsi`).
- Pagination dikontrol, secara default menampilkan 10-25 baris per halaman.

### 4. Asynchronous Metrics (Future Improvement)
Saat ini widget `StatsOverview` melakukan agregasi langsung (contoh: `Order::success()->sum('total_pembayaran')`).
- **Skala Ekstrem (> 1 Juta Transaksi)**: Agregasi langsung seperti ini akan memblokir load dashboard. Solusi untuk masa depan adalah mengaktifkan *caching* pada widget Filament, atau menghitung statistik ini melalui Cron Job (Scheduler) setiap jam dan menyimpannya di tabel `metrics_cache`, lalu dashboard hanya membaca dari cache tersebut.

### 5. Soft Deletes
Data krusial tidak pernah benar-benar dihapus. Hampir semua model dan resource (terutama transaksi dan master data) menggunakan fitur `SoftDeletes` bawaan Laravel, dipadukan dengan filter `TrashedFilter::make()` dari Filament agar admin bisa memulihkan (restore) data jika terjadi kesalahan.

---

## 5. Ringkasan Eksekusi Command

Jika ada masalah instalasi di server produksi atau lokal yang baru, jalankan:
```bash
# Clear cache Laravel
php artisan optimize:clear
php artisan filament:clear-cached-components

# Membuat atau update user menjadi Admin
php artisan tinker --execute="$u = \App\Models\User::where('email','admin@viygo.com')->first(); $u->role='admin'; $u->is_active=true; $u->save();"
```
