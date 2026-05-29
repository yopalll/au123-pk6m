# 07 — Admin Store (Filament v5 Panel)

> **Tujuan:** Spesifikasi panel Filament ke-3 `store` (`/admin/store`) untuk role `admin_store`: provider, registrasi, authorization, 9 Resource, widget dashboard.
>
> ⚠️ **Filament v5.6** (bukan v3 spt PRD §10.1). Syntax di sini diambil dari **resource V1 nyata** (`app/Filament/Resources/KategoriResource.php`) — pakai `Filament\Schemas\Schema`, actions di namespace `Filament\Actions`. **Selalu jadikan resource V1 sebagai contoh kanonik saat coding.**

---

## 1. Prasyarat
- Migration ALTER `users.role` + `UserRole::ADMIN_STORE` sudah ada (doc 03 §1, doc 04 §1).
- `User::canAccessPanel()` sudah punya cabang `store` (doc 04 §2):
  ```php
  if ($panel->getId()==='store') return $this->role===UserRole::ADMIN_STORE && $this->is_active;
  ```
- `AdminStoreSeeder` sudah membuat user `admin.store@viygo.id` (doc 06 §4.5).

---

## 2. `StorePanelProvider`

**File:** `app/Providers/Filament/StorePanelProvider.php`. Pola = `OwnerPanelProvider` V1 (doc 01 §3.4), beda `id`/`path`/namespace/`navigationGroups`. **Bukan** `->default()`.

```php
<?php
namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsActive;
use Filament\Http\Middleware\{Authenticate, AuthenticateSession, DisableBladeIconComponents, DispatchServingFilamentEvent};
use Filament\Pages\Dashboard;
use Filament\{Panel, PanelProvider};
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\{AddQueuedCookiesToResponse, EncryptCookies};
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StorePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('store')
            ->path('admin/store')
            ->login()
            ->brandName('VIYGO Store')
            ->favicon(asset('favicon.ico'))
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#1B2D6B'),
                'info'    => Color::hex('#4BA3CC'),
            ])
            ->discoverResources(in: app_path('Filament/Store/Resources'), for: 'App\\Filament\\Store\\Resources')
            ->discoverPages(in: app_path('Filament/Store/Pages'), for: 'App\\Filament\\Store\\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Store/Widgets'), for: 'App\\Filament\\Store\\Widgets')
            ->widgets([AccountWidget::class])
            ->navigationGroups(['Katalog', 'Pesanan', 'Konten', 'Komunitas'])
            ->middleware([
                EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
                AuthenticateSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class,
                SubstituteBindings::class, DisableBladeIconComponents::class, DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class, EnsureUserIsActive::class]);
    }
}
```

