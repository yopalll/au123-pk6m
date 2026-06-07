<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumLike extends Model
{
    protected $table = 'forum_likes';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = ['id_user', 'likeable_type', 'likeable_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
