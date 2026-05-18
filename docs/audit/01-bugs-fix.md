# 01 — Bug Fix Report

> Tanggal eksekusi: 2026-05-16
> Dikerjakan oleh: Fullstack Dev (AI Agent)
> Referensi audit: `01-bugs.md`

---

## Ringkasan

| ID       | Severity   | Judul                                                         | Status       |
|----------|-----------|---------------------------------------------------------------|--------------|
| BUG-A01  | 🔴 Critical | Race condition double-booking slot                            | ✅ Fixed      |
| BUG-A02  | 🔴 Critical | Mass-assignment privilege escalation pada `User`              | ✅ Fixed      |
| BUG-A03  | 🔴 Critical | `.env` ter-commit dengan kredensial Midtrans + APP_KEY        | ⚠️ Manual    |
| BUG-A04  | 🟠 High    | `DatabaseSeeder::run()` truncate semua tabel tanpa guard      | ✅ Fixed      |
| BUG-A05  | 🟠 High    | `Order` model hardcode status string di scope                 | ✅ Fixed      |
| BUG-A06  | 🟠 High    | `PaymentController::finish()` & `CompleteBookings` hardcode   | ✅ Fixed      |
| BUG-A07  | 🟠 High    | `BookingController::batal()` salah blokir pembatalan same-day | ✅ Fixed      |
| BUG-A08  | 🟠 High    | `refund_key` tidak unik antar attempt                         | ✅ Fixed      |
| BUG-A09  | 🟠 High    | `AkunController::bookings` leak semua order untuk tab unknown | ✅ Fixed      |
| BUG-A10  | 🟠 High    | `convertGbpToIdr` tanpa batas atas Midtrans limit             | ✅ Fixed      |
| BUG-A11  | 🟡 Medium  | `itemDetails()` price negatif / gross_amount mismatch         | ✅ Fixed      |
| BUG-A12  | 🟡 Medium  | `KategoriController::showSub` kirim stdClass bukan model      | ✅ Fixed      |
| BUG-A13  | 🟡 Medium  | `ReviewObserver::recompute()` tidak handle salon soft-deleted | ✅ Fixed      |
| BUG-A14  | 🟡 Medium  | `Salon::scopeActive` tidak filter owner non-aktif             | ✅ Fixed      |
| BUG-A15  | 🟡 Medium  | `updatePengaturan` tidak menyimpan `phone_number`             | ✅ Fixed      |
| BUG-A16  | 🟢 Low     | `CheckRole::handle` redundant property check                  | ✅ Fixed      |
| BUG-A17  | 🟢 Low     | Migration baseline `cancelled` (British) vs `canceled`        | ⚠️ Skipped   |
| BUG-A18  | 🟢 Low     | `order_detail.status` ditulis literal `'pending'`            | ✅ Fixed      |

---

## Detail Pengerjaan

### ✅ BUG-A01 — Race condition double-booking slot
**File**: `app/Http/Controllers/BookingController.php`

**Apa yang dilakukan:**
- Dibuat blok `try/catch` untuk `QueryException` (SQLSTATE 23000).
- Di dalam `DB::transaction`, ditambahkan `OrderDetail::lockForUpdate()` pada baris order_detail yang berpotensi konflik sebelum re-verify slot.
- Re-verify slot dilakukan _di dalam_ transaction sehingga dua request yang bersamaan akan terserialisasi.
- Jika slot sudah diambil, throw `RuntimeException('SLOT_TAKEN')` → controller return `back()->withErrors(['waktu' => '...'])`.

```php
// P0-4 / BUG-A01: Pessimistic lock sebelum insert
OrderDetail::whereIn('id_staff', array_filter([$resolvedStaffId]))
    ->whereHas('order', fn ($q) => $q->whereDate('date_order', $data['tanggal']))
    ->lockForUpdate()
    ->get();
```

---

### ✅ BUG-A02 — Mass-assignment privilege escalation pada `User`
**File**: `app/Models/User.php`

**Apa yang dilakukan:**
- Dihapus `'role'` dan `'is_active'` dari `$fillable`.
- Ditambahkan `$guarded = ['role', 'is_active', 'id_user']`.
- Setiap eksplisit assignment (`$user->role = 'admin'; $user->save()`) tetap berfungsi.

