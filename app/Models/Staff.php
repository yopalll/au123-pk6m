<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes;

    protected $table = 'staff';

    protected $primaryKey = 'id_staff';

    protected $fillable = [
        'id_salon',
        'name',
        'profile_url',
        'status',
    ];

    // ──── Relasi ────────────────────────────────────────────

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'id_salon');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class, 'id_staff');
    }

    /**
     * Many-to-Many: Service yang bisa dikerjakan staff ini.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'staff_service', 'id_staff', 'id_service')
            ->withTimestamps();
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'id_staff');
    }

    // ──── Scope ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
