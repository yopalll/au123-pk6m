# ⚠️ Catatan Lingkungan Nyata (Hasil Phase 0)

> **WAJIB dibaca semua agent sebelum mengerjakan phase mana pun.**
> Plan ditulis dengan beberapa **asumsi** yang ternyata BERBEDA dari kode V1 sebenarnya.
> Dokumen ini adalah **sumber kebenaran** — kalau plan bentrok dengan dokumen ini, ikuti dokumen ini.

---

## 1. Versi Stack (PENTING)

| Komponen | Versi terpasang | Implikasi |
|----------|-----------------|-----------|
| Laravel | 12 | ✅ sesuai plan |
| PHP | 8.3 | ✅ |
| **Filament** | **v5.6** | 🔴 **BUKAN v3.** Sintaks Resource/Form/Action beda — lihat §5 |
| Livewire | 4.1 | — |
| Flux | 2.13 | — |
| Pest | 4.5 | Test pakai **Pest** (`it()/test()`), bukan PHPUnit class-style |
| Midtrans | terpasang | reuse untuk payment produk |

---

## 2. Model `User` (app/Models/User.php)

```
table: users          PK: id_user
fillable: first_name, last_name, email, password, phone_number, profile_url
GUARDED : role, is_active, id_user      ← TIDAK BISA mass-assign!
```

- 🔴 **`role` di-`$guarded`** → `User::create([... 'role'=>'admin_store'])` / `updateOrCreate` dengan role **TIDAK akan menyetel role**. Harus assignment eksplisit:
  ```php
  $u = User::firstOrCreate(['email'=>'...'], [/* tanpa role */]);
  $u->role = 'admin_store';
  $u->is_active = true;
  $u->save();
  ```
- Kolom telepon = **`phone_number`** (bukan `phone`).
- Sudah `implements FilamentUser` dan **sudah punya `canAccessPanel()`** (kasus `admin` & `owner`, di-gate `is_active`). → **TAMBAH** case `store`, jangan timpa method.
- Sudah pakai SoftDeletes, Fortify 2FA. Accessor `full_name` & `name`.
- Relasi V1 yang sudah ada: `salons, orders, reviews, pembayarans, promos, favourites` (jangan dihapus saat menambah relasi V2).

## 3. Konstanta Role (app/Constants/UserRole.php)

```php
const CUSTOMER='customer'; const SALON_OWNER='salon_owner'; const ADMIN='admin';
// TIDAK ada admin_store → harus DITAMBAH:
const ADMIN_STORE='admin_store';   // + masukkan ke all()
```
Ada juga `App\Constants\OrderStatus` (PENDING/CONFIRMED/SUCCESS/CANCELED) yang dipakai scope Order.

## 4. Model Booking V1 (untuk Phase 2A)

**Order** (`table: order`, PK `id_order`):
```
fillable: id_user, id_salon, id_promo, kode_order, date_order,
          total_pembayaran, total_diskon, status
relasi  : user(), salon(), promo(), details()  ← hasMany OrderDetail (BUKAN orderDetails)
          review() hasOne, pembayaran() hasOne
```
- 🔴 **Tidak ada** kolom `subtotal`/`total`/`diskon`. Pakai: total = **`total_pembayaran`**, diskon = **`total_diskon`**, subtotal = hitung dari `details.sum('subtotal')`.

**OrderDetail** (`table: order_detail`, PK `id_order_detail`):
```
fillable: id_order, id_service, id_staff, start_time, end_time,
          harga_at_order, subtotal, catatan, status
relasi  : order(), service()  ← BUKAN treatment, staff()
```

**Service** (`table: service`, PK `id_service`): kolom `nama`, `durasi` (int menit), `harga`, `status`.

> ⚠️ **Salon** dan **Pembayaran**: nama kolom display Salon kemungkinan **`nama_salon`** (bukan `nama`) — ServiceResource memakai `relationship('salon','nama_salon')`. **Baca `app/Models/Salon.php` & `app/Models/Pembayaran.php` dulu** sebelum menulis view/PDF untuk memastikan: nama salon, alamat, no telp, latitude/longitude, slug; dan field Pembayaran (metode, status, paid_at, midtrans_transaction_id).

