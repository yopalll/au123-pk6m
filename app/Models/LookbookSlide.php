<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LookbookSlide extends Model
{
    protected $table = 'lookbook_slides';

    protected $primaryKey = 'id_slide';

    protected $fillable = ['id_lookbook', 'judul', 'deskripsi', 'image_url', 'tips', 'sort_order'];

    public function lookbook(): BelongsTo
    {
        return $this->belongsTo(Lookbook::class, 'id_lookbook', 'id_lookbook');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LookbookItem::class, 'id_slide', 'id_slide');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'lookbook_items', 'id_slide', 'id_product')
            ->withPivot('position_x', 'position_y')
            ->withTimestamps();
    }
}
