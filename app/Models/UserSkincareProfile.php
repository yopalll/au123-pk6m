<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkincareProfile extends Model
{
    protected $table = 'user_skincare_profiles';

    public $timestamps = false;

    protected $fillable = ['id_user', 'skin_type', 'skin_concerns'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
