# Laporan Perubahan — VIYGO Beauty Marketplace

> Tanggal: 19 Mei 2026  
> Dikerjakan pada sesi ini (belum di-commit)

---

## 1. Fix: Middleware CSRF Laravel 12

**File:** `app/Providers/Filament/AdminPanelProvider.php`, `app/Providers/Filament/OwnerPanelProvider.php`

**Masalah:**  
`BindingResolutionException` — `Target class [Illuminate\Foundation\Http\Middleware\PreventRequestForgery] does not exist.`  
Di Laravel 12, kelas CSRF middleware diganti namanya.

**Perubahan:**
- Ganti `use Illuminate\Foundation\Http\Middleware\PreventRequestForgery` → `use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken`
- Ganti `PreventRequestForgery::class` → `ValidateCsrfToken::class` di array middleware kedua panel

---

## 2. Fix: Dark Mode Glitch di Filament Admin

**File:** `app/Providers/Filament/AdminPanelProvider.php`, `app/Providers/Filament/OwnerPanelProvider.php`

**Masalah:**  
Saat berpindah halaman di admin, terjadi flicker antara dark mode dan light mode karena Filament mencoba mendeteksi preferensi sistem saat setiap navigasi.

**Perubahan:**
- Tambah `->darkMode(false)` di kedua panel provider agar mode selalu konsisten (light mode)

---

## 3. Fix: Routing Error `/admin/salons` — Missing Parameter: record

**File:** `app/Filament/Resources/SalonResource.php`

**Masalah:**  
`UrlGenerationException` — `Missing required parameter for [Route: filament.admin.resources.salons.view] [URI: admin/salons/{record}]`

**Akar masalah:**  
- 6.379 salon di database memiliki kolom `slug = NULL`
- Model `Salon` menggunakan `getRouteKeyName() = 'slug'` untuk frontend
- Saat Filament merender tabel dan generate URL per baris, Laravel memanggil `$record->getRouteKey()` yang mengembalikan `null` → dianggap parameter hilang
- `getRecordRouteKeyName()` hanya memengaruhi *route binding* (resolve), bukan *URL generation*

**Perubahan:**
- Tambah `->recordUrl(fn (Salon $record) => static::getUrl('view', ['record' => $record->id_salon]))` untuk row click
- Tambah `->url(fn (Salon $record) => static::getUrl('view', ['record' => $record->id_salon]))` pada `ViewAction`
- Tambah `->url(fn (Salon $record) => static::getUrl('edit', ['record' => $record->id_salon]))` pada `EditAction`
- Tambah `getRecordRouteKeyName(): 'id_salon'` agar route binding juga pakai `id_salon`
- Jalankan `php artisan optimize:clear` untuk membersihkan cache

---

## 4. Fitur Baru: Input Kode Promo di Halaman Booking

**File:**
- `routes/web.php`
- `app/Http/Controllers/BookingController.php`
- `app/Models/Promo.php`
- `resources/views/booking/create.blade.php`

**Latar belakang:**  
Tabel `promo` dan `user_promo` sudah ada di database lengkap dengan field `kode_promo`, `diskon`, `tipe_promo` (percentage/fixed), `min_transaksi`, `stock`, dll. Model `Order` sudah punya kolom `id_promo` dan `total_diskon`. Namun **tidak ada UI** untuk user memasukkan kode promo di alur booking.

### 4a. Route Baru

```
POST /promo/validate  →  BookingController@validatePromo  (name: promo.validate)
```

### 4b. BookingController — `validatePromo()`

Endpoint AJAX untuk validasi kode promo sebelum submit:
- Cek promo aktif & belum expired (`Promo::byCode()`)
- Cek minimum transaksi (`meetsMinimum()`)
- Cek stok (`isSoldOut()`)
- Hitung diskon (`calculateDiscount()`)
- Return JSON: `{ valid, id_promo, nama_promo, diskon, tipe }`

### 4c. BookingController — Update `store()`

- Tambah validasi field `kode_promo` (nullable)
- Re-validasi promo di server saat form disubmit (mencegah tampering dari client)
- Re-check stok di dalam `DB::transaction` dengan pessimistic lock
- `$promo->increment('used_counter')` di dalam transaksi (race-condition safe)
- Simpan `id_promo`, `total_diskon`, dan `total_pembayaran` (setelah diskon) ke tabel `order`
- Tambah exception handler `PROMO_EXHAUSTED`

### 4d. View — Step 3 Booking (Confirm)

Penambahan di `resources/views/booking/create.blade.php`:

**UI Elements:**
- Input text kode promo (huruf kapital otomatis)
- Tombol "Apply" / "Remove" (toggle)
- Pesan error inline (kode tidak valid, stok habis, minimum tidak terpenuhi)
- Pesan sukses inline (nama promo + tipe diskon)
- Baris "Discount" (muncul saat promo diterapkan, warna hijau)
- Baris "Total After Discount" (muncul saat promo diterapkan)
- Hidden input `kode_promo` untuk dikirim bersama form

**Alpine.js state baru:**
| State | Tipe | Fungsi |
|---|---|---|
| `promoCode` | `string` | Nilai input kode promo |
| `promoApplied` | `object\|null` | Data promo yang berhasil diapply |
| `promoError` | `string` | Pesan error validasi |
| `promoLoading` | `boolean` | Loading state saat fetch |
| `finalPrice` (computed) | `number` | `totalPrice - diskon` |

**Alpine.js method baru:**
- `applyPromo()` — fetch ke `/promo/validate`, update state
- `removePromo()` — reset semua state promo

---

## 5. Refactor: Konsolidasi Logic Promo ke Model

**File:** `app/Models/Promo.php`, `app/Http/Controllers/BookingController.php`

Setelah code review (simplify), duplikasi logika ditemukan antara `validatePromo` dan `store`.

**Ditambahkan ke `Promo` model:**

```php
// Lookup by code (normalized)
public function scopeByCode($query, string $code)

// Check minimum transaction
public function meetsMinimum(float $total): bool

// Check stock exhausted
public function isSoldOut(): bool

// Calculate discount (percentage or fixed, with cap)
public function calculateDiscount(float $total): float
```

**Hasil:** Kedua controller method sekarang memanggil method yang sama — tidak ada lagi duplikasi kalkulasi diskon.

---

## Ringkasan File yang Diubah

| File | Jenis Perubahan |
|---|---|
| `app/Providers/Filament/AdminPanelProvider.php` | Fix middleware + dark mode |
| `app/Providers/Filament/OwnerPanelProvider.php` | Fix middleware + dark mode |
| `app/Filament/Resources/SalonResource.php` | Fix routing URL generation |
| `app/Http/Controllers/BookingController.php` | Fitur promo + refactor |
| `app/Models/Promo.php` | Tambah business logic methods |
| `resources/views/booking/create.blade.php` | UI input kode promo |
| `routes/web.php` | Route validasi promo |
