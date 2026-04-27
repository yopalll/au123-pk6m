<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $table      = 'kategori';
    protected $primaryKey = 'id_kategori';

    protected $fillable = [
        'name',
        'deskripsi',
        'slug',
        'icon_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'id_kategori');
    }

    // ──── Scope ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
