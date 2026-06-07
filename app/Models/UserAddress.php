<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAddress extends Model
{
    protected $table = 'user_addresses';

    protected $primaryKey = 'id_address';

    protected $fillable = [
        'id_user', 'label', 'nama_penerima', 'phone', 'alamat_lengkap',
        'kota', 'kota_id', 'provinsi', 'provinsi_id', 'kode_pos', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProductOrder::class, 'id_address', 'id_address');
    }
}
