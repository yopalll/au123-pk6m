# 04 — Eloquent Models & Constants V2

> **Tujuan:** Spesifikasi 28 model V2 + update model `User` V1 + Constants class baru. Nama tabel/PK/kolom **harus persis** dengan doc 03. Pola mengikuti V1 (`docs/eloquent-models.md`): custom `$primaryKey`, `casts()` method, relasi eksplisit dengan nama kolom FK.
>
> Lokasi: `app/Models/`. Constants di `app/Constants/`. Observer di `app/Observers/`.

---

## 1. Constants Classes Baru (single source of truth)

Pola ikut `App\Constants\OrderStatus` / `UserRole` V1 (lihat doc 01 §3.1).

```php
// app/Constants/UserRole.php  — UPDATE (tambah ADMIN_STORE)
class UserRole {
    public const CUSTOMER='customer', SALON_OWNER='salon_owner', ADMIN='admin', ADMIN_STORE='admin_store';
    public static function all(): array { return [self::CUSTOMER,self::SALON_OWNER,self::ADMIN,self::ADMIN_STORE]; }
}

// app/Constants/ProductOrderStatus.php
class ProductOrderStatus {
    public const PENDING='pending', PAID='paid', PROCESSING='processing', SHIPPED='shipped',
                 DELIVERED='delivered', COMPLETED='completed', CANCELLED='cancelled', REFUNDED='refunded';
    public static function all(): array { return [self::PENDING,self::PAID,self::PROCESSING,self::SHIPPED,self::DELIVERED,self::COMPLETED,self::CANCELLED,self::REFUNDED]; }
    /** Status di mana customer boleh review. */
    public static function reviewable(): array { return [self::DELIVERED, self::COMPLETED]; }
}

// app/Constants/ProductPaymentStatus.php
class ProductPaymentStatus { public const PENDING='pending',SUCCESS='success',FAILED='failed',EXPIRED='expired',REFUND='refund'; }

// app/Constants/EmptyReturnStatus.php
class EmptyReturnStatus { public const PENDING='pending',APPROVED='approved',REJECTED='rejected',PICKED_UP='picked_up',RECEIVED='received'; }

// app/Constants/PointTier.php
class PointTier {
    public const STARTER='starter', BRONZE='bronze', SILVER='silver', GOLD='gold';
    public const THRESHOLD = ['bronze'=>50, 'silver'=>150, 'gold'=>300];
    /** Hitung tier dari saldo/total_earned. */
    public static function fromPoints(int $p): string {
        return match(true){ $p>=300=>self::GOLD, $p>=150=>self::SILVER, $p>=50=>self::BRONZE, default=>self::STARTER };
    }
}

// app/Constants/ForumStatus.php
class ForumStatus { public const PUBLISHED='published',HIDDEN='hidden',DELETED='deleted'; }

// app/Constants/BadgeSlug.php
class BadgeSlug { public const SKINCARE_GURU='skincare-guru',TOP_REVIEWER='top-reviewer',ECO_WARRIOR='eco-warrior',RISING_STAR='rising-star'; }

// app/Constants/CommunityPoints.php  (nilai aksi gamification)
class CommunityPoints { public const CREATE_THREAD=5, GET_REPLY=1, GET_LIKE=2; }
```

> Pakai konstanta ini di migration enum default, validasi, service, Filament, Blade — JANGAN hardcode string.

---

## 2. Update Model `User` (V1)

Tambahkan ke [`app/Models/User.php`](../../app/Models/User.php) yang sudah ada. **Jangan** ubah `$fillable`/`$guarded` existing.

