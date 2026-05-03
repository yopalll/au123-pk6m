# VIYGO Admin Panel — Implementation Plan (Filament v5)

> **Dibuat:** 1 Mei 2026
> **Status:** PLAN — belum dieksekusi
> **Target:** Admin panel lengkap di `/admin` menggunakan Filament v5
> **Prasyarat:** Laravel 13, Livewire v4, PHP 8.3 — semua sudah terpenuhi

---

## Daftar Isi

1. [Overview](#1-overview)
2. [Instalasi & Setup](#2-instalasi--setup)
3. [Auth Guard — Admin Only](#3-auth-guard--admin-only)
4. [Dashboard Widgets](#4-dashboard-widgets)
5. [Resources (CRUD)](#5-resources-crud)
- 5.1 SalonResource
- 5.2 UserResource
- 5.3 KategoriResource
- 5.4 KotaResource
- 5.5 ServiceResource
- 5.6 OrderResource
- 5.7 ReviewResource
- 5.8 PromoResource
6. [Relation Managers](#6-relation-managers)
7. [Custom Actions & Bulk Actions](#7-custom-actions--bulk-actions)
8. [File Map](#8-file-map)
9. [Pertimbangan Teknis](#9-pertimbangan-teknis)
10. [Urutan Eksekusi](#10-urutan-eksekusi)

---

## 1. Overview

Admin panel untuk platform VIYGO yang memungkinkan admin mengelola:
- **8.750 salon** — approve/reject, edit, soft-delete
- **190.594 services** — via relation manager di salon
- **7.568 staff** — via relation manager di salon
- **50.492 salon images** — via relation manager di salon
- **7.183 kategori** — toggle aktif/nonaktif
- **1.709 kota** — read + edit
- **~5.769 users** — manajemen role, activate/deactivate
- **Orders** — lihat, update status
- **Reviews** — moderasi (toggle `is_visible`)
- **Promos** — full CRUD

**Tech stack:** Filament v5 (compatible Livewire v4 + Laravel 13)

---

## 2. Instalasi & Setup

### Step 2.1 — Install package

```bash
composer require filament/filament:"^5.0" -W
```

### Step 2.2 — Scaffold panel

```bash
php artisan filament:install --panels
```

Ini akan membuat:
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/` directory structure

### Step 2.3 — Konfigurasi AdminPanelProvider

```php
// app/Providers/Filament/AdminPanelProvider.php

use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
public function panel(Panel $panel): Panel
{
return $panel
->default()
->id('admin')
->path('admin') // URL: /admin
->login() // Filament login page
->brandName('VIYGO Admin')
->favicon(asset('favicon.ico'))
->colors([
'primary' => '#1B2D6B', // VIYGO navy
'info' => '#4BA3CC', // VIYGO blue
])
->discoverResources(
in: app_path('Filament/Resources'),
for: 'App\\Filament\\Resources'
)
->discoverWidgets(
in: app_path('Filament/Widgets'),
for: 'App\\Filament\\Widgets'
)
->middleware([
// default middleware
])
->authMiddleware([
// Filament default auth
]);
}
}
```

### Step 2.4 — Register provider

Tambahkan di `bootstrap/providers.php`:

```php
return [
// ...existing providers
App\Providers\Filament\AdminPanelProvider::class,
];
```

### Step 2.5 — Create admin user

```bash
php artisan make:filament-user
# ATAU menggunakan tinker:
# User::where('email', 'admin@viygo.com')->update(['role' => 'admin'])
```

---

## 3. Auth Guard — Admin Only

Panel hanya dapat diakses user dengan `role = 'admin'`.

### Implementasi di User model

Tambahkan interface `FilamentUser` dan method `canAccessPanel()`:

```php
// app/Models/User.php

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
// ...existing code...

public function canAccessPanel(Panel $panel): bool
{
// Hanya admin yang dapat akses panel
if ($panel->getId() === 'admin') {
return $this->role === 'admin' && $this->is_active;
}

return false;
}
}
```

### Catatan penting

- User model menggunakan **custom PK** `id_user` — Filament v5 akan detect ini dari `$primaryKey`
- `$this->role` adalah string column: `'customer' | 'salon_owner' | 'admin'`
- `$this->is_active` adalah boolean — user non-aktif tidak dapat login ke panel

---

## 4. Dashboard Widgets

### 4.1 StatsOverviewWidget

**File:** `app/Filament/Widgets/StatsOverview.php`

```php
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
protected function getStats(): array
{
return [
Stat::make('Total Salons', Salon::count())
->description(Salon::active()->count() . ' active')
->color('primary'),

Stat::make('Total Users', User::count())
->description(User::where('role', 'customer')->count() . ' customers'),

Stat::make('Total Services', Service::count())
->description(number_format(Service::active()->count()) . ' active'),

Stat::make('Total Orders', Order::count())
->description(Order::pending()->count() . ' pending')
->color('warning'),

Stat::make('Total Reviews', Review::count())
->description(Review::visible()->count() . ' visible'),

Stat::make('Revenue', '£' . number_format(Order::success()->sum('total_pembayaran'), 2))
->color('success'),
];
}
}
```

### 4.2 LatestOrdersWidget

**File:** `app/Filament/Widgets/LatestOrders.php`

```php
use Filament\Tables;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
protected int|string|array $columnSpan = 'full';

public function table(Tables\Table $table): Tables\Table
{
return $table
->query(Order::with(['user', 'salon'])->latest()->limit(10))
->columns([
Tables\Columns\TextColumn::make('kode_order')->label('Order Code'),
Tables\Columns\TextColumn::make('user.full_name')->label('Customer'),
Tables\Columns\TextColumn::make('salon.nama_salon')->label('Salon')->limit(30),
Tables\Columns\TextColumn::make('total_pembayaran')
->label('Total')
->money('GBP'),
Tables\Columns\BadgeColumn::make('status')
->colors([
'warning' => 'pending',
'success' => 'success',
'danger' => 'canceled',
]),
Tables\Columns\TextColumn::make('date_order')->date('d M Y'),
]);
}
}
```

### 4.3 SalonsByStatusChart (opsional)

**File:** `app/Filament/Widgets/SalonsByStatusChart.php`

Pie chart: active vs inactive vs pending salons.

---

## 5. Resources (CRUD)

> **Penting:** Semua model VIYGO menggunakan **custom primary key** (bukan `id`).
> Filament v5 akan mendeteksi `$primaryKey` dari model secara otomatis, tapi pastikan setiap resource
> tidak hardcode `id` di manapun.

### 5.1 SalonResource

**File:** `app/Filament/Resources/SalonResource.php`

**Fitur:**
- List: paginate 25, searchable by `nama_salon`, `slug`, `alamat`
- View: read-only detail page
- Edit: update status, deskripsi, contact info
- Soft-delete: trash support via `SoftDeletes` trait
- 3 Relation Managers: Services, Staff, Images

**Tabel columns:**

| Column | Type | Sortable | Searchable |
|--------|------|----------|------------|
| `id_salon` | TextColumn | | |
| `nama_salon` | TextColumn | | |
| `slug` | TextColumn | | |
| `kota.nama_kota` | TextColumn | | |
| `rating` | TextColumn | | |
| `total_review` | TextColumn | | |
| `status` | BadgeColumn | | |
| `created_at` | TextColumn (date) | | |

**Filters:**
- `status` — SelectFilter: `active`, `inactive`, `pending`
- `kota.nama_kota` — SelectFilter (top 50 kota by salon count)
- `rating` — Filter ≥ 4.0, ≥ 3.0
- `TrashedFilter` — show soft-deleted

**Form fields:**

```
Section "Basic Info":
- nama_salon (TextInput, required, maxLength 255)
- slug (TextInput, disabled — auto-generated)
- deskripsi (Textarea)
- status (Select: active/inactive/pending)

Section "Location":
- id_kota (Select, searchable, relationship 'kota', titleColumn 'nama_kota')
- alamat (TextInput)
- latitude (TextInput, numeric)
- longitude (TextInput, numeric)

Section "Contact & Hours":
- phone_number (TextInput)
- opening_time (TimePicker)
- closing_time (TimePicker)

Section "Metrics" (disabled — read-only):
- rating (TextInput, disabled)
- total_review (TextInput, disabled)
```

**Relation Managers:**
- `ServicesRelationManager` → lihat §6.1
- `StaffRelationManager` → lihat §6.2
- `ImagesRelationManager` → lihat §6.3

---

### 5.2 UserResource

**File:** `app/Filament/Resources/UserResource.php`

**Tabel columns:**

| Column | Type |
|--------|------|
| `id_user` | TextColumn |
| `first_name` + `last_name` (virtual: full_name) | TextColumn, searchable |
| `email` | TextColumn, searchable |
| `role` | BadgeColumn (customer=gray, salon_owner=info, admin=danger) |
| `is_active` | IconColumn (boolean) |
| `created_at` | TextColumn (date) |

**Filters:**
- `role` — SelectFilter: `customer`, `salon_owner`, `admin`
- `is_active` — TernaryFilter
- `TrashedFilter`

**Form fields:**
```
- first_name (TextInput, required)
- last_name (TextInput, required)
- email (TextInput, email, required, unique rule ignoring current)
- role (Select: customer/salon_owner/admin)
- is_active (Toggle)
- password (TextInput, password, hashed — only on create, nullable on edit)
```

**Catatan:** JANGAN expose password hash. Pada edit, password field kosong = tidak diubah.

---

### 5.3 KategoriResource

**File:** `app/Filament/Resources/KategoriResource.php`

**Tabel columns:**

| Column | Type |
|--------|------|
| `id_kategori` | TextColumn |
| `name` | TextColumn, searchable |
| `slug` | TextColumn, searchable |
| `is_active` | IconColumn (boolean toggle) |
| `services_count` | TextColumn (withCount) |

**Form fields:**
```
- name (TextInput, required)
- slug (TextInput, required — auto-generate dari name)
- deskripsi (Textarea)
- icon_url (TextInput, url, nullable)
- is_active (Toggle, default true)
```

**Bulk action:** Toggle active/inactive untuk batch kategori.

---

### 5.4 KotaResource

**File:** `app/Filament/Resources/KotaResource.php`

**Tabel columns:**

| Column | Type |
|--------|------|
| `id_kota` | TextColumn |
| `nama_kota` | TextColumn, searchable |
| `provinsi` | TextColumn |
| `salons_count` | TextColumn (withCount) |

**Form fields:**
```
- nama_kota (TextInput, required)
- provinsi (TextInput)
```

**Catatan:** 1.709 kota — perlu pagination. Tidak perlu Create (data dari scraper).

---

### 5.5 ServiceResource

**File:** `app/Filament/Resources/ServiceResource.php`

> **Perhatian:** 190.594 rows! Resource ini HARUS:
> - Tidak eager-load semua data
> - Pagination 25 per page
> - Global search disabled (terlalu berbagai data)
> - Sebaiknya diakses via Relation Manager di SalonResource

**Tabel columns:**

| Column | Type |
|--------|------|
| `id_service` | TextColumn |
| `nama` | TextColumn, searchable |
| `salon.nama_salon` | TextColumn, searchable |
| `kategori.name` | TextColumn |
| `harga` | TextColumn, money GBP |
| `durasi` | TextColumn (suffix " min") |
| `status` | BadgeColumn |

**Filters:**
- `status` — SelectFilter: `active`, `inactive`
- `kategori` — SelectFilter (relationship)
- `TrashedFilter`

**Form fields:**
```
- id_salon (Select, searchable, relationship 'salon', titleColumn 'nama_salon')
- id_kategori (Select, searchable, relationship 'kategori', titleColumn 'name')
- nama (TextInput, required)
- deskripsi (Textarea)
- harga (TextInput, numeric, prefix '£', required)
- durasi (TextInput, numeric, suffix 'minutes', required)
- status (Select: active/inactive)
```

---

### 5.6 OrderResource

**File:** `app/Filament/Resources/OrderResource.php`

> **Mode:** View & Edit only (no Create dari admin). Admin dapat update status.

**Tabel columns:**

| Column | Type |
|--------|------|
| `id_order` | TextColumn |
| `kode_order` | TextColumn, searchable, copyable |
| `user.full_name` | TextColumn (accessor) |
| `salon.nama_salon` | TextColumn |
| `date_order` | TextColumn (date 'd M Y') |
| `total_pembayaran` | TextColumn, money GBP |
| `total_diskon` | TextColumn, money GBP |
| `status` | BadgeColumn |

**Filters:**
- `status` — SelectFilter: `pending`, `success`, `canceled`
- `date_order` — DateFilter (range)

**Form fields (edit only):**
```
- status (Select: pending/confirmed/success/canceled)
- total_diskon (TextInput, numeric, prefix '£')
```

**Relation Manager:** `OrderDetailsRelationManager` → lihat §6.4

---

### 5.7 ReviewResource

**File:** `app/Filament/Resources/ReviewResource.php`

> **Mode:** List + Edit (moderasi). Admin dapat toggle `is_visible`.

**Tabel columns:**

| Column | Type |
|--------|------|
| `id_review` | TextColumn |
| `user.full_name` | TextColumn |
| `salon.nama_salon` | TextColumn, searchable |
| `rating` | TextColumn ( icon) |
| `komentar` | TextColumn, limit 50 |
| `is_visible` | ToggleColumn |
| `created_at` | TextColumn (date) |

**Filters:**
- `is_visible` — TernaryFilter
- `rating` — SelectFilter: 1–5

**Bulk action:** "Hide Selected" / "Show Selected" — toggle `is_visible`.

---

### 5.8 PromoResource

**File:** `app/Filament/Resources/PromoResource.php`

> **Mode:** Full CRUD.

**Tabel columns:**

| Column | Type |
|--------|------|
| `id_promo` | TextColumn |
| `nama_promo` | TextColumn, searchable |
| `kode_promo` | TextColumn, searchable, copyable |
| `tipe_promo` | BadgeColumn |
| `diskon` | TextColumn (suffix '%') |
| `stock` | TextColumn |
| `used_counter` | TextColumn |
| `status` | BadgeColumn |
| `time_expired` | TextColumn (date) |

**Filters:**
- `status` — SelectFilter: `active`, `inactive`
- `tipe_promo` — SelectFilter: `percentage`, `fixed`
- Active scope (not expired)
- `TrashedFilter`

**Form fields:**
```
Section "Promo Info":
- nama_promo (TextInput, required)
- deskripsi_promo (Textarea)
- kode_promo (TextInput, required, unique)
- tipe_promo (Select: percentage/fixed)

Section "Discount":
- diskon (TextInput, numeric — persentase atau nominal)
- diskon_max (TextInput, numeric, prefix '£')
- min_transaksi (TextInput, numeric, prefix '£')

Section "Availability":
- time_start (DateTimePicker)
- time_expired (DateTimePicker)
- stock (TextInput, numeric)
- status (Select: active/inactive)

Section "Usage" (disabled — read-only):
- used_counter (TextInput, disabled)
```

---

## 6. Relation Managers

### 6.1 ServicesRelationManager (di SalonResource)

**File:** `app/Filament/Resources/SalonResource/RelationManagers/ServicesRelationManager.php`

```
Relationship: hasMany('services') via id_salon
Columns: nama, kategori.name, harga (£), durasi (min), status
Actions: Create, Edit, Delete, ForceDelete, Restore
```

### 6.2 StaffRelationManager (di SalonResource)

**File:** `app/Filament/Resources/SalonResource/RelationManagers/StaffRelationManager.php`

```
Relationship: hasMany('staff') via id_salon
Columns: name, profile_url, status
Actions: Create, Edit, Delete
```

### 6.3 ImagesRelationManager (di SalonResource)

**File:** `app/Filament/Resources/SalonResource/RelationManagers/ImagesRelationManager.php`

```
Relationship: hasMany('images') via id_salon
Columns: image_url (ImageColumn — thumbnail), is_primary (toggle), urutan
Actions: Create, Edit, Delete
```

### 6.4 OrderDetailsRelationManager (di OrderResource)

**File:** `app/Filament/Resources/OrderResource/RelationManagers/OrderDetailsRelationManager.php`

```
Relationship: hasMany('details') via id_order
Columns: service.nama, staff.name, start_time, end_time, harga_at_order (£), subtotal (£), catatan, status
Actions: View only (no edit from admin)
```

---

## 7. Custom Actions & Bulk Actions

### Per-record Actions

| Resource | Action | Efek |
|----------|--------|------|
| SalonResource | **Approve** | Set `status = 'active'` |
| SalonResource | **Reject** | Set `status = 'inactive'` |
| UserResource | **Deactivate** | Set `is_active = false` |
| UserResource | **Promote to Admin** | Set `role = 'admin'` (confirmation required) |
| OrderResource | **Mark as Success** | Set `status = 'success'` |
| OrderResource | **Cancel Order** | Set `status = 'canceled'` |
| ReviewResource | **Toggle Visibility** | Flip `is_visible` |

### Bulk Actions

| Resource | Bulk Action |
|----------|-------------|
| SalonResource | Bulk approve, bulk reject |
| KategoriResource | Bulk activate, bulk deactivate |
| ReviewResource | Bulk hide, bulk show |

---

## 8. File Map

Setelah implementasi selesai, file structure akan menjadi:

```
app/Filament/
├── Resources/
│ ├── SalonResource.php
│ ├── SalonResource/
│ │ ├── Pages/
│ │ │ ├── ListSalons.php
│ │ │ ├── CreateSalon.php
│ │ │ ├── EditSalon.php
│ │ │ └── ViewSalon.php
│ │ └── RelationManagers/
│ │ ├── ServicesRelationManager.php
│ │ ├── StaffRelationManager.php
│ │ └── ImagesRelationManager.php
│ │
│ ├── UserResource.php
│ ├── UserResource/Pages/...
│ │
│ ├── KategoriResource.php
│ ├── KategoriResource/Pages/...
│ │
│ ├── KotaResource.php
│ ├── KotaResource/Pages/...
│ │
│ ├── ServiceResource.php
│ ├── ServiceResource/Pages/...
│ │
│ ├── OrderResource.php
│ ├── OrderResource/
│ │ ├── Pages/...
│ │ └── RelationManagers/
│ │ └── OrderDetailsRelationManager.php
│ │
│ ├── ReviewResource.php
│ ├── ReviewResource/Pages/...
│ │
│ └── PromoResource.php
│ └── PromoResource/Pages/...
│
├── Widgets/
│ ├── StatsOverview.php
│ ├── LatestOrders.php
│ └── SalonsByStatusChart.php
│
└── (auto-generated pages per resource)

app/Providers/Filament/
└── AdminPanelProvider.php
```

**Total file baru:** ~35-40 files

---

## 9. Pertimbangan Teknis

### 9.1 Custom Primary Keys

Semua model VIYGO menggunakan PK non-default. Filament v5 akan membaca `$primaryKey` dari model.

**Yang perlu diperhatikan:**
- Jangan hardcode `->id` di resource — gunakan `->getKey()` atau column name langsung
- Route model binding sudah menggunakan slug untuk Salon — di Filament, menggunakan `id_salon` langsung (Filament punya routing sendiri)

### 9.2 Performa dengan Data Besar

| Tabel | Records | Strategi |
|-------|---------|----------|
| `service` | 190.594 | Paginate 25, lazy-load di relation manager, no global search |
| `salon_images` | 50.492 | Paginate di relation manager, thumbnail-only di list |
| `salon` | 8.750 | Paginate 25, searchable |
| `kategori` | 7.183 | Paginate 25, searchable |
| `staff` | 7.568 | Relation manager only |

### 9.3 Currency

Semua harga ditampilkan dalam **£ GBP**:
```php
TextColumn::make('harga')->money('GBP')
TextColumn::make('total_pembayaran')->money('GBP')
```

### 9.4 SoftDeletes

Model dengan `SoftDeletes`: `Salon`, `Service`, `Staff`, `Promo`.
Filament resource perlu:
```php
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
```

### 9.5 Accessor — Kota.nama & SalonImage.url

- Di Filament columns, menggunakan `nama_kota` (real column), BUKAN `nama` (accessor)
- Di Filament columns, menggunakan `image_url` (real column), BUKAN `url` (accessor)
- Accessor tidak dapat dipakai di SQL-level sorting/filtering

### 9.6 Konflik Route

Filament panel di `/admin` — tidak akan konflik dengan existing routes (`/`, `/cari`, `/salon/{slug}`, dll).
Public layout menggunakan `layouts.public`, Filament menggunakan layout sendiri.

### 9.7 Livewire v4 Compatibility

Filament v5 dibangun khusus untuk Livewire v4 (yang sudah terinstall).
Livewire Flux v2 (frontend) dan Filament v5 (admin) dapat coexist tanpa konflik.

---

## 10. Urutan Eksekusi

Jalankan dalam urutan ini:

### Tahap A — Foundation (15 menit)
1. `composer require filament/filament:"^5.0" -W`
2. `php artisan filament:install --panels`
3. Konfigurasi `AdminPanelProvider.php` (branding, colors, path)
4. Tambahkan `FilamentUser` interface + `canAccessPanel()` di `User.php`
5. untuk admin user: `php artisan make:filament-user` atau update via tinker
6. Verifikasi: buka `/admin` di browser — login page muncul

### Tahap B — Dashboard Widgets (10 menit)
1. untuk `StatsOverview.php`
2. untuk `LatestOrders.php`
3. Verifikasi: dashboard menampilkan stats + tabel order

### Tahap C — Core Resources (40 menit)
1. `SalonResource` + 3 relation managers (Services, Staff, Images)
2. `UserResource`
3. `KategoriResource`
4. `KotaResource`

### Tahap D — Transaction Resources (20 menit)
1. `OrderResource` + OrderDetails relation manager
2. `ReviewResource`
3. `PromoResource`

### Tahap E — ServiceResource (10 menit)
1. `ServiceResource` (standalone — juga accessible via SalonResource relation manager)

### Tahap F — Polish (15 menit)
1. Custom actions (approve/reject salon, toggle review visibility, etc.)
2. Bulk actions
3. Navigation grouping (sidebar: Marketplace, Transactions, Settings)
4. Smoke test semua resource
5. Update `progress.md` dan `PROGRESS_REPORT.md`

**Total estimasi: ~2 jam**

---

## Navigasi Sidebar (Rencana)

```
Dashboard
─────────────
Marketplace
├── Salons (8,750)
├── Services (190K)
├── Categories (7,183)
└── Cities (1,709)
─────────────
Transactions
├── Orders
├── Reviews
└── Promos
─────────────
Users
└── Users (5,769)
```

---

*Plan ini siap dieksekusi. Agent berikutnya: baca file ini end-to-end, lalu jalankan Tahap A–F secara berurutan.*
