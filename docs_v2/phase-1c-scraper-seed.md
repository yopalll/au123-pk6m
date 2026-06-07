# Phase 1C — Go Scraper + Laravel Seeders
## Step 1.4: Fresh.com Scraper + Step 1.5: FreshProductSeeder, AdminStoreSeeder, ForumCategorySeeder

> **Prerequisite:** Phase 1B selesai (Models sudah ada, tabel produk sudah di-migrate)  
> **Verifikasi:** `Product::count() > 0`, admin_store user ada, 5 forum category terseed

---

## STEP 1.4 — Go Scraper (fresh.com)

> **Tujuan:** Scrape data produk dari fresh.com satu kali untuk seed data awal.
> Scraper berjalan di luar Laravel (Go program terpisah), outputnya berupa file JSON.

### Struktur File

```
scripts/
└── scraper/
    ├── fresh_scraper.go    ← scraper utama
    ├── go.mod
    ├── config.json         ← konfigurasi URL & settings
    └── output/             ← hasil JSON (dibuat otomatis)
```

Gambar produk disimpan ke: `public/images/products/fresh/`

### Buat `scripts/scraper/go.mod`

```
module fresh_scraper

go 1.21

require (
    github.com/chromedp/chromedp v0.9.3
    github.com/PuerkitoBio/goquery v1.9.1
)
```

### Buat `scripts/scraper/config.json`

```json
{
  "base_url": "https://www.fresh.com",
  "collections": [
    "/collections/black-tea-collection",
    "/collections/rose-collection",
    "/collections/soy-collection",
    "/collections/kombucha-collection",
    "/collections/sugar-collection",
    "/collections/moisturizers",
    "/collections/cleansers",
    "/collections/serums-essences",
    "/collections/toners",
    "/collections/eye-care",
    "/collections/masks",
    "/collections/exfoliants",
    "/collections/facial-mists",
    "/collections/lip-care",
    "/collections/body-care",
    "/collections/gift-sets"
  ],
  "usd_to_idr_rate": 16200,
  "output_dir": "output/",
  "image_dir": "../../public/images/products/fresh/",
  "delay_ms": 1500,
  "headless": true,
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
}
```

### Buat `scripts/scraper/fresh_scraper.go`

Scraper ini menggunakan `chromedp` (headless Chrome) + `goquery` untuk parse DOM.

Arsitektur fungsi:
- `main()` — baca config, loop tiap koleksi
- `scrapeProductList(collectionURL)` → `[]string` daftar URL produk
- `scrapeProductDetail(productURL)` → `ProductData` struct
- `parseProductData(doc *goquery.Document)` → extract semua field
- `convertPrice(usdPrice float64, rate float64)` → harga IDR
- `downloadImage(url, destDir string)` → download + simpan gambar
- `exportToJSON(products []ProductData, filename string)` → export JSON

**Output JSON format per produk:**
```json
{
  "fresh_product_id": "black-tea-kombucha-facial-treatment-essence",
  "fresh_url": "https://www.fresh.com/products/...",
  "nama": "Black Tea Kombucha Facial Treatment Essence",
  "kategori": "Serum & Essence",
  "koleksi": "Black Tea Collection",
  "deskripsi": "...",
  "key_ingredients": "Black Tea Ferment, Kombucha, Hyaluronic Acid",
  "full_ingredients": "Water, Lactobacillus/Black Tea Ferment...",
  "cara_pemakaian": "After cleansing, apply to a cotton pad...",
  "harga_usd": 52.00,
  "harga_idr": 842400,
  "volume_ml": 150,
  "berat_gram": 200,
  "skin_type": "all",
  "skin_concern": "dehydration,dullness",
  "badge": "bestseller",
  "images": [
    "public/images/products/fresh/black-tea-kombucha-essence-1.jpg"
  ]
}
```

**Cara menjalankan scraper:**
```bash
cd scripts/scraper
go mod tidy
go run fresh_scraper.go
```

Output: file JSON per kategori di `scripts/scraper/output/` dan gambar di `public/images/products/fresh/`.

---

