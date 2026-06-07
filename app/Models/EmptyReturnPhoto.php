<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmptyReturnPhoto extends Model
{
    protected $table = 'empty_return_photos';

    public $timestamps = false;

    protected $fillable = ['id_return', 'photo_url'];

    public function emptyReturn(): BelongsTo
    {
        return $this->belongsTo(EmptyReturn::class, 'id_return', 'id_return');
    }
}
