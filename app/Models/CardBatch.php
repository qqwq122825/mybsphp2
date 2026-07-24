<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'public_id',
    'card_type_id',
    'batch_no',
    'source',
    'sale_flag',
    'planned_quantity',
    'actual_quantity',
    'status',
    'created_by_administrator_id',
    'maker_reference',
    'generated_at',
    'closed_at',
    'note',
])]
class CardBatch extends Model
{
    use SoftDeletes;

    /**
     * @return BelongsTo<CardType, $this>
     */
    public function cardType(): BelongsTo
    {
        return $this->belongsTo(CardType::class)->withTrashed();
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'batch_id');
    }

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'integer',
            'actual_quantity' => 'integer',
            'generated_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }
}