## STEP 1.5 — Laravel Seeders

### Seeder 1: `FreshProductSeeder`

**File:** `database/seeders/FreshProductSeeder.php`

Logic:
1. Baca semua file JSON dari `scripts/scraper/output/*.json`
2. Loop tiap produk, lakukan `updateOrCreate` dengan key `fresh_product_id`
3. Buat/update: `ProductCategory` (by slug), `ProductCollection` (by slug), `Product`, `ProductImage`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\ProductCategory;
use App\Models\ProductCollection;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Str;

class FreshProductSeeder extends Seeder
{
    public function run(): void
    {
        $outputDir = base_path('scripts/scraper/output');

        if (!File::isDirectory($outputDir)) {
            $this->command->warn("Output dir tidak ditemukan: {$outputDir}");
            $this->command->warn("Jalankan scraper dulu: cd scripts/scraper && go run fresh_scraper.go");
            return;
        }

        $jsonFiles = File::glob($outputDir . '/*.json');

        foreach ($jsonFiles as $file) {
            $products = json_decode(File::get($file), true);
            if (!$products) continue;

            foreach ($products as $data) {
                // Upsert ProductCategory
                $category = ProductCategory::updateOrCreate(
                    ['slug' => Str::slug($data['kategori'])],
                    ['nama' => $data['kategori'], 'slug' => Str::slug($data['kategori'])]
                );

                // Upsert ProductCollection (jika ada)
                $collection = null;
                if (!empty($data['koleksi'])) {
                    $collection = ProductCollection::updateOrCreate(
                        ['slug' => Str::slug($data['koleksi'])],
                        ['nama' => $data['koleksi'], 'slug' => Str::slug($data['koleksi'])]
                    );
                }

                // Upsert Product
                $product = Product::updateOrCreate(
                    ['fresh_product_id' => $data['fresh_product_id']],
                    [
                        'id_product_category' => $category->id_product_category,
                        'id_collection'       => $collection?->id_collection,
                        'nama'                => $data['nama'],
                        'slug'                => Str::slug($data['nama']),
                        'deskripsi'           => $data['deskripsi'] ?? '',
                        'key_ingredients'     => $data['key_ingredients'] ?? null,
                        'full_ingredients'    => $data['full_ingredients'] ?? null,
                        'cara_pemakaian'      => $data['cara_pemakaian'] ?? null,
                        'harga'               => $data['harga_idr'],
                        'stok'                => 100,
                        'berat_gram'          => $data['berat_gram'] ?? 200,
                        'volume_ml'           => $data['volume_ml'] ?? null,
                        'skin_type'           => $data['skin_type'] ?? 'all',
                        'skin_concern'        => $data['skin_concern'] ?? null,
                        'brand'               => 'Fresh',
                        'badge'               => $data['badge'] ?? null,
                        'fresh_url'           => $data['fresh_url'],
                        'status'              => 'active',
                    ]
                );

                // Upsert ProductImages
                if (!empty($data['images'])) {
                    // Hapus gambar lama dulu jika re-seed
                    $product->images()->delete();
                    foreach ($data['images'] as $i => $imageUrl) {
                        ProductImage::create([
                            'id_product' => $product->id_product,
                            'image_url'  => $imageUrl,
                            'is_primary' => $i === 0,
                            'sort_order' => $i,
                        ]);
                    }
                }
            }
        }

        $this->command->info('FreshProductSeeder selesai. Total produk: ' . Product::count());
    }
}
```

### Prasyarat: tambah konstanta role

Sebelum seeder, update `app/Constants/UserRole.php` (lihat [CATATAN-LINGKUNGAN §3](CATATAN-LINGKUNGAN.md)):

```php
public const ADMIN_STORE = 'admin_store';   // tambah konstanta baru
// + masukkan ke array all():
public static function all(): array
{
    return [self::CUSTOMER, self::SALON_OWNER, self::ADMIN, self::ADMIN_STORE];
}
```

### Seeder 2: `AdminStoreSeeder`

**File:** `database/seeders/AdminStoreSeeder.php`

> 🔴 **PENTING (lihat [CATATAN-LINGKUNGAN §2](CATATAN-LINGKUNGAN.md)):**
> - `users` V1 pakai `first_name`/`last_name` (bukan `name`).
> - Kolom `role` ada di **`$guarded`** → **TIDAK bisa** di-set via `create()`/`updateOrCreate()` array.
>   Wajib assignment properti eksplisit lalu `save()`.

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Constants\UserRole;
use App\Models\User;

class AdminStoreSeeder extends Seeder
{
    public function run(): void
    {
        // 1) firstOrCreate TANPA role (role guarded → diabaikan kalau di array)
        $user = User::firstOrCreate(
            ['email' => 'admin.store@viygo.id'],
            [
                'first_name'        => 'Admin',
                'last_name'         => 'Store VIYGO',
                'password'          => Hash::make('ViygoStore2026!'),
                'phone_number'      => null,
                'email_verified_at' => now(),
            ]
        );

        // 2) Set kolom guarded secara eksplisit
        $user->role      = UserRole::ADMIN_STORE;
        $user->is_active = true;
        $user->save();

        $this->command->info('Admin Store user berhasil dibuat: admin.store@viygo.id');
        $this->command->warn('INGAT: Ganti password default setelah deploy ke production!');
    }
}
```

