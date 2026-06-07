# Phase 1B — Eloquent Models + Relationships
## Step 1.3: 24+ Model Files

> **Prerequisite:** Phase 1A selesai (28 tabel sudah di-migrate)  
> **Verifikasi:** `php artisan tinker` → semua model bisa di-query tanpa error

---

## KONTEKS

Semua model baru disimpan di `app/Models/`. Karena V1 menggunakan PK custom (`id_user`, `id_salon`, dll.),
setiap model V2 yang FK ke V1 WAJIB specify foreignKey yang benar.

Konvensi setiap model:
- Set `$table` eksplisit
- Set `$primaryKey` jika bukan `id`
- Set `$fillable` lengkap
- Define semua relasi `belongsTo`, `hasMany`, `belongsToMany`

---

## MODELS YANG DIBUAT

### 1. `ProductCategory`
```php
// app/Models/ProductCategory.php
class ProductCategory extends Model
{
    protected $table = 'product_categories';
    protected $primaryKey = 'id_product_category';
    protected $fillable = ['nama', 'slug', 'deskripsi', 'icon_url', 'parent_id', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id', 'id_product_category');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id', 'id_product_category');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'id_product_category', 'id_product_category');
    }
}
```

### 2. `ProductCollection`
```php
class ProductCollection extends Model
{
    protected $table = 'product_collections';
    protected $primaryKey = 'id_collection';
    protected $fillable = ['nama', 'slug', 'deskripsi', 'banner_url', 'tagline', 'sort_order'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'id_collection', 'id_collection');
    }
}
```

### 3. `Product`
```php
class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id_product';
    protected $fillable = [
        'id_product_category', 'id_collection', 'nama', 'slug', 'deskripsi',
        'key_ingredients', 'full_ingredients', 'cara_pemakaian',
        'harga', 'harga_diskon', 'stok', 'berat_gram', 'volume_ml',
        'skin_type', 'skin_concern', 'brand', 'badge',
        'rating', 'total_review', 'total_sold', 'status', 'is_featured',
        'fresh_product_id', 'fresh_url',
    ];
    protected $casts = ['is_featured' => 'boolean', 'harga' => 'decimal:2'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'id_product_category', 'id_product_category');
    }
    public function collection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'id_collection', 'id_collection');
    }
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'id_product', 'id_product');
    }
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'id_product', 'id_product')->where('is_primary', true);
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'id_product', 'id_product');
    }
    public function orderItems(): HasMany
    {
        return $this->hasMany(ProductOrderItem::class, 'id_product', 'id_product');
    }
}
```

### 4. `ProductImage`
```php
class ProductImage extends Model
{
    protected $table = 'product_images';
    protected $primaryKey = 'id_product_image';
    protected $fillable = ['id_product', 'image_url', 'alt_text', 'is_primary', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
```

### 5. `Wishlist`
```php
class Wishlist extends Model
{
    protected $table = 'wishlists';
    public $timestamps = false;
    protected $fillable = ['id_user', 'id_product'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
```

### 6. `UserSkincareProfile`
```php
class UserSkincareProfile extends Model
{
    protected $table = 'user_skincare_profiles';
    public $timestamps = false;
    protected $fillable = ['id_user', 'skin_type', 'skin_concerns'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
```

### 7. `Cart`
```php
class Cart extends Model
{
    protected $table = 'carts';
    protected $primaryKey = 'id_cart';
    protected $fillable = ['id_user', 'id_product', 'qty'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
```

### 8. `UserAddress`
```php
class UserAddress extends Model
{
    protected $table = 'user_addresses';
    protected $primaryKey = 'id_address';
    protected $fillable = [
        'id_user', 'label', 'nama_penerima', 'phone', 'alamat_lengkap',
        'kota', 'kota_id', 'provinsi', 'provinsi_id', 'kode_pos', 'is_default',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
    public function orders(): HasMany
    {
        return $this->hasMany(ProductOrder::class, 'id_address', 'id_address');
    }
}
```

### 9. `ProductOrder`
```php
class ProductOrder extends Model
{
    protected $table = 'product_orders';
    protected $primaryKey = 'id_product_order';
    protected $fillable = [
        'id_user', 'id_address', 'id_promo', 'kode_order',
        'subtotal', 'biaya_kirim', 'total_diskon', 'poin_digunakan', 'potongan_poin', 'grand_total',
        'kurir', 'layanan_kirim', 'estimasi_tiba', 'resi', 'status', 'catatan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'id_address', 'id_address');
    }
    public function items(): HasMany
    {
        return $this->hasMany(ProductOrderItem::class, 'id_product_order', 'id_product_order');
    }
    public function pembayaran(): HasOne
    {
        return $this->hasOne(ProductPembayaran::class, 'id_product_order', 'id_product_order');
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'id_product_order', 'id_product_order');
    }
}
```

