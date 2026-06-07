<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOrderItem extends Model
{
    protected $table = 'product_order_items';

    protected $primaryKey = 'id_item';

    protected $fillable = [
        'id_product_order', 'id_product', 'nama_produk', 'qty',
        'harga_satuan', 'berat_gram', 'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'id_product_order', 'id_product_order');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
