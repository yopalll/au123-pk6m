<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumReply extends Model
{
    protected $table = 'forum_replies';

    protected $primaryKey = 'id_reply';

    protected $fillable = ['id_thread', 'id_user', 'parent_id', 'konten', 'like_count', 'status'];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'id_thread', 'id_thread');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'parent_id', 'id_reply');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'parent_id', 'id_reply');
    }
}