### 10. `ProductOrderItem`
```php
class ProductOrderItem extends Model
{
    protected $table = 'product_order_items';
    protected $primaryKey = 'id_item';
    protected $fillable = ['id_product_order', 'id_product', 'nama_produk', 'qty', 'harga_satuan', 'berat_gram', 'subtotal'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'id_product_order', 'id_product_order');
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
```

### 11. `ProductPembayaran`
```php
class ProductPembayaran extends Model
{
    protected $table = 'product_pembayaran';
    protected $primaryKey = 'id_pembayaran';
    protected $fillable = [
        'id_product_order', 'id_user', 'midtrans_order_id', 'midtrans_transaction_id',
        'snap_token', 'metode', 'jumlah', 'status', 'paid_at',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'id_product_order', 'id_product_order');
    }
}
```

### 12. `ProductReview`
```php
class ProductReview extends Model
{
    protected $table = 'product_reviews';
    protected $primaryKey = 'id_product_review';
    protected $fillable = ['id_user', 'id_product', 'id_product_order', 'rating', 'judul', 'komentar', 'foto_urls', 'is_verified_purchase'];
    protected $casts = ['foto_urls' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
```

### 13. `Lookbook`
```php
class Lookbook extends Model
{
    protected $table = 'lookbooks';
    protected $primaryKey = 'id_lookbook';
    protected $fillable = ['judul', 'slug', 'deskripsi', 'cover_url', 'tema', 'is_published', 'published_at', 'view_count'];

    public function slides(): HasMany
    {
        return $this->hasMany(LookbookSlide::class, 'id_lookbook', 'id_lookbook')->orderBy('sort_order');
    }
}
```

### 14. `LookbookSlide`
```php
class LookbookSlide extends Model
{
    protected $table = 'lookbook_slides';
    protected $primaryKey = 'id_slide';
    protected $fillable = ['id_lookbook', 'judul', 'deskripsi', 'image_url', 'tips', 'sort_order'];

    public function lookbook(): BelongsTo
    {
        return $this->belongsTo(Lookbook::class, 'id_lookbook', 'id_lookbook');
    }
    public function items(): HasMany
    {
        return $this->hasMany(LookbookItem::class, 'id_slide', 'id_slide');
    }
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'lookbook_items', 'id_slide', 'id_product')
                    ->withPivot('position_x', 'position_y')
                    ->withTimestamps();
    }
}
```

### 15. `LookbookItem`
```php
class LookbookItem extends Model
{
    protected $table = 'lookbook_items';
    protected $fillable = ['id_slide', 'id_product', 'position_x', 'position_y'];

    public function slide(): BelongsTo
    {
        return $this->belongsTo(LookbookSlide::class, 'id_slide', 'id_slide');
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
```

### 16. `EmptyReturn`
```php
class EmptyReturn extends Model
{
    protected $table = 'empty_returns';
    protected $primaryKey = 'id_return';
    protected $fillable = [
        'id_user', 'id_product', 'id_salon', 'nama_produk', 'jumlah',
        'metode', 'alamat_pickup', 'status', 'poin_earned',
        'catatan_admin', 'verified_by', 'verified_at',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'id_user', 'id_user'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'id_product', 'id_product'); }
    public function photos(): HasMany { return $this->hasMany(EmptyReturnPhoto::class, 'id_return', 'id_return'); }
}
```

### 17. `EmptyReturnPhoto`
```php
class EmptyReturnPhoto extends Model
{
    protected $table = 'empty_return_photos';
    public $timestamps = false;
    protected $fillable = ['id_return', 'photo_url'];

    public function emptyReturn(): BelongsTo { return $this->belongsTo(EmptyReturn::class, 'id_return', 'id_return'); }
}
```

### 18. `UserPoint`
```php
class UserPoint extends Model
{
    protected $table = 'user_points';
    protected $fillable = ['id_user', 'saldo', 'total_earned', 'total_spent', 'tier'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'id_user', 'id_user'); }
    public function transactions(): HasMany { return $this->hasMany(PointTransaction::class, 'id_user', 'id_user'); }
}
```

### 19. `PointTransaction`
```php
class PointTransaction extends Model
{
    protected $table = 'point_transactions';
    public $timestamps = false;
    protected $fillable = ['id_user', 'type', 'amount', 'source', 'reference_id', 'reference_type', 'description', 'saldo_after'];
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'id_user', 'id_user'); }
}
```

### 20. `ExclusiveContent`
```php
class ExclusiveContent extends Model
{
    protected $table = 'exclusive_contents';
    protected $primaryKey = 'id_content';
    protected $fillable = ['judul', 'slug', 'tipe', 'konten', 'video_url', 'thumbnail_url', 'min_tier', 'is_published'];
}
```

