<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Produk dengan kategori ini sebagai kategori UTAMA.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'id_product_category', 'id_product_category');
    }

    /**
     * SEMUA produk yang masuk kategori ini (utama + tambahan) via pivot.
     */
    public function allProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'category_product',
            'id_product_category',
            'id_product'
        )->withTimestamps();
    }
}
