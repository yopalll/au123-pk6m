<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumBookmark extends Model
{
    protected $table = 'forum_bookmarks';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = ['id_user', 'id_thread'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'id_thread', 'id_thread');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
