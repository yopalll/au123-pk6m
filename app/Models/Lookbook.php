<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lookbook extends Model
{
    protected $table = 'lookbooks';

    protected $primaryKey = 'id_lookbook';

    protected $fillable = [
        'judul', 'slug', 'deskripsi', 'cover_url', 'tema',
        'is_published', 'published_at', 'view_count',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function slides(): HasMany
    {
        return $this->hasMany(LookbookSlide::class, 'id_lookbook', 'id_lookbook')->orderBy('sort_order');
    }
}
