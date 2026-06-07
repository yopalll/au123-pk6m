<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumThread extends Model
{
    protected $table = 'forum_threads';

    protected $primaryKey = 'id_thread';

    protected $fillable = [
        'id_user', 'id_forum_category', 'judul', 'slug', 'konten',
        'view_count', 'like_count', 'reply_count', 'is_pinned', 'is_locked', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'id_forum_category', 'id_forum_category');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'id_thread', 'id_thread');
    }

    public function taggedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'forum_thread_tags', 'id_thread', 'id_product')
            ->withTimestamps();
    }
}
