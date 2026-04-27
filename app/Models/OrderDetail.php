<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    protected $table      = 'order_detail';
    protected $primaryKey = 'id_order_detail';

    protected $fillable = [
        'id_order',
        'id_service',
        'id_staff',
        'start_time',
        'end_time',
        'harga_at_order',
        'subtotal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga_at_order' => 'decimal:2',
            'subtotal'       => 'decimal:2',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'id_service');
    }

    /**
     * Staff yang mengerjakan (nullable — bisa null jika belum ditentukan).
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff');
    }

    // ──── Scope ─────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