**Kredensial default:**
- Email: `admin.store@viygo.id`
- Password: `ViygoStore2026!`
- Role: `admin_store`

### Seeder 3: `ForumCategorySeeder`

**File:** `database/seeders/ForumCategorySeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ForumCategory;

class ForumCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nama' => 'Review Produk',       'slug' => 'review-produk',     'icon' => '🧴', 'deskripsi' => 'Ulasan dan diskusi produk skincare',          'sort_order' => 1],
            ['nama' => 'Tips Skincare',        'slug' => 'tips-skincare',     'icon' => '💡', 'deskripsi' => 'Tips dan rekomendasi perawatan kulit',        'sort_order' => 2],
            ['nama' => 'Routine & Lifestyle',  'slug' => 'routine-lifestyle', 'icon' => '🌿', 'deskripsi' => 'Sharing daily skincare routine',              'sort_order' => 3],
            ['nama' => 'Peduli Lingkungan',    'slug' => 'peduli-lingkungan', 'icon' => '♻️', 'deskripsi' => 'Diskusi sustainability, daur ulang',          'sort_order' => 4],
            ['nama' => 'Diskusi Umum',         'slug' => 'diskusi-umum',      'icon' => '💬', 'deskripsi' => 'Topik bebas terkait beauty & wellness',      'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            ForumCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        $this->command->info('5 forum kategori berhasil di-seed.');
    }
}
```

---

## STEP 1.5b — Filament Store Panel Setup (Admin Store)

> **PENTING:** Ini fondasi panel `/admin/store`. Semua Filament Resource di Phase 2–3
> (`ProductResource`, `LookbookResource`, `EmptyReturnResource`, dll.) **mendaftar ke panel ini**.
> Buat panel dulu SEBELUM resource manapun.

> 🔴 **Filament v5.6 — bukan v3** (lihat [CATATAN-LINGKUNGAN §5](CATATAN-LINGKUNGAN.md)).
> **Tiru persis** `app/Providers/Filament/OwnerPanelProvider.php` yang sudah jalan di repo.

### 1. Buat Panel Provider

```bash
php artisan make:filament-panel store
```

Hasil: `app/Providers/Filament/StorePanelProvider.php`. Isi mengikuti gaya OwnerPanelProvider v5
(salin middleware stack-nya persis):

```php
<?php
namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsActive;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
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
            ->profile()
            ->brandName('VIYGO Store')
            ->darkMode(false)
            ->colors(['primary' => Color::Amber])
            ->discoverResources(in: app_path('Filament/Store/Resources'), for: 'App\\Filament\\Store\\Resources')
            ->discoverPages(in: app_path('Filament/Store/Pages'), for: 'App\\Filament\\Store\\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Store/Widgets'), for: 'App\\Filament\\Store\\Widgets')
            ->widgets([AccountWidget::class])
            ->navigationGroups(['Katalog', 'Pesanan', 'Konten', 'Komunitas'])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsActive::class,
            ]);
    }
}
```

