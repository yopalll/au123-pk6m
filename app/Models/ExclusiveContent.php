<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExclusiveContent extends Model
{
    protected $table = 'exclusive_contents';

    protected $primaryKey = 'id_content';

    protected $fillable = [
        'judul', 'slug', 'tipe', 'konten',
        'video_url', 'thumbnail_url', 'min_tier', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
