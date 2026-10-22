<?php

namespace App\Models;

use App\Cards\CardOperationLogger;
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
 * @property CarbonImmutable|null $timer_paused_at
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
    'is_timer',
    'timer_paused_at',
    'timer_pause_count',
    'timer_pause_limit',
    'timer_pause_max_seconds',
])]
class Card extends Model
{
    public function agentDeleteEligible(): bool
    {
        if ($this->status === 'revoked') {
            return false;
        }

        $activated = $this->activated_at !== null || in_array($this->status, ['active', 'expired', 'consumed'], true);
        if (! $activated) {
            return true;
        }

        if (in_array($this->status, ['expired', 'consumed'], true) || $this->expires_at?->lte(now())) {
            return false;
        }

        return $this->status === 'active'
            && $this->activated_at !== null
            && $this->activated_at->gte(now()->subDay());
    }

    /** Persist the business change and its per-card audit in the same transaction. */
    public function save(array $options = [])
    {
        if ($this->exists && ! $this->isDirty(CardOperationLogger::FIELDS) && ! CardOperationLogger::isManagementOperation()) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(function () use ($options) {
            $before = $this->exists ? $this->newQuery()->lockForUpdate()->find($this->getKey()) : null;
            $created = ! $this->exists;
            $saved = parent::save($options);
            if ($saved) {
                app(CardOperationLogger::class)->record($before, $this->fresh(), $created ? 'created' : 'updated');
            }

            return $saved;
        });
    }

    public function delete()
    {
        if (! $this->exists) {
            return parent::delete();
        }

        return $this->getConnection()->transaction(function () {
            $before = $this->newQuery()->lockForUpdate()->find($this->getKey());
            $deleted = parent::delete();
            if ($deleted) {
                app(CardOperationLogger::class)->record($before, null, 'deleted');
            }

            return $deleted;
        });
    }

    /** Read-only management status; an unexpired date does not undo revocation. */
    public function softwareUserStatus(): string
    {
        if ($this->status === 'revoked' || $this->user_status === 'revoked' || $this->revoked_at !== null) {
            return 'revoked';
        }
        if ($this->user_status === 'frozen') {
            return 'frozen';
        }
        if ($this->status === 'active' && $this->isTimerPaused()) {
            return 'paused';
        }
        if ($this->status === 'active' && $this->effectiveExpiresAt()?->lte(now())) {
            return 'expired';
        }

        // Freezing the original used card is separate from freezing its software user.
        return (string) $this->status;
    }

    /**
     * 计时卡冻结的自动恢复时间点；未冻结或单次冻结不限时长时为 null。
     */
    public function timerPauseDeadline(): ?CarbonImmutable
    {
        if ($this->timer_paused_at === null || (int) $this->timer_pause_max_seconds <= 0) {
            return null;
        }

        return $this->timer_paused_at->addSeconds((int) $this->timer_pause_max_seconds);
    }

    /** 当前是否处于用户冻结计时状态（已过自动恢复点的视为已恢复）。 */
    public function isTimerPaused(?CarbonImmutable $now = null): bool
    {
        if (! $this->is_timer || $this->timer_paused_at === null) {
            return false;
        }
        $deadline = $this->timerPauseDeadline();

        return $deadline === null || ($now ?? CarbonImmutable::now())->lt($deadline);
    }

    /**
     * 实际生效的到期时间。冻结期间到期时间随冻结时长顺延，
     * 因此返回"如果此刻恢复"的到期时间；已过自动恢复点的按恢复点结算。
     */
    public function effectiveExpiresAt(?CarbonImmutable $now = null): ?CarbonImmutable
    {
        if ($this->expires_at === null || ! $this->is_timer || $this->timer_paused_at === null) {
            return $this->expires_at;
        }
        $now ??= CarbonImmutable::now();
        $deadline = $this->timerPauseDeadline();
        $resumeAt = $deadline !== null && $deadline->lt($now) ? $deadline : $now;
        $pausedSeconds = max(0, $resumeAt->getTimestamp() - $this->timer_paused_at->getTimestamp());

        return $this->expires_at->addSeconds($pausedSeconds);
    }

    /** 剩余可用秒数；冻结期间保持不变。 */
    public function timerRemainingSeconds(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $expiresAt = $this->effectiveExpiresAt($now);

        return $expiresAt === null ? 0 : max(0, $expiresAt->getTimestamp() - $now->getTimestamp());
    }

    /**
     * 结束冻结：把冻结时长顺延到 expires_at 并清除冻结标记。
     * 只修改属性，由调用方在事务内保存。
     */
    public function resumeTimer(?CarbonImmutable $now = null): bool
    {
        if (! $this->is_timer || $this->timer_paused_at === null) {
            return false;
        }
        $this->forceFill([
            'expires_at' => $this->effectiveExpiresAt($now),
            'timer_paused_at' => null,
        ]);

        return true;
    }

    /**
     * 后台调整时长前调用：仍在冻结的卡把冻结起点移到此刻并同步顺延到期时间，
     * 保证"剩余时长"不变，后台在此基础上加减时间或设定到期时间都与未冻结时一致。
     * 只修改属性，由调用方保存。
     */
    public function rebaseTimerPause(?CarbonImmutable $now = null): void
    {
        $now ??= CarbonImmutable::now();
        if ($this->isTimerPaused($now)) {
            $this->forceFill([
                'expires_at' => $this->effectiveExpiresAt($now),
                'timer_paused_at' => $now,
            ]);
        } else {
            $this->resumeTimer($now);
        }
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
            'is_timer' => 'boolean',
            'timer_paused_at' => 'immutable_datetime',
            'timer_pause_count' => 'integer',
            'timer_pause_limit' => 'integer',
            'timer_pause_max_seconds' => 'integer',
        ];
    }
}
