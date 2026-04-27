<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kota extends Model
{
    protected $table      = 'kota';
    protected $primaryKey = 'id_kota';

    protected $fillable = [
        'nama_kota',
        'provinsi',
    ];

    // ──── Relasi ────────────────────────────────────────────

    public function salons(): HasMany
    {
        return $this->hasMany(Salon::class, 'id_kota');
    }
}
