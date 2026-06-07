<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'harga' => 'decimal:2',
            'harga_diskon' => 'decimal:2',
            'rating' => 'decimal:2',
        ];
    }

    /**
     * Kategori UTAMA (primary type) — kolom products.id_product_category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'id_product_category', 'id_product_category');
    }

    /**
     * SEMUA kategori (many-to-many) — 1 produk bisa lebih dari 1 tipe.
     * Termasuk kategori utama (di-sync ke pivot oleh seeder) + kategori tambahan.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductCategory::class,
            'category_product',
            'id_product',
            'id_product_category'
        )->withTimestamps();
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
