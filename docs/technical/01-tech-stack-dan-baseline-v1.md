# 01 — Tech Stack & Baseline Arsitektur V1

> **Tujuan dokumen:** Menjelaskan **apa yang sudah ada dan sudah jalan di V1**, supaya V2 dibangun *mengikuti* pola yang sama, bukan melawannya. Semua isi di sini **diverifikasi langsung dari source code** (bukan dari PRD).
>
> Baca ini sebelum menulis kode V2 apa pun.

---

## 1. Tech Stack (versi persis dari `composer.json`)

| Komponen | Versi | Catatan penting |
|----------|-------|-----------------|
| PHP | `^8.3` | Pakai fitur 8.3 (typed const, readonly, dll. boleh). |
| Laravel Framework | `^12.0` | Struktur Laravel 12 (skeleton ramping: `bootstrap/app.php`, tidak ada `Http/Kernel.php`). |
| **Filament** | **`5.6`** | ⚠️ **Filament v5**, BUKAN v3 seperti tertulis di PRD §10.1. Syntax Resource/Schema/Table mengikuti v5. |
| **Livewire** | **`^4.1`** | Livewire v4. |
| Livewire Flux | `^2.13.1` | Komponen UI Flux. |
| Laravel Fortify | `^1.34` | Auth backend (login, register, reset, **2FA**). Tidak ada Breeze/Jetstream UI. |
| Laravel Tinker | `^3.0` | REPL untuk verifikasi. |
| **Midtrans PHP SDK** | **`^2.6`** | ✅ **Sudah terinstall.** Dipakai `PaymentController`. |
| Pest | `^4.5` (dev) | Framework test (bukan PHPUnit murni). |
| Laravel Pint | `^1.27` (dev) | Code style. Jalankan `composer lint`. |

**Frontend / build:** TailwindCSS v4 + Vite (`vite.config.js`, `package.json`). Mobile-first (lihat PRD §14).

### Yang BELUM ada (wajib ditambah di Phase 1 V2)
| Paket / config | Untuk |
|----------------|-------|
| `barryvdh/laravel-dompdf` | Invoice PDF (Modul 5 & pesanan produk). **Belum terinstall.** |
| `config/ongkir.php` | Konfigurasi api.co.id. Belum ada. |
| Env `API_CO_ID_KEY`, `ONGKIR_ORIGIN_CITY` | Ongkir. Belum ada. |

---

## 2. Struktur Direktori `app/` (V1)

```
app/
├── Actions/Fortify/        CreateNewUser, ResetUserPassword (hook registrasi Fortify)
├── Concerns/               PasswordValidationRules, ProfileValidationRules (trait validasi)
├── Console/Commands/        CompleteBookings (auto-complete booking lewat scheduler)
├── Constants/               OrderStatus, OrderDetailStatus, UserRole  ← single source of truth
├── Filament/
│   ├── Resources/           Panel ADMIN (Kategori, Kota, Mitra, Order, Promo, Review, Salon, Service, User)
│   ├── Widgets/             LatestOrders, StatsOverview (admin)
│   └── Owner/
│       ├── Resources/       Panel OWNER (Order, Promo, SalonImage, Salon, Service, Staff)
│       └── Widgets/         OwnerStatsOverview, UpcomingOrdersTable
├── Http/
│   ├── Controllers/         11 controller (lihat §5)
│   └── Middleware/          CheckRole, EnsureUserIsActive
├── Livewire/Actions/        Logout
├── Models/                  15 model (lihat §4)
├── Observers/               ReviewObserver (recalc rating salon saat review berubah)
├── Providers/
│   ├── AppServiceProvider, FortifyServiceProvider
│   └── Filament/            AdminPanelProvider, OwnerPanelProvider
└── Services/                ApproveSalonApplicationService, BookingSlotService
```

> **Pola yang harus ditiru V2:** logika bisnis non-trivial → **Service class** (`app/Services/`). Konstanta status/role → **Constants class**. Filament per-panel → subfolder namespace sendiri (`App\Filament\Store\...` untuk V2).

---

