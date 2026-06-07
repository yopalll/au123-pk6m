<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPembayaran extends Model
{
    protected $table = 'product_pembayaran';

    protected $primaryKey = 'id_pembayaran';

    protected $fillable = [
        'id_product_order', 'id_user', 'midtrans_order_id', 'midtrans_transaction_id',
        'snap_token', 'metode', 'jumlah', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'id_product_order', 'id_product_order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
