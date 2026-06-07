<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LookbookItem extends Model
{
    protected $table = 'lookbook_items';

    protected $fillable = ['id_slide', 'id_product', 'position_x', 'position_y'];

    protected function casts(): array
    {
        return [
            'position_x' => 'decimal:2',
            'position_y' => 'decimal:2',
        ];
    }

    public function slide(): BelongsTo
    {
        return $this->belongsTo(LookbookSlide::class, 'id_slide', 'id_slide');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