### 2. Daftarkan Provider

Cek `bootstrap/providers.php` — sudah ada `AdminPanelProvider` & `OwnerPanelProvider`.
Tambahkan StorePanelProvider:

```php
return [
    // ... provider lain
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\OwnerPanelProvider::class,
    App\Providers\Filament\StorePanelProvider::class,   // ← BARU
];
```

### 3. Kontrol Akses Role — TAMBAH case `store`

> 🔴 `User` **sudah** `implements FilamentUser` dan **sudah punya** `canAccessPanel()`
> (kasus admin/owner). **JANGAN timpa** — tambah satu cabang `store` saja.
> Konstanta `UserRole::ADMIN_STORE` sudah ditambah di Seeder 2 di atas.

`canAccessPanel()` V1 saat ini:
```php
public function canAccessPanel(Panel $panel): bool
{
    if ($panel->getId() === 'admin') {
        return $this->role === UserRole::ADMIN && $this->is_active;
    }
    if ($panel->getId() === 'owner') {
        return $this->role === UserRole::SALON_OWNER && $this->is_active;
    }
    return false;
}
```

Ubah menjadi (tambah blok `store` sebelum `return false;`):
```php
    if ($panel->getId() === 'store') {
        return in_array($this->role, [UserRole::ADMIN_STORE, UserRole::ADMIN], true)
               && $this->is_active;
    }
```

> Customer & salon_owner otomatis tertolak (role tidak cocok). `admin` (super admin) tetap boleh masuk.

### 4. Per-Resource Permission (PRD Section 10.3)

Permission matrix PRD diterapkan langsung di tiap Resource yang dibuat di Phase 2–3.
Contoh untuk resource yang **read + update saja** (mis. `ProductOrderResource` — tidak boleh create/delete):

```php
// Di dalam ProductOrderResource
public static function canCreate(): bool { return false; }
public static function canDelete($record): bool { return false; }
// Read & Update tetap aktif (default true)
```

Acuan matrix (dari PRD Section 10.3):

| Resource | Create | Read | Update | Delete |
|----------|--------|------|--------|--------|
| Products, Categories, Collections | ✅ | ✅ | ✅ | ✅ |
| Lookbooks, Exclusive Contents | ✅ | ✅ | ✅ | ✅ |
| Product Orders | ❌ | ✅ | ✅ (status, resi) | ❌ |
| Product Reviews, Forum Threads | ❌ | ✅ | ✅ (hide/pin) | ✅ |
| Empty Return | ❌ | ✅ | ✅ (approve/reject) | ❌ |
| Users | ❌ | ✅ (view) | ❌ | ❌ |

### Verifikasi Step 1.5b

```
1. php artisan db:seed --class=AdminStoreSeeder  (jika belum)
2. Buka /admin/store/login → login admin.store@viygo.id / ViygoStore2026!
3. Dashboard Filament tampil (resource masih kosong — diisi Phase 2-3)
4. Login sebagai customer → akses /admin/store → 403 Forbidden
```

---

## CARA MENJALANKAN

```bash
# 1. Jalankan scraper (perlu Go + Chrome terinstall)
cd scripts/scraper
go mod tidy
go run fresh_scraper.go
cd ../..

# 2. Seed data produk dari JSON hasil scraper
php artisan db:seed --class=FreshProductSeeder

# 3. Buat user admin store
php artisan db:seed --class=AdminStoreSeeder

# 4. Seed kategori forum
php artisan db:seed --class=ForumCategorySeeder
```

---

## VERIFIKASI

```bash
php artisan tinker
>>> Product::count()        # → > 0 (data dari fresh.com)
>>> ProductCategory::count() # → > 0
>>> User::where('role','admin_store')->first()->email  # → 'admin.store@viygo.id'
>>> ForumCategory::count()  # → 5
```

Lanjutkan ke **[phase-1d-config-nav.md](phase-1d-config-nav.md)**.
