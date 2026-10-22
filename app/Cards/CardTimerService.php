<?php

namespace App\Cards;

use App\Models\Application;
use App\Models\Card;
use App\Security\DeviceSecurityStateService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 计时卡的客户端冻结 / 恢复。
 *
 * 只对制卡时勾选了"计时卡"、且已激活的时长卡生效。冻结期间不再消耗时长，
 * 恢复时把冻结时长顺延到到期时间。冻结不影响在线会话与心跳，
 * 代理联动在下一次同步时以 status=paused 下发并停止代理访问。
 */
final class CardTimerService
{
    /** 这些错误码对客户端明文返回，便于提示用户；其余卡密错误仍走统一伪装。 */
    public const VISIBLE_ERRORS = [
        'TIMER_NOT_SUPPORTED',
        'TIMER_NOT_ACTIVATED',
        'TIMER_ALREADY_PAUSED',
        'TIMER_PAUSE_LIMIT',
    ];

    public function __construct(
        private readonly CardCredentialHasher $hasher,
        private readonly DeviceSecurityStateService $deviceStates,
    ) {}

    /** @return array<string, mixed> */
    public function pause(Application $application, string $code, string $secret, string $deviceId): array
    {
        return DB::transaction(function () use ($application, $code, $secret, $deviceId): array {
            $now = CarbonImmutable::now();
            $card = $this->authorizedCard($application, $code, $secret, $deviceId, $now);

            if ($card->isTimerPaused($now)) {
                throw new CardActivationException('TIMER_ALREADY_PAUSED', '该卡已处于冻结状态。', 409);
            }
            // 已过自动恢复点但尚未结算的旧冻结，先结算再开始新的冻结。
            $card->resumeTimer($now);

            $limit = (int) $card->timer_pause_limit;
            if ($limit !== -1 && (int) $card->timer_pause_count >= $limit) {
                throw new CardActivationException('TIMER_PAUSE_LIMIT', '该卡的冻结次数已用完。', 409);
            }

            $card->forceFill([
                'timer_paused_at' => $now,
                'timer_pause_count' => (int) $card->timer_pause_count + 1,
            ])->save();

            return $this->result($card->refresh(), $now);
        }, 3);
    }

    /** @return array<string, mixed> */
    public function resume(Application $application, string $code, string $secret, string $deviceId): array
    {
        return DB::transaction(function () use ($application, $code, $secret, $deviceId): array {
            $now = CarbonImmutable::now();
            $card = $this->authorizedCard($application, $code, $secret, $deviceId, $now);

            // 未冻结时恢复是幂等的，直接返回当前状态。
            if ($card->resumeTimer($now)) {
                $card->save();
                $card->refresh();
            }

            return $this->result($card, $now);
        }, 3);
    }

    /** 只读查询，不会恢复计时。 @return array<string, mixed> */
    public function status(Application $application, string $code, string $secret, string $deviceId): array
    {
        $now = CarbonImmutable::now();

        return $this->result($this->authorizedCard($application, $code, $secret, $deviceId, $now, false), $now);
    }

    private function authorizedCard(
        Application $application,
        string $code,
        string $secret,
        string $deviceId,
        CarbonImmutable $now,
        bool $lock = true,
    ): Card {
        if ($application->status !== 'active') {
            throw new CardActivationException('APP_DISABLED', $application->closedMessage(), 403);
        }
        $device = trim($deviceId);
        if ($device === '') {
            throw new CardActivationException('DEVICE_REQUIRED', '当前应用要求提供设备标识。');
        }
        if ($this->deviceStates->isBlocked($application, $device)) {
            throw new CardActivationException('DEVICE_BLOCKED', '设备已进入安全封控。', 403);
        }

        $query = Card::query()
            ->with(['application', 'cardType'])
            ->where('application_id', $application->getKey())
            ->where('code_lookup_hash', $this->hasher->lookupHash($code));
        $card = ($lock ? $query->lockForUpdate() : $query)->first();

        if (! $card instanceof Card) {
            throw new CardActivationException('INVALID_CARD', '卡密不存在。', 404);
        }
        if (
            (int) $card->cardType->secret_length > 0
            && (! is_string($card->secret_hash) || ! Hash::check($secret, $card->secret_hash))
        ) {
            throw new CardActivationException('INVALID_CARD', '卡密不存在。', 404);
        }
        if ($card->status === 'revoked' || $card->revoked_at !== null || $card->user_status === 'revoked') {
            throw new CardActivationException('CARD_UNAVAILABLE', '该卡密当前状态不可用。');
        }
        if ($card->card_is_frozen || $card->status === 'frozen') {
            throw new CardActivationException('CARD_FROZEN', '你选择卡串已冻结！');
        }
        if ($card->user_status === 'frozen') {
            throw new CardActivationException('USER_FROZEN', '该软件用户已冻结。', 403);
        }
        if (! $card->is_timer || $card->cardType->benefit_kind !== 'duration') {
            throw new CardActivationException('TIMER_NOT_SUPPORTED', '该卡不是计时卡，不支持冻结。', 409);
        }
        if ($card->status !== 'active' || $card->activated_at === null || $card->expires_at === null) {
            throw new CardActivationException('TIMER_NOT_ACTIVATED', '该卡尚未激活，请先登录激活。', 409);
        }

        $bound = trim((string) ($card->bound_device_ciphertext ?? ''));
        if ($bound !== '' && $this->machineBindingLimit($card) !== -1 && ! hash_equals($bound, $device)) {
            throw new CardActivationException('DEVICE_MISMATCH', '该卡密已被绑定，当前设备特征不匹配。', 409);
        }

        if (! $card->isTimerPaused($now) && $card->effectiveExpiresAt($now)?->lte($now)) {
            throw new CardActivationException('CARD_EXPIRED', '该卡密已经到期。', 403);
        }

        return $card;
    }

    /** @return array<string, mixed> */
    private function result(Card $card, CarbonImmutable $now): array
    {
        $paused = $card->isTimerPaused($now);
        $state = $paused ? 'paused' : 'active';
        $remaining = $card->timerRemainingSeconds($now);
        $expiresAt = $card->effectiveExpiresAt($now)?->toIso8601String() ?? '';
        $limit = (int) $card->timer_pause_limit;
        $count = (int) $card->timer_pause_count;
        $autoResumeAt = $paused ? ($card->timerPauseDeadline()?->toIso8601String() ?? '') : '';

        // 01|状态|剩余秒数|到期时间|已冻结次数|次数上限(-1不限)|自动恢复时间|服务器时间
        $legacy = implode('|', [
            '01', $state, (string) $remaining, $expiresAt, (string) $count, (string) $limit,
            $autoResumeAt, $now->toIso8601String(),
        ]);

        return [
            'timer' => [
                'state' => $state,
                'remaining_seconds' => $remaining,
                'expires_at' => $expiresAt,
                'pause_count' => $count,
                'pause_limit' => $limit,
                'auto_resume_at' => $autoResumeAt,
            ],
            'legacy_response' => $legacy,
        ];
    }

    private function machineBindingLimit(Card $card): int
    {
        $cardLimit = (int) ($card->machine_concurrency_limit ?? 0);
        $typeLimit = (int) ($card->cardType->machine_concurrency_limit ?? 0);

        return $cardLimit !== 0
            ? $cardLimit
            : ($typeLimit !== 0 ? $typeLimit : (int) $card->application->max_bound_devices);
    }
}
