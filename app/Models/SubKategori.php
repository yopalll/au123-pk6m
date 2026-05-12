<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubKategori extends Model
{
    protected $table      = 'sub_kategori';
    protected $primaryKey = 'id_sub_kategori';

    protected $fillable = [
        'name',
        'slug',
        'deskripsi',
        'icon_url',
        'treatwell_slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * M:N: sub_kategori bisa terdaftar di lebih dari 1 kategori utama
     * (mis. "Men's Haircut" → Hair + Men's).
     */
    public function kategori(): BelongsToMany
    {
        return $this->belongsToMany(
            Kategori::class,
            'kategori_sub_kategori',
            'id_sub_kategori',
            'id_kategori'
        )->withPivot('urutan')->withTimestamps();
    }

    /**
     * Service per salon yang ditandai sub_kategori ini.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'id_sub_kategori');
    }

    /**
     * M:N: salon yang muncul di listing Treatwell utk sub_kategori ini.
     */
    public function salons(): BelongsToMany
    {
        return $this->belongsToMany(
            Salon::class,
            'salon_sub_kategori',
            'id_sub_kategori',
            'id_salon'
        )->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
