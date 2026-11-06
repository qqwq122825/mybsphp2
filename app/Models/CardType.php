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
    'is_timer',
    'timer_pause_limit',
    'timer_pause_max_seconds',
    'points_amount',
    'fixed_expires_at',
    'face_price_cents',
    'default_agent_price_cents',
    'renewal_price_cents',
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

    public function basePriceCents(): int
    {
        return $this->default_agent_price_cents >= 0
            ? (int) $this->default_agent_price_cents : max(0, (int) $this->face_price_cents);
    }

    public function agentSalesEnabled(): bool
    {
        return $this->default_agent_price_cents >= 0;
    }

    /** @return array{pause_limit: int, pause_max_seconds: int}|null */
    public function timerSettings(): ?array
    {
        if ($this->benefit_kind !== 'duration' || ! $this->is_timer) {
            return null;
        }

        return [
            'pause_limit' => $this->timer_pause_limit ?? -1,
            'pause_max_seconds' => $this->timer_pause_max_seconds ?? 0,
        ];
    }

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'duration_value' => 'integer',
            'is_timer' => 'boolean',
            'timer_pause_limit' => 'integer',
            'timer_pause_max_seconds' => 'integer',
            'points_amount' => 'integer',
            'fixed_expires_at' => 'immutable_datetime',
            'face_price_cents' => 'integer',
            'default_agent_price_cents' => 'integer',
            'renewal_price_cents' => 'integer',
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
