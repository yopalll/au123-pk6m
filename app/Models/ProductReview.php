<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $table = 'product_reviews';

    protected $primaryKey = 'id_product_review';

    protected $fillable = [
        'id_user', 'id_product', 'id_product_order', 'rating',
        'judul', 'komentar', 'foto_urls', 'is_verified_purchase',
    ];

    protected function casts(): array
    {
        return [
            'foto_urls' => 'array',
            'is_verified_purchase' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'id_product_order', 'id_product_order');
    }
}
