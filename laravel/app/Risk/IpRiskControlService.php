<?php

namespace App\Risk;

use App\Applications\DailyVerificationCodec;
use App\Http\ApiResponse;
use App\Models\Application;
use App\Models\IpRiskState;
use App\Models\RiskEvent;
use App\Support\OperationalMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class IpRiskControlService
{
    public function __construct(
        private readonly DailyVerificationCodec $dailyCodec,
        private readonly OperationalMode $operationalMode,
    ) {}

    public function evaluate(Application $application, Request $request, string $endpoint, string $deviceId = ''): RiskDecision
    {
        $policy = $application->riskPolicy()->firstOrCreate([], $this->defaults());
        $ip = (string) ($request->ip() ?: '0.0.0.0');
        $ipHash = hash('sha256', $ip);

        return DB::transaction(function () use ($application, $policy, $ip, $ipHash, $endpoint, $deviceId): RiskDecision {
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

            $previousLevel = $state->level;

            $activeManualBlock = $state->permanently_blocked
                || ($state->blocked_until !== null && $state->blocked_until->isFuture());

            if (! $policy->enabled && ! $activeManualBlock) {
                $this->touchState($state, $endpoint, $deviceId, false);

                return new RiskDecision('normal', 'allow', (int) $state->window_attempts, $policy, $state);
            }

            $now = now();
            $cutoff = $now->getTimestamp() - $policy->window_seconds;
            $recentAttempts = array_values(array_filter(
                is_array($state->recent_attempt_timestamps) ? $state->recent_attempt_timestamps : [],
                static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp > $cutoff,
            ));
            $recentAttempts[] = $now->getTimestamp();
            $state->recent_attempt_timestamps = $recentAttempts;
            $state->window_attempts = count($recentAttempts);
            $state->window_started_at = $now->copy()->setTimestamp($recentAttempts[0]);
            $state->total_attempts++;
            $this->touchState($state, $endpoint, $deviceId);

            $level = 'normal';
            $action = 'allow';
            $threshold = 0;
            $reason = '窗口内请求正常';

            if ($activeManualBlock || $state->window_attempts >= $policy->high_threshold) {
                $level = 'high';
                $action = 'block';
                $threshold = $policy->high_threshold;
                $reason = $activeManualBlock ? 'IP 位于封禁周期内' : '达到高级封控阈值';
                if (! $state->permanently_blocked && ! $activeManualBlock) {
                    $state->blocked_until = now()->addMinutes($policy->block_duration_minutes);
                }
            } elseif ($state->window_attempts >= $policy->low_threshold) {
                $level = 'low';
                $action = $policy->low_action === 'decoy' && filled($application->verificationData()) ? 'decoy' : 'observe';
                $threshold = $policy->low_threshold;
                $reason = $action === 'decoy' ? '达到低级封控阈值，替换验证数据' : '达到低级封控阈值，仅记录观察';
            }

            $state->level = $level;
            $state->save();

            if ($level !== 'normal' && $level !== $previousLevel) {
                RiskEvent::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'application_id' => $application->getKey(),
                    'ip_risk_state_id' => $state->getKey(),
                    'ip' => $ip,
                    'level' => $level,
                    'action' => $action,
                    'endpoint' => $endpoint,
                    'window_attempts' => $state->window_attempts,
                    'threshold' => $threshold,
                    'device_hash' => trim($deviceId) !== '' ? hash('sha256', trim($deviceId)) : null,
                    'reason' => $reason,
                ]);
            }

            return new RiskDecision($level, $action, $state->window_attempts, $policy, $state);
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
                'SERVER_UNAVAILABLE',
                '服务器暂时故障，请稍后再试。',
                503,
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
            'window_seconds' => 10,
            'low_threshold' => 3,
            'high_threshold' => 5,
            'block_duration_minutes' => 30,
            'low_action' => 'decoy',
            'block_http_status' => 429,
            'block_code' => 'ACCESS_TEMPORARILY_RESTRICTED',
            'block_message' => '当前请求过于频繁，请稍后再试。',
        ];
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
