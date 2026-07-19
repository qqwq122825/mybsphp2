<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $issued_at
 * @property CarbonImmutable|null $activated_at
 * @property CarbonImmutable|null $consumed_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $frozen_at
 * @property CarbonImmutable|null $revoked_at
 */
#[Fillable([
    'public_id',
    'batch_id',
    'card_type_id',
    'application_id',
    'code_lookup_hash',
    'code_ciphertext',
    'code_last4',
    'secret_hash',
    'secret_ciphertext',
    'secret_last4',
    'status',
    'issued_at',
    'activated_at',
    'consumed_at',
    'expires_at',
    'frozen_at',
    'revoked_at',
    'bound_device_ciphertext',
    'bound_device_mask',
    'activation_count',
    'failed_attempt_count',
    'lock_version',
])]
class Card extends Model
{
    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<CardType, $this>
     */
    public function cardType(): BelongsTo
    {
        return $this->belongsTo(CardType::class);
    }

    /**
     * @return BelongsTo<CardBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(CardBatch::class, 'batch_id');
    }

    protected function casts(): array
    {
        return [
            'code_ciphertext' => 'encrypted',
            'secret_ciphertext' => 'encrypted',
            'bound_device_ciphertext' => 'encrypted',
            'issued_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'frozen_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'activation_count' => 'integer',
            'failed_attempt_count' => 'integer',
            'lock_version' => 'integer',
        ];
    }
}