```php
// canAccessPanel() — tambah cabang store
public function canAccessPanel(Panel $panel): bool {
    if ($panel->getId()==='admin') return $this->role===UserRole::ADMIN && $this->is_active;
    if ($panel->getId()==='owner') return $this->role===UserRole::SALON_OWNER && $this->is_active;
    if ($panel->getId()==='store') return $this->role===UserRole::ADMIN_STORE && $this->is_active;   // V2
    return false;
}

// ──── Relasi V2 ────
public function addresses()      { return $this->hasMany(UserAddress::class, 'id_user'); }
public function defaultAddress() { return $this->hasOne(UserAddress::class, 'id_user')->where('is_default', true); }
public function cartItems()      { return $this->hasMany(Cart::class, 'id_user'); }
public function wishlists()      { return $this->hasMany(Wishlist::class, 'id_user'); }
public function wishlistProducts(){ return $this->belongsToMany(Product::class,'wishlists','id_user','id_product')->withTimestamps(); }
public function skincareProfile(){ return $this->hasOne(UserSkincareProfile::class, 'id_user'); }
public function productOrders()  { return $this->hasMany(ProductOrder::class, 'id_user'); }
public function productReviews() { return $this->hasMany(ProductReview::class, 'id_user'); }
public function points()         { return $this->hasOne(UserPoint::class, 'id_user'); }
public function pointTransactions(){ return $this->hasMany(PointTransaction::class, 'id_user'); }
public function emptyReturns()   { return $this->hasMany(EmptyReturn::class, 'id_user'); }
public function communityPoints(){ return $this->hasOne(CommunityPoint::class, 'id_user'); }
public function badges()         { return $this->hasMany(UserBadge::class, 'id_user'); }
public function forumThreads()   { return $this->hasMany(ForumThread::class, 'id_user'); }
public function forumBookmarks() { return $this->hasMany(ForumBookmark::class, 'id_user'); }

// Helper tier (dipakai checkout free-ongkir & konten eksklusif)
public function tier(): string { return $this->points?->tier ?? PointTier::STARTER; }
public function pointBalance(): int { return (int) ($this->points?->saldo ?? 0); }
```

---

## 3. Modul 1 — E-commerce Models

### Product (model paling penting — cast SET, scope, accessor)
```php
class Product extends Model {
    protected $table='products'; protected $primaryKey='id_product';
    protected $fillable=[
        'id_product_category','id_collection','nama','slug','deskripsi','key_ingredients',
        'full_ingredients','cara_pemakaian','harga','harga_diskon','stok','berat_gram','volume_ml',
        'skin_type','skin_concern','brand','badge','rating','total_review','total_sold',
        'status','is_featured','fresh_product_id','fresh_url',
    ];
    protected function casts(): array { return [
        'harga'=>'decimal:2','harga_diskon'=>'decimal:2','rating'=>'decimal:2',
        'stok'=>'integer','berat_gram'=>'integer','volume_ml'=>'integer',
        'total_review'=>'integer','total_sold'=>'integer','is_featured'=>'boolean',
        'skin_type'=>'array',   // ⚠️ lihat catatan SET di bawah
    ]; }

    // Relasi
    public function category(){ return $this->belongsTo(ProductCategory::class,'id_product_category'); }
    public function collection(){ return $this->belongsTo(ProductCollection::class,'id_collection'); }
    public function images(){ return $this->hasMany(ProductImage::class,'id_product')->orderBy('sort_order'); }
    public function primaryImage(){ return $this->hasOne(ProductImage::class,'id_product')->where('is_primary',true); }
    public function reviews(){ return $this->hasMany(ProductReview::class,'id_product'); }

    // Scope
    public function scopeActive($q){ return $q->where('status','active'); }
    public function scopeFeatured($q){ return $q->where('is_featured',true); }
    public function scopeInStock($q){ return $q->where('stok','>',0); }

    // Accessor harga efektif
    protected function hargaEfektif(): Attribute {
        return Attribute::make(get: fn()=> $this->harga_diskon ?? $this->harga);
    }
}
```
> ⚠️ **Cast SET `skin_type`:** Laravel tidak punya cast SET native. `'array'` cast meng-asumsikan JSON — **tidak cocok** dengan kolom SET MySQL (comma-string). Pilih salah satu:
> - **(A) Kolom `string` + custom cast** (`CommaSeparated`) → `explode(',')`/`implode(',')`. **Direkomendasikan** (portable ke SQLite test).
> - **(B) Kolom SET MySQL + custom cast** yang baca comma-string.
> Jangan pakai `'array'` cast bawaan untuk kolom SET. Sinkronkan keputusan ini dengan doc 03 §11.