**Registrasi** di [`bootstrap/providers.php`](../../bootstrap/providers.php):
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\OwnerPanelProvider::class,
    App\Providers\Filament\StorePanelProvider::class,   // ← TAMBAH
    App\Providers\FortifyServiceProvider::class,
];
```

> [!CAUTION]
> Path `admin/store` adalah **sub-path dari `admin`**. Filament mendukung ini, tapi pastikan tidak bentrok dengan route panel admin (`admin`). Karena panel `admin` adalah `->default()` dan punya path `admin`, panel `store` di `admin/store` harus didaftarkan dengan path yang lebih spesifik. **Verifikasi `php artisan route:list | grep admin/store` tidak konflik.** Jika bentrok, ubah path jadi `store` (`/store`) — fungsional sama, hanya URL beda.

---

## 3. Authorization

- Akses panel: hanya `admin_store` + `is_active` (via `canAccessPanel`). Admin super (`admin`) **tidak** otomatis masuk panel store kecuali ditambahkan.
- Tidak perlu query-scoping (beda dari Owner yang scope ke salon sendiri) — Admin Store melihat **semua** produk/pesanan.
- Matriks CRUD (PRD §10.3): batasi aksi di tiap Resource sesuai tabel. Implementasi: hilangkan `CreateAction`/`DeleteAction` di Resource yang read/update-only (ProductOrder, EmptyReturn), atau override `canCreate()`/`canDelete()` = false.

| Resource | C | R | U | D |
|----------|---|---|---|---|
| Product, ProductCategory, ProductCollection, Lookbook, ExclusiveContent | ✅ | ✅ | ✅ | ✅ |
| ProductOrder | ❌ | ✅ | ✅(status,resi) | ❌ |
| ProductReview, ForumModeration | ❌ | ✅ | ✅(hide/pin) | ✅ |
| EmptyReturn | ❌ | ✅ | ✅(approve/reject) | ❌ |

---

## 4. Resources (9) — `app/Filament/Store/Resources/`

Semua `protected static string | \UnitEnum | null $navigationGroup` pakai grup dari §2. Skeleton mengikuti `KategoriResource` V1.

### 4.1 `ProductResource` (Katalog) — paling kompleks
Form (Filament v5 `Schema`):
```php
public static function form(Schema $form): Schema {
    return $form->schema([
        Forms\Components\TextInput::make('nama')->required(),
        Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord:true),
        Forms\Components\Select::make('id_product_category')->relationship('category','nama')->required(),
        Forms\Components\Select::make('id_collection')->relationship('collection','nama')->nullable(),
        Forms\Components\Textarea::make('deskripsi')->columnSpanFull(),
        Forms\Components\Textarea::make('key_ingredients'),
        Forms\Components\Textarea::make('full_ingredients'),
        Forms\Components\Textarea::make('cara_pemakaian'),
        Forms\Components\TextInput::make('harga')->numeric()->prefix('Rp')->required(),
        Forms\Components\TextInput::make('harga_diskon')->numeric()->prefix('Rp'),
        Forms\Components\TextInput::make('stok')->numeric()->default(0),
        Forms\Components\TextInput::make('berat_gram')->numeric()->required()->suffix('g'),
        Forms\Components\TextInput::make('volume_ml')->numeric()->suffix('ml'),
        Forms\Components\Select::make('skin_type')->multiple()
            ->options(['all'=>'All','oily'=>'Oily','dry'=>'Dry','combination'=>'Combination','sensitive'=>'Sensitive','normal'=>'Normal']),
        Forms\Components\TextInput::make('skin_concern')->helperText('comma-separated'),
        Forms\Components\TextInput::make('brand')->default('Fresh'),
        Forms\Components\Select::make('badge')->options(['bestseller'=>'Bestseller','new'=>'New','eco'=>'Eco','travel_size'=>'Travel Size']),
        Forms\Components\Select::make('status')->options(['active'=>'Active','inactive'=>'Inactive','out_of_stock'=>'Out of Stock'])->default('active'),
        Forms\Components\Toggle::make('is_featured'),
    ]);
}
```
> `skin_type` multiple Select → simpan comma-separated (custom cast, doc 04 §3). Gambar produk via **RelationManager** `ImagesRelationManager` (pola `SalonResource/RelationManagers/ImagesRelationManager.php` V1) — `is_primary`, `sort_order`, `FileUpload`.
> Table: kolom nama, category, harga, stok (badge merah jika <10), rating, total_sold, status; filter status/kategori/koleksi; bulk activate/deactivate.

### 4.2 `ProductCategoryResource` / `ProductCollectionResource`
CRUD sederhana (pola KategoriResource): nama, slug, deskripsi, icon/banner, sort_order, parent (kategori). `subKategori`-style self-relation untuk parent.

### 4.3 `ProductOrderResource` (Pesanan) — read + update status/resi
```php
public static function canCreate(): bool { return false; }
public static function canDelete($record): bool { return false; }
```
Table: kode_order, user, grand_total, status (badge warna), kurir, resi, created_at. Filter status. Action **EditAction** hanya untuk field `status` + `resi` + `estimasi_tiba`. Gunakan `ProductOrderState::canTransition()` (doc 05 §4) untuk membatasi pilihan status berikutnya. RelationManager untuk `items` (read-only). Tab "Perlu Diproses" (status=paid) & "Pengiriman" (status=shipped) via `getEloquentQuery()` atau navigation badge.

### 4.4 `ProductReviewResource` — moderasi
Read + toggle `is_visible` + delete. Table: produk, user, rating, komentar, is_verified_purchase, is_visible. Bulk hide/show. Saat visibilitas berubah → `ProductReviewObserver` recalc rating produk.

### 4.5 `LookbookResource` (Konten)
CRUD lookbook + RelationManager `slides` (judul, image, tips, sort) + di tiap slide RelationManager/atau repeater untuk `items` (pilih produk + posisi x/y). Toggle `is_published`.

### 4.6 `EmptyReturnResource` — verifikasi (approve/reject → kredit poin)
Read + custom action. **Inti integrasi poin:**
```php
\Filament\Actions\Action::make('approve')
    ->label('Approve')->color('success')->icon('heroicon-o-check')
    ->visible(fn($r)=> $r->status==='pending')
    ->form([ Forms\Components\TextInput::make('poin')->numeric()->required()->default(5) ])
    ->action(function ($record, array $data) {
        app(\App\Services\PointService::class)->credit(
            $record->user, (int)$data['poin'], 'empty_return', $record, 'Empty return approved'
        );
        $record->update([
            'status'=>'approved', 'poin_earned'=>$data['poin'],
            'verified_by'=>auth()->id(), 'verified_at'=>now(),
        ]);
        app(\App\Services\BadgeService::class)->evaluate($record->user);   // Eco Warrior
    }),
