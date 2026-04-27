# 📚 Analisis Eloquent Models — VIYGO

> Dokumen ini merangkum **semua model Eloquent** yang perlu dibuat berdasarkan skema database VIYGO, lengkap dengan properti, relasi, cast, fillable, dan catatan penting.

---

## 🗺️ Peta Relasi Antar Tabel

```
kota ──────────────────────────────────────────────────────┐
                                                            │ hasMany
kategori ──────────────────────┐ hasMany                   ▼
                               │                        [salon]
users ─────────────── hasMany ─┤ hasMany       hasMany ──┬─── hasMany ──► [service] ──► hasMany ──► [order_detail]
         hasMany ──► [order]   │                         │                   ▲
         hasMany ──► [review]  └─────────────────────────┤               [staff_service] (pivot)
         hasMany ──► [pembayaran]                         │                   │
         belongsToMany ──► [promo] (via user_promo)       ├── hasMany ──► [staff] ──► hasMany ──► [staff_schedule]
                                                          │                   │
                                                          │               [staff_service] (pivot)
                                                          ├── hasMany ──► [salon_images]
                                                          └── hasMany ──► [review]

order ──► hasOne ──► [pembayaran]
order ──► hasMany ──► [order_detail]
order ──► hasOne ──► [review]
```

---

## 📋 Daftar Model yang Harus Dibuat

| # | Model | Tabel | Primary Key | SoftDelete |
|---|-------|-------|-------------|------------|
| 1 | `User` | `users` | `id_user` | ✅ |
| 2 | `Kota` | `kota` | `id_kota` | ❌ |
| 3 | `Kategori` | `kategori` | `id_kategori` | ❌ |
| 4 | `Salon` | `salon` | `id_salon` | ✅ |
| 5 | `Promo` | `promo` | `id_promo` | ✅ |
| 6 | `Service` | `service` | `id_service` | ✅ |
| 7 | `Staff` | `staff` | `id_staff` | ✅ |
| 8 | `Order` | `order` | `id_order` | ❌ |
| 9 | `Review` | `review` | `id_review` | ❌ |
| 10 | `SalonImage` | `salon_images` | `id_salon_image` | ❌ |
| 11 | `StaffSchedule` | `staff_schedule` | `id_schedule` | ❌ |
| 12 | `OrderDetail` | `order_detail` | `id_order_detail` | ❌ |
| 13 | `Pembayaran` | `pembayaran` | `id_pembayaran` | ❌ |
| — | *(Pivot)* `UserPromo` | `user_promo` | `id` (auto) | ❌ |
| — | *(Pivot)* `StaffService` | `staff_service` | `id` (auto) | ❌ |

---

## 1. Model `User`

**File:** `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    protected $table      = 'users';
    protected $primaryKey = 'id_user';

    protected $fillable = [
        'first_name', 'last_name', 'email', 'password',
        'phone_number', 'profile_url', 'role', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function salons()        { return $this->hasMany(Salon::class, 'id_user'); }
    public function orders()        { return $this->hasMany(Order::class, 'id_user'); }
    public function reviews()       { return $this->hasMany(Review::class, 'id_user'); }
    public function pembayarans()   { return $this->hasMany(Pembayaran::class, 'id_user'); }

    /** Many-to-Many ke Promo melalui tabel pivot user_promo */
    public function promos()
    {
        return $this->belongsToMany(Promo::class, 'user_promo', 'id_user', 'id_promo')
                    ->withPivot('is_used', 'used_at')
                    ->withTimestamps();
    }
}
```

**Catatan penting:**
- `role` → enum: `customer | salon_owner | admin`
- Gunakan `SoftDeletes` (ada kolom `deleted_at`)
- `$primaryKey = 'id_user'` karena bukan `id` default Laravel

---

## 2. Model `Kota`

**File:** `app/Models/Kota.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kota extends Model
{
    protected $table      = 'kota';
    protected $primaryKey = 'id_kota';

    protected $fillable = ['nama_kota', 'provinsi'];

    // ──── Relasi ────────────────────────────────────────────
    public function salons() { return $this->hasMany(Salon::class, 'id_kota'); }
}
```

---

## 3. Model `Kategori`

