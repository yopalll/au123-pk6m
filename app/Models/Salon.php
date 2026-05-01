<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * Foto utama salon.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(SalonImage::class, 'id_salon')->where('is_primary', true);
    }

    // ──── Scope ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Use slug for implicit route-model binding when present;
     * controllers still allow id_salon as fallback.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeByKota($query, int $idKota)
    {
        return $query->where('id_kota', $idKota);
    }
}