## 5. Filament v5 — Sintaks Wajib (≠ v3)

Plan menulis gaya v3. Saat membuat Resource/Panel/Widget, **TIRU file V1 yang sudah jalan** di repo ini:
- Resource CRUD → contoh: `app/Filament/Resources/ServiceResource.php`
- Resource read+update saja → contoh: `app/Filament/Resources/OrderResource.php`
- Widget stat → contoh: `app/Filament/Widgets/StatsOverview.php`
- Panel provider → contoh: `app/Providers/Filament/OwnerPanelProvider.php`

Perbedaan kunci v5:
```php
use Filament\Schemas\Schema;                 // BUKAN Filament\Forms\Form
public static function form(Schema $form): Schema {
    return $form->schema([ Forms\Components\TextInput::make('nama')->required() ]);
}
use Filament\Tables\Table;
public static function table(Table $table): Table { ... }

// Actions ada di namespace Filament\Actions\* (BUKAN Filament\Tables\Actions\*)
->actions([ \Filament\Actions\EditAction::make(), \Filament\Actions\DeleteAction::make() ])
->bulkActions([ \Filament\Actions\BulkActionGroup::make([ \Filament\Actions\DeleteBulkAction::make() ]) ])
// Custom action (mis. Approve empty return): \Filament\Actions\Action::make('approve')->form([...])->action(fn...)

// Tipe properti navigasi v5:
protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
protected static string | \UnitEnum  | null $navigationGroup = 'Katalog';
```
Middleware panel & authMiddleware: **salin persis** dari OwnerPanelProvider (`EncryptCookies, AddQueuedCookiesToResponse, StartSession, AuthenticateSession, ShareErrorsFromSession, ValidateCsrfToken, SubstituteBindings, DisableBladeIconComponents, DispatchServingFilamentEvent` + authMiddleware `Authenticate, EnsureUserIsActive`).

## 6. Routes V1 (web.php) yang relevan

- Group akun: `prefix('akun')->name('akun.')` di dalam `middleware(['auth','verified'])` + `role:customer`. Sudah ada `akun.index, akun.bookings, akun.favorit, akun.pengaturan`, dan review di `/bookings/{kode}/review`.
  → Route booking-detail V2 (`akun.booking.detail`, `akun.booking.invoice`) **masukkan ke group akun yang SUDAH ADA ini**.
- Ada middleware alias **`role:`** → pakai `role:admin_store` bila perlu gating non-Filament.
- Payment V1: `PaymentController@show/createSnapToken/finish` + `midtrans.webhook` → pola untuk ProductPayment.
- Filament panel V1 yang ada: `admin` (AdminPanelProvider) & `owner` (OwnerPanelProvider). Store jadi panel ke-3.

---

## 7. Produk multi-kategori (pivot `category_product`)

Selain kategori utama (`products.id_product_category`), ada pivot **many-to-many**
`category_product` → 1 produk bisa masuk >1 tipe.
- Relasi: `Product::categories()` (semua) & `ProductCategory::allProducts()`.
- Seeder mengisi pivot dari kategori utama + field opsional `kategori_lain[]` di JSON.
- Migration: `2026_06_01_000030_create_category_product_table.php`.
- Di **ProductResource (Filament, phase-2b)**: ekspose ini sebagai field `Select` multiple
  (`->relationship('categories', 'nama')->multiple()`) untuk pilih tipe tambahan.
- Detail lengkap: [penjelasanscraper.md](penjelasanscraper.md#1-produk-bisa-lebih-dari-1-tipe-pivot).

## Ringkasan aksi koreksi yang sudah diterapkan ke plan
- **phase-1c**: AdminStoreSeeder pakai assignment eksplisit; tambah `UserRole::ADMIN_STORE`; `canAccessPanel()` ditambah case `store`; StorePanelProvider mengikuti gaya v5 OwnerPanelProvider.
- **phase-2a**: query/ view memakai `details/service/total_pembayaran/total_diskon/harga_at_order` + catatan verifikasi Salon/Pembayaran.
- **phase-2b/3a/3b/3c/4 (Filament)**: semua Resource/Widget mengikuti §5 (tiru resource V1).