**File:** `app/Models/Kategori.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table      = 'kategori';
    protected $primaryKey = 'id_kategori';

    protected $fillable = ['name', 'deskripsi', 'slug', 'icon_url', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function services() { return $this->hasMany(Service::class, 'id_kategori'); }
}
```

---

## 4. Model `Salon`

**File:** `app/Models/Salon.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salon extends Model
{
    use SoftDeletes;

    protected $table      = 'salon';
    protected $primaryKey = 'id_salon';

    protected $fillable = [
        'id_user', 'id_kota', 'nama_salon', 'alamat', 'deskripsi',
        'phone_number', 'opening_time', 'closing_time',
        'image_url', 'maps_url', 'latitude', 'longitude',
        'rating', 'total_review', 'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude'     => 'decimal:8',
            'longitude'    => 'decimal:8',
            'rating'       => 'decimal:2',
            'total_review' => 'integer',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function kota()      { return $this->belongsTo(Kota::class, 'id_kota'); }
    public function owner()     { return $this->belongsTo(User::class, 'id_user'); }
    public function services()  { return $this->hasMany(Service::class, 'id_salon'); }
    public function staff()     { return $this->hasMany(Staff::class, 'id_salon'); }
    public function images()    { return $this->hasMany(SalonImage::class, 'id_salon'); }
    public function orders()    { return $this->hasMany(Order::class, 'id_salon'); }
    public function reviews()   { return $this->hasMany(Review::class, 'id_salon'); }

    /** Foto utama salon */
    public function primaryImage()
    {
        return $this->hasOne(SalonImage::class, 'id_salon')->where('is_primary', true);
    }
}
```

**Catatan penting:**
- `status` → enum: `active | inactive | pending`
- Ada index pada `id_kota`, `status`, `rating` → query akan cepat
- Gunakan `SoftDeletes`

---

## 5. Model `Promo`

**File:** `app/Models/Promo.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use SoftDeletes;

    protected $table      = 'promo';
    protected $primaryKey = 'id_promo';

    protected $fillable = [
        'nama_promo', 'deskripsi_promo', 'diskon', 'diskon_max',
        'min_transaksi', 'tipe_promo', 'kode_promo',
        'time_start', 'time_expired', 'stock', 'used_counter', 'status',
    ];

    protected function casts(): array
    {
        return [
            'time_start'    => 'datetime',
            'time_expired'  => 'datetime',
            'diskon'        => 'decimal:2',
            'diskon_max'    => 'decimal:2',
            'min_transaksi' => 'decimal:2',
            'stock'         => 'integer',
            'used_counter'  => 'integer',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_promo', 'id_promo', 'id_user')
                    ->withPivot('is_used', 'used_at')
                    ->withTimestamps();
    }

    public function orders() { return $this->hasMany(Order::class, 'id_promo'); }

    // ──── Scope ─────────────────────────────────────────────
    /** Hanya promo yang masih aktif & belum expired */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('time_expired', '>=', now());
    }
}
```

---

## 6. Model `Service`

**File:** `app/Models/Service.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $table      = 'service';
    protected $primaryKey = 'id_service';

    protected $fillable = [
        'id_salon', 'id_kategori', 'nama', 'deskripsi',
        'durasi', 'harga', 'status',
    ];

    protected function casts(): array
    {
        return [
            'harga'  => 'decimal:2',
            'durasi' => 'integer',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function salon()    { return $this->belongsTo(Salon::class, 'id_salon'); }
    public function kategori() { return $this->belongsTo(Kategori::class, 'id_kategori'); }

    /** Many-to-Many: Staff yang bisa mengerjakan service ini */
    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'staff_service', 'id_service', 'id_staff')
                    ->withTimestamps();
    }

    public function orderDetails() { return $this->hasMany(OrderDetail::class, 'id_service'); }
}
```

---

## 7. Model `Staff`

**File:** `app/Models/Staff.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes;

    protected $table      = 'staff';
    protected $primaryKey = 'id_staff';

    protected $fillable = ['id_salon', 'name', 'profile_url', 'status'];

    // ──── Relasi ────────────────────────────────────────────
    public function salon() { return $this->belongsTo(Salon::class, 'id_salon'); }

    public function schedules() { return $this->hasMany(StaffSchedule::class, 'id_staff'); }

    /** Many-to-Many: Service yang bisa dikerjakan staff ini */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'staff_service', 'id_staff', 'id_service')
                    ->withTimestamps();
    }

    public function orderDetails() { return $this->hasMany(OrderDetail::class, 'id_staff'); }
}
```

