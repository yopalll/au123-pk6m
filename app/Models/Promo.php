<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use SoftDeletes;

    protected $table = 'promo';

    protected $primaryKey = 'id_promo';

    protected $fillable = [
        'id_salon',
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
            'time_start' => 'datetime',
            'time_expired' => 'datetime',
            'diskon' => 'decimal:2',
            'diskon_max' => 'decimal:2',
            'min_transaksi' => 'decimal:2',
            'stock' => 'integer',
            'used_counter' => 'integer',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    /**
     * Salon yang punya promo ini. NULL = platform-wide promo dari admin.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'id_salon');
    }

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

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('time_expired', '>=', now());
    }

    public function scopeByCode($query, string $code)
    {
        return $query->active()->where('kode_promo', strtoupper(trim($code)));
    }

    // ──── Business logic ────────────────────────────────────

    public function calculateDiscount(float $total): float
    {
        if ($this->tipe_promo === 'percentage') {
            $discount = $total * ((float) $this->diskon / 100);
            if ($this->diskon_max && $discount > (float) $this->diskon_max) {
                $discount = (float) $this->diskon_max;
            }
        } else {
            $discount = min((float) $this->diskon, $total);
        }

        return round($discount, 2);
    }

    public function isSoldOut(): bool
    {
        return $this->stock > 0 && $this->used_counter >= $this->stock;
    }

    public function meetsMinimum(float $total): bool
    {
        return $this->min_transaksi <= 0 || $total >= (float) $this->min_transaksi;
    }
}
