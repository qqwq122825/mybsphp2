<?php

namespace App\Risk;

use App\Applications\DailyVerificationCodec;
use App\Http\ApiResponse;
use App\Models\Application;
use App\Models\ClientSecuritySubjectState;
use App\Models\GlobalSecurityBlock;
use App\Models\IpRiskState;
use App\Models\RiskEvent;
use App\Models\RiskPolicy;
use App\Security\DeviceSecurityStateService;
use App\Support\OperationalMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class IpRiskControlService
{
    /** @var list<string> */
    public const CARD_PROBE_CODES = [
        'INVALID_CARD',
        'EMPTY_CARD',
        'INVALID_PARAMETERS',
        'CARD_EXPIRED',
        'DEVICE_MISMATCH',
        'CARD_FROZEN',
        'USER_FROZEN',
        'CARD_UNAVAILABLE',
        'DEVICE_BLOCKED',
        'IP_BLOCKED',
    ];

    /** @var array<string, string> */
    public const OPTIONAL_PROBE_CODES = [
        'DEVICE_REQUIRED' => 'probe_count_missing_device',
        'UNKNOWN_API' => 'probe_count_unknown_api',
    ];

    public function __construct(
        private readonly DailyVerificationCodec $dailyCodec,
        private readonly OperationalMode $operationalMode,
        private readonly DeviceSecurityStateService $deviceStates,
    ) {}

    public function evaluate(Application $application, Request $request, string $endpoint, string $deviceId = '', string $subjectProof = '', bool $restartEligible = false): RiskDecision
    {
        $policy = $application->riskPolicy()->firstOrCreate([], $this->defaults());
        $ip = (string) ($request->ip() ?: '0.0.0.0');
        $ipHash = hash('sha256', $ip);

        return DB::transaction(function () use ($application, $policy, $ip, $ipHash, $endpoint, $deviceId): RiskDecision {
            $state = $this->lockIpState($application, $ip, $ipHash);
            $globalIpBlocked = GlobalSecurityBlock::ipBlocked($ip);
            $activeBlock = $state->permanently_blocked
                || ($state->blocked_until !== null && $state->blocked_until->isFuture())
                || $globalIpBlocked;

            $this->touchState($state, $endpoint, $deviceId, false);

            if ($activeBlock) {
                $state->level = 'high';
                if ($globalIpBlocked) {
                    $state->permanently_blocked = true;
                    $state->blocked_until = null;
                    $state->note = $state->note ?: '命中全软件 IP 黑名单';
                }
                $state->save();

                return new RiskDecision('high', 'block', (int) $state->window_attempts, $policy, $state);
            }

            if ($state->level !== 'normal' && $state->level !== 'high') {
                $state->level = 'normal';
            }
            $state->save();

            return new RiskDecision((string) $state->level, 'allow', (int) $state->window_attempts, $policy, $state);
        }, 3);
    }

    public function recordAuthFailureProbe(
        Application $application,
        Request $request,
        string $deviceId,
        string $errorCode,
        string $endpoint = 'legacy.login.ic',
    ): void {
        $policy = $application->riskPolicy()->firstOrCreate([], $this->defaults());
        if (! $policy->enabled || ! $this->countsTowardProbe($errorCode, $policy)) {
            return;
        }

        $ip = (string) ($request->ip() ?: '0.0.0.0');
        $ipHash = hash('sha256', $ip);
        $normalizedDevice = trim($deviceId);
        $window = max(10, (int) ($policy->invalid_card_window_seconds ?: 60));
        $threshold = max(2, (int) ($policy->invalid_card_threshold ?: 3));
        $now = now()->getTimestamp();
        $cutoff = $now - $window;

        DB::transaction(function () use ($application, $policy, $ip, $ipHash, $normalizedDevice, $endpoint, $errorCode, $threshold, $now, $cutoff): void {
            $state = $this->lockIpState($application, $ip, $ipHash);
            $ipAlreadyBlocked = $this->ipIsBlocked($state, $ip);
            $deviceAlreadyBlocked = $normalizedDevice !== '' && $this->deviceStates->isBlocked($application, $normalizedDevice);
            $ipProbes = $this->pruneProbes($state->recent_card_probes, $cutoff);
            $ipProbes[] = $now;
            $state->recent_card_probes = $ipProbes;
            $state->window_attempts = count($ipProbes);
            $state->window_started_at = now()->copy()->setTimestamp($ipProbes[0]);
            $state->total_attempts++;
            $this->touchState($state, $endpoint, $normalizedDevice, false);

            $deviceProbes = 0;
            if ($normalizedDevice !== '') {
                $deviceState = ClientSecuritySubjectState::query()
                    ->where('application_id', $application->getKey())
                    ->where('device_hash', hash('sha256', $normalizedDevice))
                    ->lockForUpdate()
                    ->first();
                if (! $deviceState instanceof ClientSecuritySubjectState) {
                    $deviceState = ClientSecuritySubjectState::query()->create([
                        'application_id' => $application->getKey(),
                        'device_hash' => hash('sha256', $normalizedDevice),
                        'risk_score' => 0,
                        'status' => 'observe',
                        'last_event_at' => now(),
                        'reason_code' => 'C0',
                        'recent_card_probes' => [],
                    ]);
                }
                $deviceProbeList = $this->pruneProbes($deviceState->recent_card_probes, $cutoff);
                $deviceProbes = count($deviceProbeList) + 1;
                $deviceProbeList[] = $now;
                $deviceState->forceFill([
                    'recent_card_probes' => $deviceProbeList,
                    'last_event_at' => now(),
                    'reason_code' => $deviceState->status === 'blocked' ? $deviceState->reason_code : 'C0',
                ])->save();
            }

            $ipHit = count($ipProbes) >= $threshold;
            $deviceHit = $deviceProbes >= $threshold;
            $shouldBlockIp = ! $ipAlreadyBlocked && ($ipHit || ($deviceHit && ! $deviceAlreadyBlocked));
            $shouldBlockDevice = $normalizedDevice !== '' && ! $deviceAlreadyBlocked
                && ($deviceHit || ($ipHit && ! $ipAlreadyBlocked));
            if (! $shouldBlockIp && ! $shouldBlockDevice) {
                $state->save();

                return;
            }

            if ($shouldBlockIp) {
                $state->forceFill([
                    'level' => 'high',
                    'permanently_blocked' => true,
                    'blocked_until' => null,
                    'last_attempt_succeeded' => false,
                    'note' => '套卡探测 '.$errorCode.' 达到 '.$threshold.' 次 / '.$policy->invalid_card_window_seconds.' 秒',
                ]);
                GlobalSecurityBlock::rememberIp($ip, 'C0', 'IP 已拉黑，全软件登录显示卡密不存在');
            }
            $state->save();

            if ($shouldBlockDevice) {
                $this->deviceStates->block(
                    $application,
                    $normalizedDevice,
                    'C0',
                    '套卡探测达到阈值，已拉黑设备。后续任意软件登录显示卡密不存在。',
                );
            }

            $blockedParts = array_values(array_filter([
                $shouldBlockDevice ? '设备' : null,
                $shouldBlockIp ? 'IP' : null,
            ]));
            RiskEvent::query()->create([
                'public_id' => (string) Str::ulid(),
                'application_id' => $application->getKey(),
                'ip_risk_state_id' => $state->getKey(),
                'ip' => $ip,
                'level' => 'high',
                'action' => 'block',
                'endpoint' => $endpoint,
                'window_attempts' => count($ipProbes),
                'threshold' => $threshold,
                'device_hash' => $normalizedDevice !== '' ? hash('sha256', $normalizedDevice) : null,
                'reason' => '套卡探测命中：'.$errorCode.'，已拉黑当前'.implode('与', $blockedParts),
            ]);
        }, 3);
    }

    public function complete(RiskDecision $decision, bool $succeeded): void
    {
        $decision->state->forceFill(['last_attempt_succeeded' => $succeeded])->save();
    }

    public function blockForClientSecurityEvent(
        Application $application,
        string $ip,
        string $deviceId,
        string $endpoint,
        string $reasonCode,
    ): IpRiskState {
        $ip = trim($ip) !== '' ? trim($ip) : '0.0.0.0';
        $ipHash = hash('sha256', $ip);
        $deviceHash = trim($deviceId) !== '' ? hash('sha256', trim($deviceId)) : null;
        $policy = $application->riskPolicy()->firstOrCreate([], $this->defaults());

        return DB::transaction(function () use ($application, $ip, $ipHash, $deviceId, $deviceHash, $endpoint, $reasonCode, $policy): IpRiskState {
            $state = IpRiskState::query()
                ->where('application_id', $application->getKey())
                ->where('ip_hash', $ipHash)
                ->lockForUpdate()
                ->first();

            if (! $state instanceof IpRiskState) {
                $state = IpRiskState::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'application_id' => $application->getKey(),
                    'ip' => $ip,
                    'ip_hash' => $ipHash,
                    'window_started_at' => now(),
                ]);
            }

            $alreadyBlocked = $state->permanently_blocked === true;
            $state->forceFill([
                'ip' => $ip,
                'level' => 'high',
                'blocked_until' => null,
                'permanently_blocked' => true,
                'last_device_hash' => $deviceHash,
                'last_endpoint' => $endpoint,
                'last_attempt_succeeded' => false,
                'last_seen_at' => now(),
                'note' => '客户端安全事件 '.$reasonCode.' 同步拉黑 IP',
            ])->save();
            GlobalSecurityBlock::rememberIp($ip, $reasonCode, 'IP 已拉黑，全软件登录显示卡密不存在');
            if (trim($deviceId) !== '') {
                $this->deviceStates->block($application, $deviceId, $reasonCode);
            }

            if (! $alreadyBlocked) {
                RiskEvent::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'application_id' => $application->getKey(),
                    'ip_risk_state_id' => $state->getKey(),
                    'ip' => $ip,
                    'level' => 'high',
                    'action' => 'block',
                    'endpoint' => $endpoint,
                    'window_attempts' => (int) $state->window_attempts,
                    'threshold' => (int) $policy->high_threshold,
                    'device_hash' => $deviceHash,
                    'reason' => '客户端安全事件 '.$reasonCode.' 同步拉黑设备与 IP',
                ]);
            }

            return $state;
        }, 3);
    }

    /** @param array<string, mixed> $result @return array<string, mixed> */
    public function applyDecoy(array $result, Application $application, RiskDecision $decision): array
    {
        if ($decision->shouldUseDecoy() && filled($application->verificationData())) {
            $decoy = filled($decision->policy->decoy_value)
                ? (string) $decision->policy->decoy_value
                : 'RISK-'.strtoupper(Str::random(24));
            $encrypted = $this->dailyCodec->encodePayload($decoy);
            $result['payload'] = $decoy;
            $result['encrypted_payload'] = $encrypted;

            if (isset($result['legacy_response']) && is_string($result['legacy_response'])) {
                $parts = explode('|', $result['legacy_response']);
                $payloadIndex = isset($result['account']) ? 3 : 2;
                if (array_key_exists($payloadIndex, $parts)) {
                    $parts[$payloadIndex] = $encrypted;
                    $result['legacy_response'] = implode('|', $parts);
                }
            }
        }

        if ($decision->level === 'low' && $this->operationalMode->isDebug()) {
            $result['risk_control'] = [
                'level' => 'low',
                'action' => $decision->action,
                'window_attempts' => $decision->attempts,
                'window_seconds' => $decision->policy->window_seconds,
                'low_threshold' => $decision->policy->low_threshold,
            ];
        }

        return $result;
    }

    public function blockedResponse(RiskDecision $decision, Request $request): JsonResponse
    {
        if (! $this->operationalMode->isDebug()) {
            return ApiResponse::error(
                'INVALID_CARD',
                '卡密不存在',
                200,
                request: $request,
            );
        }

        $retryAfter = $decision->retryAfterSeconds();
        $response = ApiResponse::error(
            $decision->policy->block_code,
            $decision->policy->block_message,
            $decision->policy->block_http_status,
            [
                'control_level' => 'high',
                'window_attempts' => $decision->attempts,
                'window_seconds' => $decision->policy->window_seconds,
                'high_threshold' => $decision->policy->high_threshold,
                'retry_after_seconds' => $retryAfter,
            ],
            request: $request,
        );

        return $retryAfter === null ? $response : $response->header('Retry-After', (string) $retryAfter);
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'enabled' => true,
            'window_seconds' => 60,
            'low_threshold' => 6,
            'high_threshold' => 12,
            'block_duration_minutes' => 30,
            'low_action' => 'observe',
            'block_http_status' => 429,
            'block_code' => 'ACCESS_TEMPORARILY_RESTRICTED',
            'block_message' => '当前请求过于频繁，请稍后再试。',
            'restart_grace_seconds' => 8,
            'security_report_mode' => 'observe',
            'abnormal_exit_window_minutes' => 60,
            'abnormal_exit_high_threshold' => 8,
            'invalid_card_threshold' => 3,
            'invalid_card_window_seconds' => 60,
            'probe_count_missing_device' => true,
            'probe_count_unknown_api' => true,
        ];
    }

    private function countsTowardProbe(string $errorCode, RiskPolicy $policy): bool
    {
        if (in_array($errorCode, self::CARD_PROBE_CODES, true)) {
            return true;
        }
        $column = self::OPTIONAL_PROBE_CODES[$errorCode] ?? null;

        return $column !== null && (bool) $policy->{$column};
    }

    private function ipIsBlocked(IpRiskState $state, string $ip): bool
    {
        return $state->permanently_blocked
            || ($state->blocked_until !== null && $state->blocked_until->isFuture())
            || GlobalSecurityBlock::ipBlocked($ip);
    }

    private function lockIpState(Application $application, string $ip, string $ipHash): IpRiskState
    {
        $state = IpRiskState::query()
            ->where('application_id', $application->getKey())
            ->where('ip_hash', $ipHash)
            ->lockForUpdate()
            ->first();

        if ($state instanceof IpRiskState) {
            return $state;
        }

        return IpRiskState::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'ip' => $ip,
            'ip_hash' => $ipHash,
            'window_started_at' => now(),
            'recent_card_probes' => [],
        ]);
    }

    /** @param mixed $probes @return list<int> */
    private function pruneProbes(mixed $probes, int $cutoff): array
    {
        return array_values(array_filter(
            is_array($probes) ? $probes : [],
            static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp > $cutoff,
        ));
    }

    private function touchState(IpRiskState $state, string $endpoint, string $deviceId, bool $save = true): void
    {
        $state->last_seen_at = now();
        $state->last_endpoint = $endpoint;
        $state->last_device_hash = trim($deviceId) !== '' ? hash('sha256', trim($deviceId)) : $state->last_device_hash;
        if ($save) {
            $state->save();
        }
    }
}