### 21. `ForumCategory`
```php
class ForumCategory extends Model
{
    protected $table = 'forum_categories';
    protected $primaryKey = 'id_forum_category';
    protected $fillable = ['nama', 'slug', 'deskripsi', 'icon', 'sort_order'];

    public function threads(): HasMany { return $this->hasMany(ForumThread::class, 'id_forum_category', 'id_forum_category'); }
}
```

### 22. `ForumThread`
```php
class ForumThread extends Model
{
    protected $table = 'forum_threads';
    protected $primaryKey = 'id_thread';
    protected $fillable = ['id_user', 'id_forum_category', 'judul', 'slug', 'konten', 'view_count', 'like_count', 'reply_count', 'is_pinned', 'is_locked', 'status'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'id_user', 'id_user'); }
    public function category(): BelongsTo { return $this->belongsTo(ForumCategory::class, 'id_forum_category', 'id_forum_category'); }
    public function replies(): HasMany { return $this->hasMany(ForumReply::class, 'id_thread', 'id_thread'); }
    public function taggedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'forum_thread_tags', 'id_thread', 'id_product')->withTimestamps();
    }
}
```

### 23. `ForumReply`
```php
class ForumReply extends Model
{
    protected $table = 'forum_replies';
    protected $primaryKey = 'id_reply';
    protected $fillable = ['id_thread', 'id_user', 'parent_id', 'konten', 'like_count', 'status'];

    public function thread(): BelongsTo { return $this->belongsTo(ForumThread::class, 'id_thread', 'id_thread'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'id_user', 'id_user'); }
    public function parent(): BelongsTo { return $this->belongsTo(ForumReply::class, 'parent_id', 'id_reply'); }
    public function children(): HasMany { return $this->hasMany(ForumReply::class, 'parent_id', 'id_reply'); }
}
```

### 24. `ForumLike`
```php
class ForumLike extends Model
{
    protected $table = 'forum_likes';
    public $timestamps = false;
    protected $fillable = ['id_user', 'likeable_type', 'likeable_id'];
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
}
```

### 25. `ForumBookmark`
```php
class ForumBookmark extends Model
{
    protected $table = 'forum_bookmarks';
    public $timestamps = false;
    protected $fillable = ['id_user', 'id_thread'];
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
}
```

### 26. `ForumThreadTag`
```php
class ForumThreadTag extends Model
{
    protected $table = 'forum_thread_tags';
    protected $fillable = ['id_thread', 'id_product'];
}
```

### 27. `UserBadge`
```php
class UserBadge extends Model
{
    protected $table = 'user_badges';
    protected $fillable = ['id_user', 'badge_slug', 'earned_at'];
    protected $casts = ['earned_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'id_user', 'id_user'); }
}
```

### 28. `CommunityPoint`
```php
class CommunityPoint extends Model
{
    protected $table = 'community_points';
    protected $fillable = ['id_user', 'total_points'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'id_user', 'id_user'); }
}
```

---

## UPDATE MODEL `User` (V1)

Tambahkan relasi baru ke model `User` yang sudah ada di `app/Models/User.php`:

```php
// Tambah di dalam class User:
public function cartItems(): HasMany
{
    return $this->hasMany(Cart::class, 'id_user', 'id_user');
}
public function wishlists(): HasMany
{
    return $this->hasMany(Wishlist::class, 'id_user', 'id_user');
}
public function productOrders(): HasMany
{
    return $this->hasMany(ProductOrder::class, 'id_user', 'id_user');
}
public function addresses(): HasMany
{
    return $this->hasMany(UserAddress::class, 'id_user', 'id_user');
}
public function skincareProfile(): HasOne
{
    return $this->hasOne(UserSkincareProfile::class, 'id_user', 'id_user');
}
public function points(): HasOne
{
    return $this->hasOne(UserPoint::class, 'id_user', 'id_user');
}
public function emptyReturns(): HasMany
{
    return $this->hasMany(EmptyReturn::class, 'id_user', 'id_user');
}
public function forumThreads(): HasMany
{
    return $this->hasMany(ForumThread::class, 'id_user', 'id_user');
}
public function communityPoint(): HasOne
{
    return $this->hasOne(CommunityPoint::class, 'id_user', 'id_user');
}
public function badges(): HasMany
{
    return $this->hasMany(UserBadge::class, 'id_user', 'id_user');
}
```

Tambahkan juga `'admin_store'` ke validasi role di User model jika ada cast/validation.

---

## VERIFIKASI

```bash
php artisan tinker
>>> Product::count()        # → 0 (tabel kosong, tapi tidak error)
>>> UserPoint::count()      # → 0
>>> ForumCategory::count()  # → 0
>>> Cart::first()           # → null
```

Semua harus return tanpa exception. Lanjutkan ke **[phase-1c-scraper-seed.md](phase-1c-scraper-seed.md)**.