## 3. Auth, Role & Panel (fondasi yang dipakai ulang V2)

### 3.1 Role
- Enum kolom `users.role` (DB) **V1 = `['customer','salon_owner','admin']`** (lihat migration `2026_04_12_000003_create_users_table.php`).
- Single source of truth di kode: [`app/Constants/UserRole.php`](../../app/Constants/UserRole.php)
  ```php
  class UserRole {
      public const CUSTOMER='customer', SALON_OWNER='salon_owner', ADMIN='admin';
      public static function all(): array { ... }
  }
  ```
- **V2 menambah `admin_store`** → harus diubah di **dua tempat**: (a) ALTER enum DB, (b) konstanta `UserRole`.

### 3.2 Mass-assignment guard
[`app/Models/User.php`](../../app/Models/User.php):
```php
protected $fillable = ['first_name','last_name','email','password','phone_number','profile_url'];
protected $guarded  = ['role','is_active','id_user'];   // SEC-03: cegah privilege escalation
```
→ Set `role` HARUS via property assignment, bukan mass-assign:
```php
$u = User::firstOrNew(['email'=>'admin.store@viygo.id']);
$u->fill([...]); $u->role = UserRole::ADMIN_STORE; $u->save();   // ✅
```

### 3.3 Middleware
- Alias didaftarkan di [`bootstrap/app.php`](../../bootstrap/app.php):
  - `role` → `CheckRole` — `Route::middleware('role:customer')` / `'role:admin,admin_store'`.
  - `active` → `EnsureUserIsActive` — juga di-append global ke grup `web` (logout user yang di-nonaktifkan mid-session).
- `CheckRole` ([`app/Http/Middleware/CheckRole.php`](../../app/Http/Middleware/CheckRole.php)) cek `is_active` lalu `in_array($user->role, $roles, true)`, else `abort(403)`.

### 3.4 Filament panels
Dua panel terdaftar di [`bootstrap/providers.php`](../../bootstrap/providers.php):

| Panel | id | path | Provider | Resource namespace |
|-------|----|----- |----------|--------------------|
| Admin (super) | `admin` | `/admin` (`->default()`) | `AdminPanelProvider` | `App\Filament\Resources` |
| Salon Owner | `owner` | `/owner` | `OwnerPanelProvider` | `App\Filament\Owner\Resources` |

Akses dikontrol di `User::canAccessPanel()`:
```php
public function canAccessPanel(Panel $panel): bool {
    if ($panel->getId()==='admin') return $this->role===UserRole::ADMIN && $this->is_active;
    if ($panel->getId()==='owner') return $this->role===UserRole::SALON_OWNER && $this->is_active;
    return false;
}
```
> **Untuk V2:** tambah panel ke-3 `store` (path `/admin/store`), daftarkan provider di `bootstrap/providers.php`, dan tambahkan cabang `store` di `canAccessPanel()`. Detail di doc 07.

Pola panel provider (lihat `OwnerPanelProvider`): `->id()->path()->login()->discoverResources(...)->navigationGroups([...])->middleware([...])->authMiddleware([Authenticate, EnsureUserIsActive])`. Panel `store` ikut pola ini. Owner panel **men-scope query** ke salon milik user (`User::ownedSalonIds()`); Admin Store **tidak** perlu scoping (admin store lihat semua produk).

---

## 4. Model & Konvensi Database V1

### 4.1 Daftar model (15) — `app/Models/`
`User, Kota, Kategori, SubKategori, Salon, SalonImage, Service, Staff, StaffSchedule, Order, OrderDetail, Pembayaran, Promo, Review, MitraApplication`

> Catatan: `docs/eloquent-models.md` mendokumentasikan 13 model awal (belum termasuk `SubKategori` & `MitraApplication` yang ditambah belakangan). Tetap referensi bagus untuk pola model.

### 4.2 Konvensi Primary Key — CUSTOM (penting!)
Semua tabel domain dibuat dengan `$table->id('id_<nama>')` → kolom PK = `id_user`, `id_salon`, `id_order`, dst. (BIGINT UNSIGNED auto-increment). Maka setiap model **wajib**:
```php
protected $table      = 'order';
protected $primaryKey = 'id_order';
```

