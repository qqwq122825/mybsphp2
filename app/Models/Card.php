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
    'redeemed_by_account_id',
    'code_lookup_hash',
    'code_ciphertext',
    'code_last4',
    'secret_hash',
    'secret_ciphertext',
    'secret_last4',
    'status',
    'card_is_frozen',
    'agent_starred',
    'user_status',
    'issued_at',
    'activated_at',
    'consumed_at',
    'expires_at',
    'frozen_at',
    'user_frozen_at',
    'last_online_at',
    'user_note',
    'account_concurrency_limit',
    'machine_concurrency_limit',
    'revoked_at',
    'bound_device_ciphertext',
    'bound_device_mask',
    'activation_count',
    'failed_attempt_count',
    'lock_version',
])]
class Card extends Model
{
    /** Read-only management status; an unexpired date does not undo revocation. */
    public function softwareUserStatus(): string
    {
        if ($this->status === 'revoked' || $this->user_status === 'revoked' || $this->revoked_at !== null) {
            return 'revoked';
        }
        if ($this->user_status === 'frozen') {
            return 'frozen';
        }
        if ($this->status === 'active' && $this->expires_at?->lte(now())) {
            return 'expired';
        }

        // Freezing the original used card is separate from freezing its software user.
        return (string) $this->status;
    }

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
        // Keep the original type readable for historical cards after the
        // type is removed from the management list.
        return $this->belongsTo(CardType::class)->withTrashed();
    }

    /**
     * @return BelongsTo<CardBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(CardBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<ApplicationAccount, $this>
     */
    public function redeemedAccount(): BelongsTo
    {
        return $this->belongsTo(ApplicationAccount::class, 'redeemed_by_account_id');
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
            'user_frozen_at' => 'immutable_datetime',
            'last_online_at' => 'immutable_datetime',
            'card_is_frozen' => 'boolean',
            'agent_starred' => 'boolean',
            'revoked_at' => 'immutable_datetime',
            'activation_count' => 'integer',
            'failed_attempt_count' => 'integer',
            'lock_version' => 'integer',
            'account_concurrency_limit' => 'integer',
            'machine_concurrency_limit' => 'integer',
        ];
    }
}