### Model E-commerce lain (spesifikasi ringkas)

| Model | $table | $primaryKey | $fillable inti | casts | Relasi utama |
|-------|--------|-------------|----------------|-------|--------------|
| `ProductCategory` | product_categories | id_product_category | nama,slug,deskripsi,icon_url,parent_id,sort_order | — | `parent()` belongsTo self; `children()` hasMany self; `products()` hasMany |
| `ProductCollection` | product_collections | id_collection | nama,slug,deskripsi,banner_url,tagline,sort_order | — | `products()` hasMany |
| `ProductImage` | product_images | id_product_image | id_product,image_url,alt_text,is_primary,sort_order | is_primary:bool | `product()` belongsTo |
| `Wishlist` | wishlists | id (default) | id_user,id_product | — | `user()`,`product()` belongsTo; `$timestamps` only created_at → set `const UPDATED_AT=null` |
| `UserSkincareProfile` | user_skincare_profiles | id | id_user,skin_type,skin_concerns | — | `user()` belongsTo |
| `Cart` | carts | id_cart | id_user,id_product,qty | qty:int | `user()`,`product()`; accessor `subtotal` = product->hargaEfektif * qty |
| `UserAddress` | user_addresses | id_address | id_user,label,nama_penerima,phone,alamat_lengkap,kota,kota_id,provinsi,provinsi_id,kode_pos,is_default | is_default:bool | `user()`,`productOrders()` |
| `ProductOrder` | product_orders | id_product_order | (semua kolom kecuali PK) | money→decimal:2, poin_digunakan:int | lihat code di bawah |
| `ProductOrderItem` | product_order_items | id_item | id_product_order,id_product,nama_produk,qty,harga_satuan,berat_gram,subtotal | money:decimal:2,qty/berat:int | `order()`,`product()` |
| `ProductPembayaran` | product_pembayaran | id_pembayaran | id_product_order,id_user,midtrans_*,snap_token,metode,jumlah,status,paid_at | jumlah:decimal:2,paid_at:datetime,raw_response:array | `order()`,`user()` |
| `ProductReview` | product_reviews | id_product_review | id_user,id_product,id_product_order,rating,judul,komentar,foto_urls,is_verified_purchase,is_visible | rating:int,foto_urls:array,is_*:bool | `user()`,`product()`,`order()`; scope `visible()` |

```php
class ProductOrder extends Model {
    protected $table='product_orders'; protected $primaryKey='id_product_order';
    protected $fillable=['id_user','id_address','id_promo','kode_order','subtotal','biaya_kirim',
        'total_diskon','poin_digunakan','potongan_poin','grand_total','kurir','layanan_kirim',
        'estimasi_tiba','resi','status','catatan'];
    protected function casts(): array { return [
        'subtotal'=>'decimal:2','biaya_kirim'=>'decimal:2','total_diskon'=>'decimal:2',
        'potongan_poin'=>'decimal:2','grand_total'=>'decimal:2','poin_digunakan'=>'integer',
    ]; }
    public function user(){ return $this->belongsTo(User::class,'id_user'); }
    public function address(){ return $this->belongsTo(UserAddress::class,'id_address'); }
    public function promo(){ return $this->belongsTo(Promo::class,'id_promo'); }      // reuse V1
    public function items(){ return $this->hasMany(ProductOrderItem::class,'id_product_order'); }
    public function pembayaran(){ return $this->hasOne(ProductPembayaran::class,'id_product_order'); }
    public function scopeByStatus($q,string $s){ return $q->where('status',$s); }
}
```