\Filament\Actions\Action::make('reject')
    ->label('Reject')->color('danger')
    ->visible(fn($r)=> $r->status==='pending')
    ->form([ Forms\Components\Textarea::make('catatan_admin')->required() ])
    ->action(fn($record,$data)=> $record->update(['status'=>'rejected','catatan_admin'=>$data['catatan_admin'],'verified_by'=>auth()->id(),'verified_at'=>now()])),
```
> Logika kredit poin bisa juga di `EmptyReturnObserver` (status→approved) — pilih satu agar tidak dobel. Doc 04 §7 & doc 05 §2.5.

### 4.7 `ExclusiveContentResource`
CRUD: judul, slug, tipe (article/video/tip), konten (RichEditor untuk article), video_url, thumbnail, min_tier (Select bronze/silver/gold), is_published.

### 4.8 `ForumModerationResource`
Read + pin/lock/hide/delete thread. Table: judul, kategori, user, reply_count, like_count, status, is_pinned. Actions: toggle pin, set status hidden, delete. Tidak ada create.

---

## 5. Widgets Dashboard — `app/Filament/Store/Widgets/`

Pola `StatsOverview` / `LatestOrders` V1 (`app/Filament/Widgets/`). Dashboard `/admin/store` (PRD §10.4):

| Widget | Isi |
|--------|-----|
| `StoreStatsOverview` (StatsOverviewWidget) | Total produk aktif; Pesanan hari ini (+nilai); Pesanan perlu diproses (status=paid); Empty return pending |
| `SalesChartWidget` (ChartWidget) | Grafik penjualan 7 hari (Σ grand_total per hari, status≥paid) |
| `LowStockTable` (TableWidget) | Produk `stok < 10` & status active |
| `PendingEmptyReturnsTable` | empty_returns status=pending |

Contoh stat:
```php
protected function getStats(): array {
    return [
        Stat::make('Produk Aktif', Product::active()->count()),
        Stat::make('Pesanan Hari Ini', ProductOrder::whereDate('created_at', today())->count())
            ->description('Rp '.number_format(ProductOrder::whereDate('created_at',today())->sum('grand_total'))),
        Stat::make('Perlu Diproses', ProductOrder::byStatus('paid')->count())->color('warning'),
        Stat::make('Empty Return Pending', EmptyReturn::where('status','pending')->count())->color('info'),
    ];
}
```

---

## 6. Login & UX
- Panel `store` punya halaman login sendiri (`->login()`). User `admin_store` login di `/admin/store/login`.
- Brand "VIYGO Store", warna sama dengan panel lain (konsistensi).
- (Opsional) renderHook link login antar-panel spt V1 (`filament.auth.login-links-*`).

## 7. Verifikasi (acceptance Phase 1 Step 1.3 / Phase 4 Step 4.2)
```bash
php artisan route:list | grep "admin/store"            # panel terdaftar, tidak konflik
# Login admin.store@viygo.id → /admin/store tampil; customer/owner ditolak (403/redirect)
# Resource CRUD: buat produk, ubah status pesanan, approve empty return → poin user bertambah
```

> [!NOTE]
> Filament v5 API masih tergolong baru. Jika ada method yang berbeda dari snippet di atas, **rujuk resource V1 yang sudah jalan** (`app/Filament/Resources/*`, `app/Filament/Owner/Resources/*`) sebagai sumber kebenaran syntax, lalu `php artisan filament:upgrade` jika perlu.

---

*Berikutnya: `08-security-nfr-testing.md` — auth/authz, rate limit, cache, queue, strategi testing, kompatibilitas DB test.*
