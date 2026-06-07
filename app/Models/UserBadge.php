<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBadge extends Model
{
    protected $table = 'user_badges';

    protected $fillable = ['id_user', 'badge_slug', 'earned_at'];

    protected function casts(): array
    {
        return ['earned_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
