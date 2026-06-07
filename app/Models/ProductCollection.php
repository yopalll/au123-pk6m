<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
