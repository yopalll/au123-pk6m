<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumThreadTag extends Model
{
    protected $table = 'forum_thread_tags';

    protected $fillable = ['id_thread', 'id_product'];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'id_thread', 'id_thread');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