### 4.3 Konvensi Foreign Key — WAJIB sebut kolom
Contoh nyata dari `create_order_table.php`:
```php
$table->foreignId('id_user')->constrained('users','id_user')->cascadeOnDelete();
$table->foreignId('id_salon')->constrained('salon','id_salon')->cascadeOnDelete();
$table->foreignId('id_promo')->nullable()->constrained('promo','id_promo')->nullOnDelete();
```
> Argumen kedua `constrained('tabel','kolom_pk')` **wajib** karena PK bukan `id`. Tabel V1 yang sering di-FK dari V2: `users`(id_user), `promo`(id_promo), `salon`(id_salon).

### 4.4 Pola model lain
- **SoftDeletes** di: `User, Salon, Promo, Service, Staff` (ada kolom `deleted_at`).
- **Casts** via method `casts(): array` (Laravel 11+ style).
- **Local scopes**: `Promo::scopeActive`, `Order::scopeByStatus`, `Review::scopeVisible`.
- **Accessor**: `User::fullName` (gabung first+last, anti-duplikat) & alias `name` (untuk Filament v5 `getUserName()`).
- **Money**: kolom uang pakai `decimal(12,2)` (bukan 10,2). Cast `'decimal:2'`.

### 4.5 Tabel V1 (untuk referensi FK V2)
| Tabel | PK | Kolom kunci | Dipakai V2? |
|-------|----|-----------  |-------------|
| `users` | `id_user` | first_name,last_name,email,role,is_active,phone_number | ✅ FK utama hampir semua tabel V2 |
| `salon` | `id_salon` | nama_salon,slug,id_kota,id_user,status | ✅ Empty Return drop-off |
| `promo` | `id_promo` | kode_promo,diskon,diskon_max,min_transaksi,tipe_promo,status,stock | ✅ checkout produk reuse promo |
| `order` | `id_order` | kode_order(`VYG-…`),status(pending/confirmed/success/canceled),total_pembayaran(GBP) | ➖ acuan pola untuk `product_orders` |
| `pembayaran` | `id_pembayaran` | id_order,snap_token,midtrans_order_id,id_transaksi,status_pembayaran,raw_response | ➖ acuan pola untuk `product_pembayaran` |
| `review` | `id_review` | id_order,id_salon,rating,is_visible | ➖ acuan pola untuk `product_reviews` |
| `kota` | `id_kota` | nama_kota,provinsi (sumber Treatwell, lokasi salon) | ❌ JANGAN dipakai untuk alamat kirim — pakai `user_addresses` (api.co.id) |
| `kategori`,`sub_kategori` | id_kategori,id_sub_kategori | kategori SALON | ❌ produk pakai `product_categories` |

---

## 5. Controller & Routing V1

11 controller di `app/Http/Controllers/`: `Home, Search, Kategori, Salon, Booking, Payment, Review, Akun, Mitra, Static, Controller(base)`.

Pola routing ([`routes/web.php`](../../routes/web.php)):
- **Public**: home, `/cari`, `/kategori/{slug}`, `/salon/{slug}`, `/mitra`, static pages.
- **`['auth','verified']`**: booking flow + payment.
- **`['auth','verified','role:customer']` + prefix `akun`**: dashboard customer, bookings, favorit, pengaturan, review.
- **`/dashboard`** me-redirect by role (`admin`→/admin, `salon_owner`→/owner, else→akun.bookings).
- **`/midtrans/webhook`** publik (throttle 120/1), CSRF dikecualikan di `bootstrap/app.php`.
- Throttling per-route: `throttle:5,1` (mitra apply), `throttle:10,1` (contact), dst.

> **V2** menambah route group `/shop`, `/lookbook`, `/komunitas`, `/empty-return`, `/akun/poin`, `/eksklusif`, `/akun/bookings/{kode}`. Detail di doc 05.

