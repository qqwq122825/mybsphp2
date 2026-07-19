<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'public_id',
    'application_id',
    'name',
    'code',
    'purpose',
    'benefit_kind',
    'duration_seconds',
    'points_amount',
    'fixed_expires_at',
    'face_price_cents',
    'default_agent_price_cents',
    'code_prefix',
    'code_length',
    'secret_length',
    'alphabet',
    'status',
    'max_activations',
])]
class CardType extends Model
{
    use SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return HasMany<CardBatch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(CardBatch::class);
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'points_amount' => 'integer',
            'fixed_expires_at' => 'immutable_datetime',
            'face_price_cents' => 'integer',
            'default_agent_price_cents' => 'integer',
            'code_length' => 'integer',
            'secret_length' => 'integer',
            'max_activations' => 'integer',
        ];
    }
}
