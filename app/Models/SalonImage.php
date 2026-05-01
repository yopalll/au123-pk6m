<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonImage extends Model
{
    protected $table      = 'salon_images';
    protected $primaryKey = 'id_salon_image';

    protected $fillable = [
        'id_salon',
        'image_url',
        'is_primary',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'urutan'     => 'integer',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'id_salon');
    }
}
