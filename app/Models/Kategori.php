<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Kategori extends Model
{
    protected $table = 'kategori';

    protected $primaryKey = 'id_kategori';

    protected $fillable = [
        'name',
        'slug',
        'deskripsi',
        'icon_url',
        'treatwell_slug',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * M:N: kategori (mis. Hair) → 6 sub_kategori dropdown navbar
     * Pivot punya `urutan` agar dropdown sorted sesuai navbar Treatwell.
     */
    public function subKategori(): BelongsToMany
    {
        return $this->belongsToMany(
            SubKategori::class,
            'kategori_sub_kategori',
            'id_kategori',
            'id_sub_kategori'
        )
            ->withPivot('urutan')
            ->withTimestamps()
            ->orderByPivot('urutan');
    }

    /**
     * M:N: salon yang menyediakan kategori ini (derived dari service).
     */
    public function salons(): BelongsToMany
    {
        return $this->belongsToMany(
            Salon::class,
            'salon_kategori',
            'id_kategori',
            'id_salon'
        )->withTimestamps();
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'id_kategori');
    }

    /**
     * Hitung services yang masuk kategori ini lewat 2 jalur:
     *   (a) service.id_kategori  langsung, atau
     *   (b) service.id_sub_kategori → pivot kategori_sub_kategori → kategori.
     * Akurat meski di masa depan ada service yang hanya punya id_sub_kategori.
     */
    public function servicesViaPivotCount(): int
    {
        return Cache::remember(
            "kategori:{$this->id_kategori}:services_pivot_count_v3",
            now()->addMinutes(10),
            fn () => Service::query()
                ->whereIn('id_salon', $this->salons()->pluck('salon.id_salon'))
                ->count()
        );
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
