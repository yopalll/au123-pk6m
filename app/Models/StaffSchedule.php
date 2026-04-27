<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSchedule extends Model
{
    protected $table      = 'staff_schedule';
    protected $primaryKey = 'id_schedule';

    protected $fillable = [
        'id_staff',
        'hari',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    // ──── Relasi ────────────────────────────────────────────

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff');
    }
}