`kode_order` V1 dibuat di `BookingController::store`:
```php
'kode_order' => 'VYG-' . strtoupper(Str::random(8)),   // contoh: VYG-A1B2C3D4
```
> **V2**: produk pakai prefix `VYG-S-`. Disarankan buat `OrderCodeGenerator` service (doc 05) agar konsisten & unik.

---

## 6. Integrasi Midtrans V1 (acuan untuk pembayaran produk)

`PaymentController` ([`app/Http/Controllers/PaymentController.php`](../../app/Http/Controllers/PaymentController.php)) — pelajari ini karena `ProductPaymentController` V2 **meniru polanya**:

**Urutan:** `BookingController::store` buat order(pending) → `show()` render Snap host page → `createSnapToken()` minta token + simpan `pembayaran` → frontend `snap.pay()` → `finish()` verifikasi server-side via `Transaction::status()` → `webhook()` sebagai safety net.

**Teknik penting yang WAJIB direplikasi di pembayaran produk:**
1. **Config Midtrans** di constructor dari `config('services.midtrans.*')` ([`config/services.php`](../../config/services.php)).
2. **Verifikasi server-side**, bukan percaya body callback frontend.
3. **Webhook signature SHA512**: `hash('sha512', order_id . status_code . gross_amount . server_key)` dibandingkan `hash_equals()` → 403 jika mismatch.
4. **Idempotency guard** (SEC-04): skip jika `transaction_id` sama & status sudah `completed`.
5. **Pessimistic lock**: `Order::...->lockForUpdate()` di dalam `DB::transaction()` → serialisasi webhook bersamaan.
6. **Status mapping**: `capture`(+fraud≠challenge)/`settlement`→completed+order confirmed; `pending`→pending; `deny/expire/cancel/failure`→failed.
7. **Retry order_id**: jika Midtrans tolak `order_id` (sudah dipakai), retry dengan suffix `-R{time}`. Webhook strip suffix via regex `/-R\d+$/`.
8. **Limit IDR**: gross_amount > 999.999.999 → `DomainException` (limit transaksi tunggal Midtrans).

> [!CAUTION]
> **Perbedaan kritis untuk produk:** Harga salon V1 dalam **GBP** → `convertGbpToIdr()` (rate `config('services.midtrans.exchange_rate', 20000)`). **Produk V2 sudah dalam IDR** → `gross_amount` = nilai rupiah langsung, JANGAN dikonversi. Inilah salah satu alasan `ProductPaymentController` dibuat terpisah, bukan extend `PaymentController`.

Kolom Midtrans di `pembayaran` (ditambah lewat migration belakangan): `snap_token`, `midtrans_order_id`, `id_transaksi`, `raw_response` (json). `product_pembayaran` punya kolom serupa (lihat doc 03).

---

## 7. Hal lain yang relevan untuk V2

- **Observer**: `ReviewObserver` me-recalculate `salon.rating` & `total_review`. V2 butuh pola sama untuk `product_reviews` → update `products.rating`/`total_review` (pakai Observer atau service).
- **Scheduler**: `CompleteBookings` command auto-update status. V2 bisa pakai pola serupa untuk auto-cancel pesanan `pending` yang tak dibayar, atau auto-complete pengiriman.
- **Seeder**: `DatabaseSeeder` memanggil seeder modular (KategoriSeeder, SalonSeeder, dst.). V2 tambah `FreshProductSeeder`, `AdminStoreSeeder`, `ForumCategorySeeder` (daftarkan di `DatabaseSeeder` bila perlu).
- **Trust proxies** `*` (ngrok/HTTPS) sudah diset — relevan untuk testing Midtrans webhook lewat tunnel.

---

## 8. Cek-cepat verifikasi V1 (untuk agent lanjutan)

```bash
php artisan route:list | grep -E 'booking|salon|akun'   # rute V1 ada
php artisan migrate:status                              # 35 migration V1 'Ran'
php artisan tinker --execute="echo App\Models\Salon::count();"
grep -n "exchange_rate" config/services.php             # konfirmasi harga GBP
```

---

*Berikutnya: [`02-arsitektur-overview-v2.md`](02-arsitektur-overview-v2.md) — bagaimana 5 modul V2 disusun di atas fondasi ini.*