---

## 8. Model `Order`

**File:** `app/Models/Order.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table      = 'order';
    protected $primaryKey = 'id_order';

    protected $fillable = [
        'id_user', 'id_salon', 'id_promo',
        'kode_order', 'date_order',
        'total_pembayaran', 'total_diskon', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date_order'       => 'date',
            'total_pembayaran' => 'decimal:2',
            'total_diskon'     => 'decimal:2',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function user()       { return $this->belongsTo(User::class, 'id_user'); }
    public function salon()      { return $this->belongsTo(Salon::class, 'id_salon'); }
    public function promo()      { return $this->belongsTo(Promo::class, 'id_promo'); }
    public function details()    { return $this->hasMany(OrderDetail::class, 'id_order'); }
    public function review()     { return $this->hasOne(Review::class, 'id_order'); }
    public function pembayaran() { return $this->hasOne(Pembayaran::class, 'id_order'); }

    // ──── Scope ─────────────────────────────────────────────
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
```

---

## 9. Model `Review`

**File:** `app/Models/Review.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table      = 'review';
    protected $primaryKey = 'id_review';

    protected $fillable = [
        'id_user', 'id_salon', 'id_order',
        'rating', 'komentar', 'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'rating'     => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function user()  { return $this->belongsTo(User::class, 'id_user'); }
    public function salon() { return $this->belongsTo(Salon::class, 'id_salon'); }
    public function order() { return $this->belongsTo(Order::class, 'id_order'); }

    // ──── Scope ─────────────────────────────────────────────
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
```

---

## 10. Model `SalonImage`

**File:** `app/Models/SalonImage.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonImage extends Model
{
    protected $table      = 'salon_images';
    protected $primaryKey = 'id_salon_image';

    protected $fillable = ['id_salon', 'image_url', 'is_primary', 'urutan'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'urutan'     => 'integer',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function salon() { return $this->belongsTo(Salon::class, 'id_salon'); }
}
```

---

## 11. Model `StaffSchedule`

**File:** `app/Models/StaffSchedule.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffSchedule extends Model
{
    protected $table      = 'staff_schedule';
    protected $primaryKey = 'id_schedule';

    protected $fillable = ['id_staff', 'hari', 'start_time', 'end_time', 'is_available'];

    protected function casts(): array
    {
        return ['is_available' => 'boolean'];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function staff() { return $this->belongsTo(Staff::class, 'id_staff'); }
}
```

**Catatan:** `hari` → enum: `Monday | Tuesday | Wednesday | Thursday | Friday | Saturday | Sunday`

---

## 12. Model `OrderDetail`

**File:** `app/Models/OrderDetail.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table      = 'order_detail';
    protected $primaryKey = 'id_order_detail';

    protected $fillable = [
        'id_order', 'id_service', 'id_staff',
        'start_time', 'end_time',
        'harga_at_order', 'subtotal', 'status',
    ];

    protected function casts(): array
    {
        return [
            'harga_at_order' => 'decimal:2',
            'subtotal'       => 'decimal:2',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function order()   { return $this->belongsTo(Order::class, 'id_order'); }
    public function service() { return $this->belongsTo(Service::class, 'id_service'); }
    public function staff()   { return $this->belongsTo(Staff::class, 'id_staff'); }

    // ──── Scope ─────────────────────────────────────────────
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
```

---

## 13. Model `Pembayaran`

**File:** `app/Models/Pembayaran.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table      = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';

    protected $fillable = [
        'id_order', 'id_user',
        'metode_pembayaran', 'jumlah_bayar',
        'status_pembayaran', 'tanggal_bayar',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_bayar'  => 'decimal:2',
            'tanggal_bayar' => 'datetime',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────
    public function order() { return $this->belongsTo(Order::class, 'id_order'); }
    public function user()  { return $this->belongsTo(User::class, 'id_user'); }
}
```

---

## 🔗 Tabel Pivot (Many-to-Many)

### Pivot: `user_promo`

