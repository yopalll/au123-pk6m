<?php

namespace App\Models;

use App\Constants\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $table = 'order';

    protected $primaryKey = 'id_order';

    protected $fillable = [
        'id_user',
        'id_salon',
        'id_promo',
        'kode_order',
        'date_order',
        'total_pembayaran',
        'total_diskon',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_order' => 'date',
            'total_pembayaran' => 'decimal:2',
            'total_diskon' => 'decimal:2',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'id_salon');
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class, 'id_promo');
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'id_order');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'id_order');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'id_order');
    }

    // ──── Scope ─────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // BUG-A05: Use OrderStatus constants to eliminate hardcoded status strings.
    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', OrderStatus::CONFIRMED);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', OrderStatus::SUCCESS);
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', OrderStatus::CANCELED);
    }
}
