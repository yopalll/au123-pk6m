<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmptyReturn extends Model
{
    protected $table = 'empty_returns';

    protected $primaryKey = 'id_return';

    protected $fillable = [
        'id_user', 'id_product', 'id_salon', 'nama_produk', 'jumlah',
        'metode', 'alamat_pickup', 'status', 'poin_earned',
        'catatan_admin', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'id_salon', 'id_salon');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id_user');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EmptyReturnPhoto::class, 'id_return', 'id_return');
    }
}
