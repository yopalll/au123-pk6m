# 01 — Bugs (Active)

> Semua bug di file ini **terverifikasi terhadap kode terkini per 2026-05-16**. Setiap entri menyertakan lokasi `file:line`, gejala, root-cause, dan patch siap-tempel.
> Severity: 🔴 Critical · 🟠 High · 🟡 Medium · 🟢 Low

---

## 🔴 BUG-A01 — Race condition: double-booking slot tidak ter-prevent di level DB

**File**: [`app/Services/BookingSlotService.php`](../app/Services/BookingSlotService.php), [`app/Http/Controllers/BookingController.php:138-171`](../app/Http/Controllers/BookingController.php#L138)
**Severity**: 🔴 Critical
**Tabel terkait**: `order_detail` — tidak ada UNIQUE constraint pada `(id_staff, start_time)` per tanggal.

### Gejala
Dua user men-tekan tombol "Continue to Payment →" hampir bersamaan untuk slot yang sama. Server `isSlotAvailableForDuration` keduanya return `true` karena belum ada commit, lalu dua-duanya menulis `order_detail` dengan `(id_staff=5, date=2026-05-20, start_time=10:00)`.

### Root cause
`BookingController::store()` melakukan re-check **di luar** `DB::transaction`. Transaction hanya melindungi penulisan, bukan check. Lock pessimistic juga belum digunakan.

### Patch (recommended)
1. **DB**: tambah migration unique partial index (MySQL 8 supports it via generated column):
   ```php
   Schema::table('order_detail', function (Blueprint $t) {
       $t->index(['id_staff','start_time']);
   });
   // Kemudian unique-key composite via raw, tapi karena MySQL tidak support partial unique
   // on (id_staff, date_order, start_time) — buat via DB trigger atau:
   //   - taruh `date_order` di order_detail sebagai kolom generated dari relasi (denormalize),
   //   - lalu unique (id_staff, date_order, start_time) WHERE status != 'canceled'.
   ```
2. **Service layer**: bungkus check+insert dalam satu transaction dengan `lockForUpdate` pada baris busy:
   ```php
   DB::transaction(function () use (...) {
       // SELECT ... FOR UPDATE pada order_detail yang berpotensi konflik
       OrderDetail::whereIn('id_staff', $staffIds)
           ->whereHas('order', fn($q) => $q->whereDate('date_order', $date))
           ->lockForUpdate()
           ->get();

       if (!$this->slots->isSlotAvailableForDuration(...)) {
           throw new SlotTakenException;
       }

       // insert order + order_detail
   });
   ```
3. Tangkap `QueryException` (kode 23000 / duplicate) di controller dan return `back()->withErrors(['waktu' => 'Slot taken'])`.

---

## 🔴 BUG-A02 — Mass-assignment privilege escalation pada `User`

**File**: [`app/Models/User.php:26-35`](../app/Models/User.php#L26)
**Severity**: 🔴 Critical (security)

### Gejala
`$fillable` mengandung `role` dan `is_active`. Setiap kontrol yang lupa pakai `$request->only(...)` membuka pintu: customer kirim `role=admin` lewat form Settings dan bisa promosi diri sendiri.

Saat audit, `AkunController::updatePengaturan()` aman karena pakai `$request->only(...)`. Tapi proteksinya hanya di 1 titik; setiap endpoint baru bisa salah pakai.

### Patch
```php
// app/Models/User.php
protected $fillable = [
    'first_name', 'last_name', 'email', 'password', 'phone_number', 'profile_url',
    // ⛔ HAPUS: 'role', 'is_active'
];

protected $guarded = ['role', 'is_active', 'id_user'];
```
Lalu eksplisit set `role` & `is_active` lewat assignment property (`$user->role = 'admin'; $user->save();`), bukan mass-assign.

---

## 🔴 BUG-A03 — `.env` ter-commit dengan kredensial Midtrans + APP_KEY asli

**File**: [`.env`](../.env)
**Severity**: 🔴 Critical (security)

Berisi:
- `APP_KEY=base64:pK93LW...`
- `MIDTRANS_SERVER_KEY=Mid-server-lc3MUQ0AvraQ8pvr9SONHWgF`
- `MIDTRANS_CLIENT_KEY=Mid-client-nvxeilk0GupI1tWd`
- `DB_PASSWORD=localpw`
- `APP_DEBUG=true` + `APP_URL=https://shining-frequently-crappie.ngrok-free.app`

### Patch
1. Verifikasi `.gitignore` (sudah ada `.env`?). File `.env` tampak hadir di working tree — pastikan tidak ter-track:
   ```bash
   git rm --cached .env
   ```
2. **Rotasi sekarang** server-key & client-key Midtrans Sandbox.
3. Generate `APP_KEY` baru di setiap environment via `php artisan key:generate`.
4. Untuk production: `APP_DEBUG=false`. (Detail lebih lengkap di [`04-security.md`](04-security.md).)

---

## 🟠 BUG-A04 — `DatabaseSeeder::run()` melakukan `truncate` pada 11 tabel inti

**File**: [`database/seeders/DatabaseSeeder.php:31-41`](../database/seeders/DatabaseSeeder.php#L31)
**Severity**: 🟠 High

### Gejala
Eksekusi `php artisan db:seed` di production = **data wipe** (`users`, `salon`, `service`, semua pivot, dll). Tidak ada konfirmasi `--force` / environment check.

### Patch
```php
public function run(): void
{
    if (app()->environment('production') && ! $this->command->confirm('Truncate all VIYGO tables in production?')) {
        $this->command->warn('Aborted.');
        return;
    }
    // ... existing code
}
```
Atau, lebih baik: pisahkan seeder destructive ke class `DatabaseSeederFresh` dan biarkan `DatabaseSeeder` melakukan **upsert** idempoten saja.

---

## 🟠 BUG-A05 — `Order` model masih hard-code status string di scope

**File**: [`app/Models/Order.php:74-82`](../app/Models/Order.php#L74)
**Severity**: 🟠 High (consistency)

```php
public function scopePending($query)
{
    return $query->where('status', 'pending');     // ← hardcoded
}

public function scopeSuccess($query)
{
    return $query->where('status', 'success');     // ← hardcoded
}
```

### Patch
```php
use App\Constants\OrderStatus;

public function scopePending($query)   { return $query->where('status', OrderStatus::PENDING); }
public function scopeConfirmed($query) { return $query->where('status', OrderStatus::CONFIRMED); }
public function scopeSuccess($query)   { return $query->where('status', OrderStatus::SUCCESS); }
public function scopeCanceled($query)  { return $query->where('status', OrderStatus::CANCELED); }
```

---

## 🟠 BUG-A06 — `PaymentController::finish()` & `CompleteBookings` hardcode status strings

**File 1**: [`app/Http/Controllers/PaymentController.php:169, 221, 228`](../app/Http/Controllers/PaymentController.php#L169)
**File 2**: [`app/Console/Commands/CompleteBookings.php:25, 40`](../app/Console/Commands/CompleteBookings.php#L25)
**Severity**: 🟠 High (regression risk + consistency)

### Gejala
`finish()` membandingkan `in_array($order->status, ['confirmed', 'success'])` dan menulis `$order->status = 'confirmed';` — bukan pakai constant. Webhook handler sudah pakai constant, tapi `finish()` belum (inkonsisten).

### Patch
```php
// PaymentController::finish — line 169
if (in_array($order->status, [OrderStatus::CONFIRMED, OrderStatus::SUCCESS])) {

// line 221, 228
$order->status = OrderStatus::CONFIRMED;

// CompleteBookings.php
$query = Order::where('status', OrderStatus::CONFIRMED)->whereDate('date_order','<',today());
// ...
$query->update(['status' => OrderStatus::SUCCESS]);
```

---

## 🟠 BUG-A07 — `BookingController::batal()` salah memblokir pembatalan hari yang sama

**File**: [`app/Http/Controllers/BookingController.php:196-200`](../app/Http/Controllers/BookingController.php#L196)
**Severity**: 🟠 High

### Gejala
```php
if ($order->date_order && $order->date_order->isPast()) {
    return back()->withErrors(['cancel' => 'This appointment has already passed and cannot be cancelled.']);
}
```
Karena `Order::$casts['date_order'] = 'date'` (Carbon date di-strip ke 00:00), `isPast()` akan `true` setiap kali sekarang sudah > 00:00 pada hari janji. Akibatnya **user tidak bisa cancel appointment yang baru di-book hari itu** (misal book jam 09:00 untuk jam 18:00, jam 09:01 sudah dianggap "passed").

### Patch
```php
// Gunakan kombinasi tanggal + start_time dari order_detail pertama.
$firstDetail = $order->details()->orderBy('start_time')->first();
$startAt = $firstDetail && $firstDetail->start_time
    ? Carbon::parse($order->date_order->toDateString().' '.$firstDetail->start_time)
    : $order->date_order->copy()->endOfDay();

if ($startAt->isPast()) {
    return back()->withErrors(['cancel' => 'This appointment has already started and cannot be cancelled.']);
}
```

---

## 🟠 BUG-A08 — `Midtrans refund_key` tidak unik antar attempt

**File**: [`app/Http/Controllers/BookingController.php:207-211`](../app/Http/Controllers/BookingController.php#L207)
**Severity**: 🟠 High

```php
'refund_key' => 'refund_' . $order->kode_order,
```

### Gejala
Midtrans menolak refund jika `refund_key` sudah pernah dipakai. Skenario: refund pertama partial-fail (timeout di luar Midtrans), `pembayaran.status` belum sempat update. User klik cancel lagi — Midtrans return 412 karena `refund_key` duplikat, tapi server bilang "Refund failed".

### Patch
```php
'refund_key' => 'refund_' . $order->kode_order . '_' . now()->format('YmdHis'),
```

---

## 🟠 BUG-A09 — `AkunController::bookings` jatuh ke "show all" untuk tab tak dikenal

**File**: [`app/Http/Controllers/AkunController.php:37-42`](../app/Http/Controllers/AkunController.php#L37)
**Severity**: 🟠 High (information leak)

```php
->when(isset($statusMap[$tab]), fn ($q) => $q->whereIn('status', $statusMap[$tab]))
```

### Gejala
Jika `?tab=hax`, kondisi `when` skip → query return SEMUA order user, termasuk yang `canceled` yang seharusnya hanya muncul di tab "dibatalkan". Bug halus tapi memecah tab semantics dan bikin user bingung.

### Patch
```php
$tab = in_array($tab, array_keys($statusMap), true) ? $tab : 'mendatang';
$orders = Order::where('id_user', auth()->id())
    ->whereIn('status', $statusMap[$tab])
    ->with([...])
    ->latest()
    ->paginate(10)
    ->withQueryString();
```

---

## 🟠 BUG-A10 — `convertGbpToIdr` tidak punya batas atas, berisiko melampaui limit Midtrans

**File**: [`app/Http/Controllers/PaymentController.php:392-397`](../app/Http/Controllers/PaymentController.php#L392)
**Severity**: 🟠 High (payment failure)

Midtrans max `gross_amount` di Snap = 999,999,999 IDR (~£48,800 dengan rate 20,500). Order di atas batas akan ditolak dengan error "transaction_details.gross_amount is too high" — tanpa fallback UX di Snap page.

### Patch
```php
protected function convertGbpToIdr(float $gbpAmount): int
{
    $idr = (int) round($gbpAmount * (float) config('services.midtrans.exchange_rate', 20000));

    if ($idr > 999_999_999) {
        throw new \DomainException('Order amount exceeds Midtrans single-transaction limit.');
    }
    return $idr;
}
```
Lalu di `createSnapToken`, tangkap & return `response()->json(['error' => 'Amount exceeds gateway limit; please split your booking.'], 422)`.

---

## 🟡 BUG-A11 — `itemDetails()` bisa menghasilkan harga item negatif untuk Midtrans

**File**: [`app/Http/Controllers/PaymentController.php:418-427`](../app/Http/Controllers/PaymentController.php#L418)
**Severity**: 🟡 Medium

```php
$items[] = [
    'id'    => 'ADJ',
    'name'  => $diff > 0 ? 'Booking fee' : 'Discount',
    'price' => $diff,        // ← bisa negatif
    'quantity' => 1,
];
```

### Gejala
Midtrans mengizinkan item price negatif (untuk diskon), tapi `sum(item.price * qty)` **harus** = `gross_amount`. Saat ada promo dengan rounding floor/ceil ke IDR, total bisa meleset karena `gross_amount` di-recalc independen dari `harga_at_order`. Skenario produksi: Snap throw "transaction_details.gross_amount does not match item_details.

### Patch
Hitung `gross_amount` dari `sum(items[*].price)` **setelah** adjustment, bukan dari `total_pembayaran` GBP:
```php
$grossAmountIdr = array_sum(array_map(fn($i)=>$i['price']*$i['quantity'], $items));
// kirim ke transaction_details.gross_amount
```

---

## 🟡 BUG-A12 — `KategoriController::showSub` mengirim `$kategori` sebagai `stdClass`, view butuh model

**File**: [`app/Http/Controllers/KategoriController.php:116-122`](../app/Http/Controllers/KategoriController.php#L116)
**Severity**: 🟡 Medium

```php
$kategori = (object) [
    'name' => $sub->name,
    'slug' => $sub->slug,
    'deskripsi' => $sub->deskripsi,
];
return view('kategori.show', compact('kategori', 'salons', 'sub'));
```

### Gejala
View `kategori/show.blade.php` (untuk halaman utama `/kategori/{slug}`) bisa pakai `$kategori->subKategori`, `$kategori->id_kategori`, dll. Pada path sub-kategori, properti itu `undefined` → error "Undefined property" atau halaman parsial.

### Patch
Buat view terpisah `kategori/show-sub.blade.php` atau pastikan template menggunakan `??` guard. Idealnya: kirim instance `Kategori` asli dari relasi `$sub->kategori` + flag `$sub`.

---

## 🟡 BUG-A13 — `ReviewObserver::recompute()` tidak handle salon soft-deleted

**File**: [`app/Observers/ReviewObserver.php:27-44`](../app/Observers/ReviewObserver.php#L27)
**Severity**: 🟡 Medium

```php
$salon = Salon::find($idSalon);
```
`Salon` pakai `SoftDeletes` → soft-deleted salon return `null` dan recompute silent-skip. Tapi rating salon yang sudah soft-deleted bisa berbeda saat di-restore. Juga jika reviewer me-edit review setelah salon dihapus → loose-data.

### Patch
```php
$salon = Salon::withTrashed()->find($idSalon);
if (! $salon) return;
```

---

## 🟡 BUG-A14 — `Salon::scopeActive` tidak filter berdasarkan owner non-aktif

**File**: [`app/Models/Salon.php:123-126`](../app/Models/Salon.php#L123)
**Severity**: 🟡 Medium

Salon dengan owner `is_active=false` masih masuk listing publik. Bisa menyebabkan booking ke salon yang ownernya sudah dinonaktifkan.

### Patch
```php
public function scopeActive($query)
{
    return $query->where('status', 'active')
        ->whereHas('owner', fn($q) => $q->where('is_active', true));
}
```

---

## 🟡 BUG-A15 — `AkunController::updatePengaturan` tidak menyimpan `phone_number`

**File**: [`app/Http/Controllers/AkunController.php:91-102`](../app/Http/Controllers/AkunController.php#L91)
**Severity**: 🟡 Medium

```php
$request->validate([... 'email' => ...]);
auth()->user()->update($request->only('first_name', 'last_name', 'email'));
```

### Gejala
`phone_number` ada di `User::$fillable` dan dipakai Midtrans `customer_details.phone`, tapi user tidak punya UI untuk meng-update-nya selain registrasi.

### Patch
Tambah validasi + `only()` include `phone_number`, dan tambahkan field di view `akun/pengaturan.blade.php`.

---

## 🟢 BUG-A16 — `CheckRole::handle` redundant property check

**File**: [`app/Http/Middleware/CheckRole.php:33-37`](../app/Http/Middleware/CheckRole.php#L33)
**Severity**: 🟢 Low

```php
if (property_exists($user, 'is_active') || isset($user->is_active)) {
    if ($user->is_active === false) { ... }
}
```
`property_exists()` selalu `false` untuk Eloquent dynamic attributes. Cek pertama mubazir.

### Patch
```php
if ($user->is_active === false) {
    abort(403, 'Your account is currently inactive.');
}
```

---

## 🟢 BUG-A17 — `OrderDetail` migration spelling `cancelled` di down() (legacy enum)

**File**: [`database/migrations/2026_04_12_000014_create_order_detail_table.php:29`](../database/migrations/2026_04_12_000014_create_order_detail_table.php#L29)
**Severity**: 🟢 Low

Migration awal menulis `'cancelled'` (British). Hotfix `2026_05_02_110000_*` me-normalisasi ke `'canceled'`. Bila migration di-rollback ke baseline (rare), enum lama kembali. Cosmetic — tinggalkan komentar atau ganti baseline.

### Patch (opsional)
Edit migration awal agar pakai `'canceled'` sejak baseline; lalu drop hotfix `_canonicalise_canceled.php`. Hati-hati: hanya boleh dilakukan jika tidak ada environment yang sudah jalan dengan migration order lama.

---

## 🟢 BUG-A18 — Inkonsistensi: `order_detail.status` ditulis literal `'pending'`, bukan constant

**File**: [`app/Http/Controllers/BookingController.php:166`](../app/Http/Controllers/BookingController.php#L166)
**Severity**: 🟢 Low

```php
'status' => 'pending',
```
`OrderStatus` di-define hanya untuk `order.status`. Sebenarnya enum `order_detail.status` punya domain berbeda (`pending|in_progress|completed|canceled`). Buat constant kelas terpisah:
```php
// app/Constants/OrderDetailStatus.php
class OrderDetailStatus {
    public const PENDING     = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED   = 'completed';
    public const CANCELED    = 'canceled';
}
```
Lalu pakai di `BookingController` & owner panel.

---

## Verifikasi Error Screenshot

| Screenshot | Path | Status |
|---|---|---|
| `eror_v1/Screenshot 2026-05-13 211330.png` | `app\Filament\Owner\Resources\SalonResource.php:33 — Class "Filament\Forms\Components\Section" not found` | ✅ **Sudah diperbaiki di kode** — file saat ini pakai `\Filament\Schemas\Components\Section::make(...)`. Cache view/config kemungkinan masih kotor. Jalankan `php artisan optimize:clear`. |
| `eror_v1/Screenshot 2026-05-13 211342.png` | Sama (`/owner/salons/{slug}/edit`) | ✅ Sama dengan di atas. |
| `eror_v1/Screenshot 2026-05-13 211813.png` | Dashboard Filament kosong (panel default Laravel Starter Kit) | ⚠️ **Anomaly**: panel `admin` belum di-rebrand. Lihat [`03-anomalies.md` → ANOM-09]. |
| `eror_v1/Screenshot 2026-05-13 211906.png` | `SQLSTATE[HY000]: 1364 Field 'first_name' doesn't have a default value` saat register | ✅ **Sudah diperbaiki** — `app/Actions/Fortify/CreateNewUser.php` versi sekarang split `$input['name']` → `first_name`/`last_name`. Tapi jika user clone repo + composer install + lupa run migration terbaru: tetap kena. Solusi: pastikan `php artisan migrate` & gunakan view registrasi yang kirim field `name`. |
| `eror_v1/Screenshot 2026-05-13 212114.png` | Admin Filament Kategori table — total services Men's = 0 | ✅ Data anomaly only — `Men's` kategori mungkin tidak punya service. Bisa di-flag di owner panel sebagai dashboard warning. |
| `eror_v2/Screenshot 2026-05-13 222833.png` | Booking page menampilkan service £0.00 dan £0.01 (Patch Tests) | ⚠️ **Anomaly UX** — service harga 0 muncul di pilihan booking. Lihat ANOM-04. |
| `eror_v2/Screenshot 2026-05-13 222845.png` | Sama, dengan satu service ter-select | Sama. |
