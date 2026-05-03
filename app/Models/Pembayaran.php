<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    protected $table      = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';

    protected $fillable = [
        'id_order',
        'id_user',
        'metode_pembayaran',
        'id_transaksi',
        'snap_token',
        'raw_response',
        'jumlah_bayar',
        'status_pembayaran',
        'tanggal_bayar',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_bayar'  => 'decimal:2',
            'tanggal_bayar' => 'datetime',
            'raw_response'  => 'array',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // ──── Scope ─────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status_pembayaran', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status_pembayaran', 'pending');
    }
}
