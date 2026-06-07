<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductOrder extends Model
{
    protected $table = 'product_orders';

    protected $primaryKey = 'id_product_order';

    protected $fillable = [
        'id_user', 'id_address', 'id_promo', 'kode_order',
        'subtotal', 'biaya_kirim', 'total_diskon', 'poin_digunakan', 'potongan_poin', 'grand_total',
        'kurir', 'layanan_kirim', 'estimasi_tiba', 'resi', 'status', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'biaya_kirim' => 'decimal:2',
            'total_diskon' => 'decimal:2',
            'potongan_poin' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'id_address', 'id_address');
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class, 'id_promo', 'id_promo');
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
