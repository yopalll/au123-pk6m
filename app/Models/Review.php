<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $table = 'review';

    protected $primaryKey = 'id_review';

    protected $fillable = [
        'id_user',
        'id_salon',
        'id_order',
        'rating',
        'komentar',
        'owner_reply',
        'owner_reply_at',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_visible' => 'boolean',
            'owner_reply_at' => 'datetime',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    // ──── Scope ─────────────────────────────────────────────

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
