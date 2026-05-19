<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salon extends Model
{
    use SoftDeletes;

    protected $table      = 'salon';
    protected $primaryKey = 'id_salon';

    protected $fillable = [
        'id_user',
        'id_kota',
        'nama_salon',
        'slug',
        'alamat',
        'deskripsi',
        'phone_number',
        'opening_time',
        'closing_time',
        'image_url',
        'maps_url',
        'latitude',
        'longitude',
        'rating',
        'total_review',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude'     => 'decimal:8',
            'longitude'    => 'decimal:8',
            'rating'       => 'decimal:2',
            'total_review' => 'integer',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function kota(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'id_kota');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'id_salon');
    }

    /**
     * M:N: salon ↔ kategori utama (Hair, Nails, ...).
     * Pivot diisi oleh scraper saat visit URL /treatment-group-{slug}/.
     */
    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(
            Kategori::class,
            'salon_kategori',
            'id_salon',
            'id_kategori'
        )->withTimestamps();
    }

    /**
     * M:N: salon ↔ sub_kategori (treatment spesifik: Blow Dry, Pedicure, ...).
     * Pivot diisi oleh scraper saat visit URL /treatment-{slug}/.
     */
    public function subKategoris(): BelongsToMany
    {
        return $this->belongsToMany(
            SubKategori::class,
            'salon_sub_kategori',
            'id_salon',
            'id_sub_kategori'
        )->withTimestamps();
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'id_salon');
    }

    public function images(): HasMany
    {
        return $this->hasMany(SalonImage::class, 'id_salon');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'id_salon');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'id_salon');
    }

    /**
     * Promos owned by this salon (created by the salon owner).
     * Does NOT include platform-wide promos (those have id_salon=NULL).
     */
    public function promos(): HasMany
    {
        return $this->hasMany(Promo::class, 'id_salon');
    }

    /**
     * Foto utama salon.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(SalonImage::class, 'id_salon')->where('is_primary', true);
    }

    // ──── Scope ─────────────────────────────────────────────

    // BUG-A14: Exclude salons whose owner account is deactivated.
    // An inactive owner can no longer manage bookings, so the salon should
    // not appear in public listings.
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereHas('owner', fn ($q) => $q->where('is_active', true));
    }

    public function scopeByKota($query, int $idKota)
    {
        return $query->where('id_kota', $idKota);
    }
}
