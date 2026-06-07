<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    protected $table = 'forum_categories';

    protected $primaryKey = 'id_forum_category';

    protected $fillable = ['nama', 'slug', 'deskripsi', 'icon', 'sort_order'];

    public function threads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'id_forum_category', 'id_forum_category');
    }
}