Diakses melalui `User::promos()` atau `Promo::users()`. Kolom ekstra yang perlu di-`withPivot`:

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `is_used` | boolean | Apakah promo sudah dipakai |
| `used_at` | timestamp\|null | Waktu pemakaian promo |

### Pivot: `staff_service`

Diakses melalui `Staff::services()` atau `Service::staff()`. Tidak ada kolom ekstra, cukup `withTimestamps()`.

---

## ⚙️ Pola Penting yang Harus Dipahami

### 1. Custom Primary Key
Semua tabel menggunakan primary key non-default (bukan `id`). **Wajib** mendefinisikan:
```php
protected $primaryKey = 'id_salon'; // sesuaikan tiap model
```

### 2. SoftDeletes
Model dengan `SoftDeletes` perlu trait + kolom `deleted_at`:
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Salon extends Model
{
    use SoftDeletes; // aktifkan
}

// Query otomatis exclude deleted records
Salon::all();           // hanya active
Salon::withTrashed()->get(); // semua termasuk deleted
Salon::onlyTrashed()->get(); // hanya yang deleted
```

### 3. Eager Loading (Hindari N+1)
```php
// ❌ Buruk — N+1 query
$salons = Salon::all();
foreach ($salons as $salon) {
    echo $salon->kota->nama_kota; // query per iterasi
}

// ✅ Baik — eager load
$salons = Salon::with(['kota', 'services', 'staff'])->get();
```

### 4. Local Scope
Digunakan untuk filter yang sering dipakai:
```php
// Definisi di model
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

// Pemakaian
$activeSalons = Salon::active()->get();
$activePromos = Promo::active()->get();
```

### 5. Accessor & Mutator (Laravel 9+)
```php
use Illuminate\Database\Eloquent\Casts\Attribute;

// Accessor: ambil nama lengkap user
protected function fullName(): Attribute
{
    return Attribute::make(
        get: fn () => $this->first_name . ' ' . $this->last_name,
    );
}

// Pemakaian
$user->full_name; // "John Doe"
```

---

## 📁 Struktur File Model

```
app/
└── Models/
    ├── User.php           ✅ sudah ada (perlu diupdate)
    ├── Kota.php           ❌ belum dibuat
    ├── Kategori.php       ❌ belum dibuat
    ├── Salon.php          ❌ belum dibuat
    ├── Promo.php          ❌ belum dibuat
    ├── Service.php        ❌ belum dibuat
    ├── Staff.php          ❌ belum dibuat
    ├── Order.php          ❌ belum dibuat
    ├── Review.php         ❌ belum dibuat
    ├── SalonImage.php     ❌ belum dibuat
    ├── StaffSchedule.php  ❌ belum dibuat
    ├── OrderDetail.php    ❌ belum dibuat
    └── Pembayaran.php     ❌ belum dibuat
```

---

## 🚀 Perintah Artisan untuk Generate Model

```bash
# Generate semua model sekaligus
php artisan make:model Kota
php artisan make:model Kategori
php artisan make:model Salon
php artisan make:model Promo
php artisan make:model Service
php artisan make:model Staff
php artisan make:model Order
php artisan make:model Review
php artisan make:model SalonImage
php artisan make:model StaffSchedule
php artisan make:model OrderDetail
php artisan make:model Pembayaran

# Generate model + migration sekaligus (kalau belum ada migration)
php artisan make:model NamaModel -m

# Generate model + factory + seeder
php artisan make:model NamaModel -mfs
```

---

## ✅ Checklist Implementasi

- [ ] Update `User.php` — tambah relasi & `SoftDeletes`
- [ ] Buat `Kota.php`
- [ ] Buat `Kategori.php`
- [ ] Buat `Salon.php`
- [ ] Buat `Promo.php`
- [ ] Buat `Service.php`
- [ ] Buat `Staff.php`
- [ ] Buat `Order.php`
- [ ] Buat `Review.php`
- [ ] Buat `SalonImage.php`
- [ ] Buat `StaffSchedule.php`
- [ ] Buat `OrderDetail.php`
- [ ] Buat `Pembayaran.php`
- [ ] Test semua relasi dengan `php artisan tinker`
- [ ] Tambahkan Scope aktif untuk `Salon`, `Promo`, `Service`, `Staff`

---

*Dibuat otomatis berdasarkan analisis migration VIYGO — 2026*
