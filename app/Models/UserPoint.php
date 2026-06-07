<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPoint extends Model
{
    protected $table = 'user_points';

    protected $fillable = ['id_user', 'saldo', 'total_earned', 'total_spent', 'tier'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class, 'id_user', 'id_user');
    }
}
