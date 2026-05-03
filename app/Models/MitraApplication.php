<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MitraApplication extends Model
{
    protected $table      = 'mitra_applications';
    protected $primaryKey = 'id_application';

    protected $fillable = [
        'nama_salon',
        'nama_pemilik',
        'email',
        'phone',
        'id_kota',
        'catatan',
        'status',
    ];

    public function kota(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'id_kota');
    }
}