---

## 4. Modul 2 — Lookbook Models

| Model | $table | $primaryKey | $fillable | Relasi |
|-------|--------|-------------|-----------|--------|
| `Lookbook` | lookbooks | id_lookbook | judul,slug,deskripsi,cover_url,tema,is_published,published_at,view_count | `slides()` hasMany (orderBy sort_order); scope `published()` |
| `LookbookSlide` | lookbook_slides | id_slide | id_lookbook,judul,deskripsi,image_url,tips,sort_order | `lookbook()` belongsTo; `items()` hasMany; `products()` belongsToMany via lookbook_items |
| `LookbookItem` | lookbook_items | id | id_slide,id_product,position_x,position_y | `slide()`,`product()` belongsTo |

```php
// LookbookSlide: produk yang ditandai di slide
public function products() {
    return $this->belongsToMany(Product::class,'lookbook_items','id_slide','id_product')
                ->withPivot('position_x','position_y')->withTimestamps();
}
```

---

## 5. Modul 3 — Empty Return / Poin Models

| Model | $table | $primaryKey | $fillable | casts | Relasi/Scope |
|-------|--------|-------------|-----------|-------|--------------|
| `EmptyReturn` | empty_returns | id_return | id_user,id_product,id_salon,nama_produk,jumlah,metode,alamat_pickup,status,poin_earned,catatan_admin,verified_by,verified_at | jumlah:int,poin_earned:int,verified_at:datetime | `user()`,`product()`,`salon()`,`verifier()`(belongsTo User 'verified_by'),`photos()`; scope `pending()` |
| `EmptyReturnPhoto` | empty_return_photos | id | id_return,photo_url | — | `return()` belongsTo |
| `UserPoint` | user_points | id | id_user,saldo,total_earned,total_spent,tier | int's | `user()`; method `recalcTier()` pakai `PointTier::fromPoints($this->total_earned)` |
| `PointTransaction` | point_transactions | id | id_user,type,amount,source,reference_id,reference_type,description,saldo_after | amount/saldo_after:int | `user()`; `reference()` morphTo (reference_type,reference_id) |
| `ExclusiveContent` | exclusive_contents | id_content | judul,slug,tipe,konten,video_url,thumbnail_url,min_tier,is_published | is_published:bool | scope `published()`; scope `forTier($tier)` |

```php
// ExclusiveContent: filter konten yang boleh diakses tier user
public function scopeForTier($q, string $tier) {
    $order=['starter'=>0,'bronze'=>1,'silver'=>2,'gold'=>3];
    $allowed=array_keys(array_filter($order, fn($lvl)=> $lvl <= $order[$tier]));
    return $q->whereIn('min_tier', array_intersect($allowed,['bronze','silver','gold']));
}
```

---

## 6. Modul 4 — Community Models

| Model | $table | $primaryKey | $fillable | Relasi/Scope |
|-------|--------|-------------|-----------|--------------|
| `ForumCategory` | forum_categories | id_forum_category | nama,slug,deskripsi,icon,sort_order | `threads()` hasMany |
| `ForumThread` | forum_threads | id_thread | id_user,id_forum_category,judul,slug,konten,view_count,like_count,reply_count,is_pinned,is_locked,status | `user()`,`category()`,`replies()`,`taggedProducts()` belongsToMany via forum_thread_tags; `likes()` morphMany ForumLike; `bookmarks()`; scope `published()` |
| `ForumReply` | forum_replies | id_reply | id_thread,id_user,parent_id,konten,like_count,status | `thread()`,`user()`,`parent()` belongsTo self,`children()` hasMany self; `likes()` morphMany |
| `ForumLike` | forum_likes | id | id_user,likeable_type,likeable_id | `user()`; `likeable()` morphTo. created_at only |
| `ForumBookmark` | forum_bookmarks | id | id_user,id_thread | `user()`,`thread()`. created_at only |
| `ForumThreadTag` | forum_thread_tags | id | id_thread,id_product | `thread()`,`product()` |
| `UserBadge` | user_badges | id | id_user,badge_slug,earned_at | `user()`; earned_at:datetime |
| `CommunityPoint` | community_points | id | id_user,total_points | `user()` |