```php
protected $fillable = [
    'first_name', 'last_name', 'email', 'password', 'phone_number', 'profile_url',
];
protected $guarded = ['role', 'is_active', 'id_user'];
```

---

### ⚠️ BUG-A03 — `.env` ter-commit dengan kredensial
**Status**: Memerlukan tindakan manual oleh developer/owner.

**Langkah yang harus dilakukan:**
1. Login Midtrans Sandbox → regenerate Server Key + Client Key.
2. `git rm --cached .env` lalu commit.
3. `php artisan key:generate` di setiap environment.
4. Set `APP_DEBUG=false` di staging/production.
5. Pastikan `.gitignore` memuat `/.env`.

---

### ✅ BUG-A04 — `DatabaseSeeder::run()` truncate tanpa guard
**File**: `database/seeders/DatabaseSeeder.php`

**Apa yang dilakukan:**
- Ditambahkan environment guard dengan `app()->environment('production')`.
- Jika environment production dan user tidak konfirmasi, seeder berhenti dengan warning.

```php
if (app()->environment('production') && ! $this->command->confirm(
    '⚠️  You are about to TRUNCATE all VIYGO tables in PRODUCTION. Continue?',
    false
)) {
    $this->command->warn('Seeding aborted by user.');
    return;
}
```

---

### ✅ BUG-A05 — `Order` model hardcode status string di scope
**File**: `app/Models/Order.php`

**Apa yang dilakukan:**
- Ditambahkan `use App\Constants\OrderStatus;`.
- `scopePending` dan `scopeSuccess` menggunakan constant `OrderStatus::PENDING` dan `OrderStatus::SUCCESS`.
- Ditambahkan `scopeConfirmed` dan `scopeCanceled` untuk kelengkapan.

---

### ✅ BUG-A06 — `PaymentController::finish()` & `CompleteBookings` hardcode strings
**File 1**: `app/Http/Controllers/PaymentController.php`
**File 2**: `app/Console/Commands/CompleteBookings.php`

**Apa yang dilakukan:**
- `finish()`: Mengganti `in_array($order->status, ['confirmed', 'success'])` → `[OrderStatus::CONFIRMED, OrderStatus::SUCCESS]`.
- `finish()`: Mengganti `$order->status = 'confirmed'` → `OrderStatus::CONFIRMED`.
- `CompleteBookings`: Mengganti `where('status', 'confirmed')` → `OrderStatus::CONFIRMED`.
- `CompleteBookings`: Mengganti `'status' => 'success'` → `OrderStatus::SUCCESS`.
- Menambahkan `use App\Constants\OrderStatus;` ke `CompleteBookings`.

---

### ✅ BUG-A07 — `BookingController::batal()` salah blokir pembatalan same-day
**File**: `app/Http/Controllers/BookingController.php:196-200`

**Root cause**: `date_order` di-cast ke `'date'` (Carbon midnight), sehingga `isPast()` = `true` sejak jam 00:01 pada hari janji.

**Fix**:
```php
$firstDetail = $order->details()->orderBy('start_time')->first();
$startAt = $firstDetail && $firstDetail->start_time
    ? Carbon::parse($order->date_order->toDateString() . ' ' . $firstDetail->start_time)
    : $order->date_order->copy()->endOfDay();

if ($startAt->isPast()) {
    return back()->withErrors(['cancel' => 'This appointment has already started...']);
}
```

---

### ✅ BUG-A08 — `Midtrans refund_key` tidak unik antar attempt
**File**: `app/Http/Controllers/BookingController.php`

**Fix**:
```php
'refund_key' => 'refund_' . $order->kode_order . '_' . now()->format('YmdHis'),
```

---

### ✅ BUG-A09 — `AkunController::bookings` jatuh ke "show all" untuk tab tak dikenal
**File**: `app/Http/Controllers/AkunController.php`

**Fix**: Validasi tab sebelum query; fallback ke `'mendatang'` jika tidak dikenal.
```php
$tab = in_array($request->get('tab', 'mendatang'), array_keys($statusMap), true)
    ? $request->get('tab', 'mendatang')
    : 'mendatang';
$orders = Order::where('id_user', auth()->id())
    ->whereIn('status', $statusMap[$tab])  // selalu filter, tidak skip
    ->...
```

