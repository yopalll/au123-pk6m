<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use SoftDeletes;

    protected $table      = 'promo';
    protected $primaryKey = 'id_promo';

    protected $fillable = [
        'nama_promo',
        'deskripsi_promo',
        'diskon',
        'diskon_max',
        'min_transaksi',
        'tipe_promo',
        'kode_promo',
        'time_start',
        'time_expired',
        'stock',
        'used_counter',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'time_start'    => 'datetime',
            'time_expired'  => 'datetime',
            'diskon'        => 'decimal:2',
            'diskon_max'    => 'decimal:2',
            'min_transaksi' => 'decimal:2',
            'stock'         => 'integer',
            'used_counter'  => 'integer',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    /**
     * Many-to-Many: User yang memiliki/mengklaim promo ini.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_promo', 'id_promo', 'id_user')
                    ->withPivot('is_used', 'used_at')
                    ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'id_promo');
    }

    // ──── Scope ─────────────────────────────────────────────

    /**
     * Promo yang masih aktif dan belum expired.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('time_expired', '>=', now());
    }
}
