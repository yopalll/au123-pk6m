<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $table = 'service';

    protected $primaryKey = 'id_service';

    protected $fillable = [
        'id_salon',
        'id_kategori',
        'id_sub_kategori',
        'nama',
        'deskripsi',
        'durasi',
        'harga',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'durasi' => 'integer',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'id_salon');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    public function subKategori(): BelongsTo
    {
        return $this->belongsTo(SubKategori::class, 'id_sub_kategori');
    }

    /**
     * Many-to-Many: Staff yang bisa mengerjakan service ini.
     */
    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'staff_service', 'id_service', 'id_staff')
            ->withTimestamps();
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'id_service');
    }

    // ──── Scope ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