---

### ✅ BUG-A10 — `convertGbpToIdr` tanpa batas atas Midtrans
**File**: `app/Http/Controllers/PaymentController.php`

**Fix**: Tambah ceiling check. Melempar `DomainException` yang harus ditangkap caller dengan 422.
```php
if ($idr > 999_999_999) {
    throw new \DomainException('Order amount exceeds Midtrans single-transaction limit.');
}
```

---

### ✅ BUG-A11 — `itemDetails()` bisa menghasilkan gross_amount mismatch
**File**: `app/Http/Controllers/PaymentController.php`

**Fix**: `gross_amount` diturunkan dari `sum(items[*].price)` setelah konversi IDR, bukan dari `total_pembayaran` GBP secara independen. Ditambahkan `_resolvedGrossAmount` property dan `resolvedGrossAmount()` helper.

---

### ✅ BUG-A12 — `KategoriController::showSub` kirim stdClass
**File**: `app/Http/Controllers/KategoriController.php`

**Fix**: Mengganti cast `(object)[...]` dengan model asli dari relasi `$sub->kategori`.
```php
$kategori = $sub->kategori; // Actual Eloquent model, bukan stdClass
return view('kategori.show', compact('kategori', 'salons', 'sub'));
```

---

### ✅ BUG-A13 — `ReviewObserver::recompute()` tidak handle salon soft-deleted
**File**: `app/Observers/ReviewObserver.php`

**Fix**:
```php
$salon = Salon::withTrashed()->find($idSalon);
```

---

### ✅ BUG-A14 — `Salon::scopeActive` tidak filter owner non-aktif
**File**: `app/Models/Salon.php`

**Fix**:
```php
public function scopeActive($query)
{
    return $query->where('status', 'active')
        ->whereHas('owner', fn ($q) => $q->where('is_active', true));
}
```

---

### ✅ BUG-A15 — `AkunController::updatePengaturan` tidak simpan `phone_number`
**File**: `app/Http/Controllers/AkunController.php`

**Fix**: Tambah validasi dan include `phone_number` di `$request->only(...)`.
```php
'phone_number' => 'nullable|string|max:30',
// ...
auth()->user()->update($request->only('first_name', 'last_name', 'email', 'phone_number'));
```

---

### ✅ BUG-A16 — `CheckRole::handle` redundant property check
**File**: `app/Http/Middleware/CheckRole.php`

**Fix**: Menghapus `property_exists()` / `isset()` check (selalu false untuk Eloquent). Langsung cek `$user->is_active === false`.

---

### ⚠️ BUG-A17 — Migration baseline `cancelled` (British spelling)
**Status**: Skipped (Low priority, hanya dilakukan jika tidak ada environment yang sudah jalan dengan migration lama).

---

### ✅ BUG-A18 — `order_detail.status` ditulis literal `'pending'`
**File**: `app/Http/Controllers/BookingController.php`
**File baru**: `app/Constants/OrderDetailStatus.php`

**Fix**: Dibuat class `OrderDetailStatus` dengan constant `PENDING`, `IN_PROGRESS`, `COMPLETED`, `CANCELED`. Dipakai di `BookingController` saat create order_detail.

---

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `app/Models/User.php` | BUG-A02: Hapus role/is_active dari fillable |
| `app/Models/Order.php` | BUG-A05: Use OrderStatus constants di scopes |
| `app/Models/Salon.php` | BUG-A14: scopeActive filter owner aktif |
| `app/Http/Controllers/BookingController.php` | BUG-A01, A07, A08, A18 |
| `app/Http/Controllers/PaymentController.php` | BUG-A06, A10, A11 |
| `app/Http/Controllers/AkunController.php` | BUG-A09, A15 |
| `app/Http/Controllers/KategoriController.php` | BUG-A12 |
| `app/Console/Commands/CompleteBookings.php` | BUG-A06 |
| `app/Observers/ReviewObserver.php` | BUG-A13 |
| `app/Http/Middleware/CheckRole.php` | BUG-A16 |
| `app/Constants/OrderDetailStatus.php` | **BARU** — BUG-A18 |
