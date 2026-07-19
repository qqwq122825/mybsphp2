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
    'note',
    'sort_order',
    'purpose',
    'benefit_kind',
    'duration_seconds',
    'duration_value',
    'duration_unit',
    'points_amount',
    'fixed_expires_at',
    'face_price_cents',
    'default_agent_price_cents',
    'balance_recharge_cents',
    'code_prefix',
    'code_length',
    'code_charset',
    'secret_length',
    'secret_charset',
    'secret_alphabet',
    'alphabet',
    'account_concurrency_limit',
    'machine_concurrency_limit',
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
            'duration_value' => 'integer',
            'points_amount' => 'integer',
            'fixed_expires_at' => 'immutable_datetime',
            'face_price_cents' => 'integer',
            'default_agent_price_cents' => 'integer',
            'balance_recharge_cents' => 'integer',
            'sort_order' => 'integer',
            'code_length' => 'integer',
            'secret_length' => 'integer',
            'account_concurrency_limit' => 'integer',
            'machine_concurrency_limit' => 'integer',
            'max_activations' => 'integer',
        ];
    }
}