```php
// ForumThread morph relations (likeable_type pakai morphMap → 'forum_thread')
public function likes() { return $this->morphMany(ForumLike::class, 'likeable'); }
public function taggedProducts() {
    return $this->belongsToMany(Product::class,'forum_thread_tags','id_thread','id_product')->withTimestamps();
}
```
> **Polymorphic morphMap (penting):** daftarkan di `AppServiceProvider::boot()` agar `likeable_type` tersimpan sebagai string pendek stabil (`'forum_thread'`,`'forum_reply'`), bukan FQCN — sesuai nilai di doc 03 (`forum_likes.likeable_type = 'forum_thread'|'forum_reply'`):
> ```php
> use Illuminate\Database\Eloquent\Relations\Relation;
> Relation::enforceMorphMap([
>     'forum_thread' => \App\Models\ForumThread::class,
>     'forum_reply'  => \App\Models\ForumReply::class,
>     'empty_return' => \App\Models\EmptyReturn::class,   // utk point_transactions.reference_type
> ]);
> ```

---

## 7. Observers (side-effects) — hook ke Service (logika di doc 05)

| Observer | Trigger | Aksi |
|----------|---------|------|
| `ProductReviewObserver` | created/updated/deleted `ProductReview` | recalc `products.rating` (avg rating visible) + `total_review`. Pola = `ReviewObserver` V1. |
| `EmptyReturnObserver` | updated status→`approved` | panggil `PointService::credit()` (earn) + isi `poin_earned`,`verified_at`,`verified_by`. |
| `ForumThreadObserver` | created | `CommunityPointService::add(user, CREATE_THREAD)`; cek badge. |
| `ForumReplyObserver` | created | increment `thread.reply_count`; `+GET_REPLY` ke author thread. |
| `ForumLikeObserver` | created/deleted | sync `like_count` di likeable; `+GET_LIKE` ke author saat like. |

> Daftarkan observer via atribut `#[ObservedBy(...)]` di model (Laravel 11+) atau di `AppServiceProvider::boot()`. Idempotensi & race: operasi counter pakai `increment()`/`decrement()` atau `DB::transaction` + lock bila perlu (lihat doc 05).

---

## 8. Catatan implementasi

- **Custom PK pada `belongsToMany`/`morph`:** karena PK induk bukan `id`, pastikan nama kolom FK di pivot benar (argumen ke `belongsToMany`). Untuk morph, `likeable_id` menyimpan PK custom thread/reply (`id_thread`/`id_reply`) — set `getMorphClass()` default OK karena pakai morphMap.
- **`UPDATED_AT=null`** untuk model yang migration-nya cuma `created_at` (Wishlist, ForumLike, ForumBookmark): `public const UPDATED_AT = null;`.
- **Mass assignment:** model V2 boleh pakai `$fillable` biasa (tidak ada kolom sensitif spt role). `ProductOrder.kode_order`/`grand_total` di-set oleh `CheckoutService`, bukan dari input user.
- **Money sebagai decimal string:** cast `decimal:2` mengembalikan string — hati-hati saat aritmetika; cast ke float/`bcmath` di service.

## 9. Verifikasi (acceptance Phase 1 Step 1.3)
```bash
php artisan tinker --execute="App\Models\Product::count(); App\Models\UserPoint::count();"
php artisan tinker --execute="\$u=App\Models\User::first(); dump(\$u->productOrders()->count(), \$u->tier());"
# relasi tidak error & query jalan (count 0 normal sebelum seeding)
```

---

*Berikutnya: `05-backend-services-controllers.md` — controller, service layer, state machine, business logic.*
