<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kota extends Model
{
    protected $table = 'kota';

    protected $primaryKey = 'id_kota';

    protected $fillable = [
        'nama_kota',
        'provinsi',
    ];

    // ──── Accessors ────────────────────────────────────────
    // Views/components reference $kota->nama; this aliases to the real column.
    protected function nama(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nama_kota,
        );
    }

    // ──── Relasi ────────────────────────────────────────────

    public function salons(): HasMany
    {
        return $this->hasMany(Salon::class, 'id_kota');
    }
}
