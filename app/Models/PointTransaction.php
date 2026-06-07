<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    protected $table = 'point_transactions';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_user', 'type', 'amount', 'source',
        'reference_id', 'reference_type', 'description', 'saldo_after',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
