<?php

namespace App\Security;

use App\Models\Application;
use App\Models\ApplicationClientSession;
use App\Models\ClientSecurityEvent;
use App\Risk\IpRiskControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ClientLaunchTelemetryService
{
    public function __construct(
        private readonly DeviceSecurityStateService $deviceStates,
        private readonly IpRiskControlService $ipRisk,
    ) {}

    public function record(
        Application $application,
        string $handle,
        string $device,
        string $payload,
        string $ip,
    ): bool {
        $settings = $this->settings($application);
        if ($payload === '' || $settings['mode'] === 'disabled') {
            return false;
        }

        $telemetry = $this->normalize(
            $payload,
            $settings['window_minutes'] * 60,
            $settings['high_threshold'],
        );
        $mode = $settings['mode'];

        return DB::transaction(function () use ($application, $handle, $device, $telemetry, $ip, $mode): bool {
            $session = ApplicationClientSession::query()
                ->where('application_id', $application->getKey())
                ->where('token_hash', hash('sha256', $handle))
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $session instanceof ApplicationClientSession
                || trim($device) === ''
                || ! hash_equals((string) $session->device_id_ciphertext, trim($device))) {
                throw new InvalidArgumentException('LAUNCH_TELEMETRY_SESSION_INVALID');
            }

            $existingEvent = ClientSecurityEvent::query()
                ->where('application_id', $application->getKey())
                ->where('event_id', $telemetry['event_id'])
                ->lockForUpdate()
                ->first();
            if ($existingEvent instanceof ClientSecurityEvent
                && $telemetry['protocol_version'] < 3) {
                return false;
            }

            if ($existingEvent instanceof ClientSecurityEvent) {
                $previousRevision = (int) data_get($existingEvent->evidence, 'revision', 0);
                if ((int) ($telemetry['revision'] ?? 0) <= $previousRevision) {
                    return false;
                }
            }

            $telemetry = $this->applyPardonBaseline(
                $telemetry,
                $existingEvent instanceof ClientSecurityEvent ? ($existingEvent->evidence ?? []) : [],
            );

            $enforce = $mode === 'enforce' && $telemetry['severe'];
            $action = $enforce ? 'freeze' : 'observe';

            $eventValues = [
                'application_id' => $application->getKey(),
                'application_client_session_id' => $session->getKey(),
                'card_id' => $session->card_id,
                'application_account_id' => $session->application_account_id,
                'event_id' => $telemetry['event_id'],
                'event_code' => 'T1',
                'severity' => $telemetry['severity'],
                'ip_hash' => hash('sha256', $ip),
                'device_hash' => hash('sha256', trim($device)),
                'evidence' => $telemetry['evidence'],
                'action' => $action,
                'created_at' => now(),
            ];
            if ($existingEvent instanceof ClientSecurityEvent) {
                $existingEvent->forceFill($eventValues)->save();
            } else {
                ClientSecurityEvent::query()->create([
                    'public_id' => (string) Str::ulid(),
                    ...$eventValues,
                ]);
            }

            if ($enforce) {
                $this->deviceStates->block($application, $device, 'T1');
                $this->ipRisk->blockForClientSecurityEvent(
                    $application,
                    $ip,
                    $device,
                    'client.launch.telemetry',
                    'T1',
                );
                $session->forceFill(['status' => 'security_blocked'])->save();
                if ($session->card_id) {
                    DB::table('cards')->where('id', $session->card_id)->update([
                        'user_status' => 'frozen', 'user_frozen_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if ($session->application_account_id) {
                    DB::table('application_accounts')->where('id', $session->application_account_id)->update([
                        'status' => 'frozen', 'updated_at' => now(),
                    ]);
                }

                return true;
            }

            $this->deviceStates->observe($application, $device, $telemetry['risk_score'], 'T1');

            return false;
        }, 3);
    }

    /**
     * @return array{event_id: string, protocol_version: int, revision?: int, severity: string, risk_score: int, severe: bool, evidence: array<string, mixed>}
     */
    private function normalize(string $payload, int $policyWindowSeconds, int $highThreshold): array
    {
        if (strlen($payload) > 1024) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }

        if (($data['v'] ?? null) === 2) {
            return $this->normalizeSummary($data, $policyWindowSeconds, $highThreshold);
        }

        if (($data['v'] ?? null) === 3) {
            return $this->normalizePermanentLedger($data, $policyWindowSeconds, $highThreshold);
        }

        if (($data['v'] ?? null) !== 1
            || ! is_string($data['id'] ?? null)
            || ! preg_match('/^[a-f0-9]{32}$/', $data['id'])
            || ! is_array($data['times'] ?? null)
            || ! is_array($data['intervals'] ?? null)
            || ! is_array($data['uptimes'] ?? null)) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }

        $count = filter_var($data['count'] ?? null, FILTER_VALIDATE_INT);
        if ($count === false || $count < 3 || $count > 20
            || count($data['times']) !== $count
            || count($data['uptimes']) !== $count
            || count($data['intervals']) !== max(0, $count - 1)) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }

        $declaredWindow = filter_var($data['window'] ?? null, FILTER_VALIDATE_INT);
        if ($declaredWindow === false || $declaredWindow < 900 || $declaredWindow > 3600) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }

        $now = now()->timestamp;
        $times = $this->boundedIntegers($data['times'], $now - $declaredWindow, $now + 60);
        $intervals = $this->boundedIntegers($data['intervals'], 0, 86400);
        $uptimes = $this->boundedIntegers($data['uptimes'], 0, 86400);
        if (count($times) !== $count || count($uptimes) !== $count || count($intervals) !== max(0, $count - 1)) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }
        for ($index = 1; $index < count($times); $index++) {
            if ($times[$index] < $times[$index - 1]
                || $intervals[$index - 1] !== $times[$index] - $times[$index - 1]) {
                throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
            }
        }

        $effectiveWindow = min($declaredWindow, $policyWindowSeconds);
        $startIndex = 0;
        while (isset($times[$startIndex]) && $times[$startIndex] < $now - $effectiveWindow) {
            $startIndex++;
        }
        if ($startIndex > 0) {
            $times = array_slice($times, $startIndex);
            $uptimes = array_slice($uptimes, $startIndex);
            $intervals = [];
            for ($index = 1; $index < count($times); $index++) {
                $intervals[] = $times[$index] - $times[$index - 1];
            }
            $count = count($times);
        }
        if ($count < 3) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_BELOW_REPORT_THRESHOLD');
        }

        $rapidIntervals = count(array_filter($intervals, fn (int $seconds): bool => $seconds <= 45));
        $shortRuns = count(array_filter($uptimes, fn (int $seconds): bool => $seconds <= 20));
        $span = end($times) - $times[0];
        $burst = $span <= 300 && ($rapidIntervals >= 2 || $shortRuns >= 3);
        $severe = $count >= $highThreshold;
        $severity = $severe ? 'high' : ($burst && $count >= 5 ? 'medium' : 'low');
        $riskScore = $severe ? 100 : ($burst ? min(35, 8 + ($count * 3)) : min(12, $count * 2));

        return [
            'event_id' => $data['id'],
            'protocol_version' => 1,
            'severity' => $severity,
            'risk_score' => $riskScore,
            'severe' => $severe,
            'evidence' => [
                'count' => $count,
                'times' => $times,
                'intervals' => $intervals,
                'uptimes' => $uptimes,
                'window_seconds' => $effectiveWindow,
                'high_threshold' => $highThreshold,
                'span_seconds' => $span,
                'rapid_intervals' => $rapidIntervals,
                'short_runs' => $shortRuns,
                'burst' => $burst,
                'severe' => $severe,
            ],
        ];
    }

    /**
     * v2 只接收聚合摘要，不接收逐次退出时间、间隔与运行时长。
     *
     * @param  array<string, mixed>  $data
     * @return array{event_id: string, protocol_version: int, severity: string, risk_score: int, severe: bool, evidence: array<string, mixed>}
     */
    private function normalizeSummary(array $data, int $policyWindowSeconds, int $highThreshold): array
    {
        if (! is_string($data['i'] ?? null) || ! preg_match('/^[a-f0-9]{32}$/', $data['i'])) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }

        $count = $this->boundedInteger($data['n'] ?? null, 3, 20);
        $span = $this->boundedInteger($data['s'] ?? null, 0, 3600);
        $rapid = $this->boundedInteger($data['r'] ?? null, 0, max(0, $count - 1));
        $short = $this->boundedInteger($data['u'] ?? null, 0, $count);
        $declaredWindow = $this->boundedInteger($data['w'] ?? null, 900, 3600);
        $effectiveWindow = min($declaredWindow, $policyWindowSeconds);

        // 摘要覆盖范围超过服务端策略窗口时不执行处置，避免扩大客户端计数口径。
        if ($span > $effectiveWindow) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_BELOW_REPORT_THRESHOLD');
        }

        $burst = $span <= 300 && ($rapid >= 2 || $short >= 3);
        $severe = $count >= $highThreshold;
        $severity = $severe ? 'high' : ($burst && $count >= 5 ? 'medium' : 'low');
        $riskScore = $severe ? 100 : ($burst ? min(35, 8 + ($count * 3)) : min(12, $count * 2));

        return [
            'event_id' => $data['i'],
            'protocol_version' => 2,
            'severity' => $severity,
            'risk_score' => $riskScore,
            'severe' => $severe,
            'evidence' => [
                'version' => 2,
                'count' => $count,
                'window_seconds' => $effectiveWindow,
                'high_threshold' => $highThreshold,
                'span_seconds' => $span,
                'rapid_intervals' => $rapid,
                'short_runs' => $short,
                'burst' => $burst,
                'severe' => $severe,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{event_id: string, protocol_version: int, revision: int, severity: string, risk_score: int, severe: bool, evidence: array<string, mixed>}
     */
    private function normalizePermanentLedger(array $data, int $policyWindowSeconds, int $highThreshold): array
    {
        if (! is_string($data['i'] ?? null) || ! preg_match('/^[a-f0-9]{32}$/', $data['i'])) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }

        $revision = $this->boundedInteger($data['q'] ?? null, 3, 1_000_000);
        $count = $this->boundedInteger($data['c'] ?? null, 3, 1_000_000);
        if ($revision < $count) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }
        $recent = $this->boundedInteger($data['n'] ?? null, 0, min(20, $count));
        $pendingBurst = $this->boundedInteger($data['m'] ?? null, 0, min(20, $count));
        $shortRuns = $this->boundedInteger($data['u'] ?? null, 0, $count);
        $rapid = $this->boundedInteger($data['r'] ?? null, 0, max(0, $recent - 1));
        $span = $this->boundedInteger($data['s'] ?? null, 0, 315_360_000);
        $declaredWindow = $this->boundedInteger($data['w'] ?? null, 900, 3600);
        $effectiveWindow = min($declaredWindow, $policyWindowSeconds);
        $burstCount = max($recent, $pendingBurst);
        $persistentThreshold = max(20, $highThreshold * 2);
        $persistentSevere = $count >= $persistentThreshold && ($shortRuns * 100) >= ($count * 70);
        $severe = $burstCount >= $highThreshold || $persistentSevere;
        $burst = $recent >= 5 && ($rapid >= 2 || $shortRuns >= 3);
        $severity = $severe ? 'high' : ($burst ? 'medium' : 'low');
        $riskScore = $severe ? 100 : ($burst ? min(35, 8 + ($recent * 3)) : min(20, max(2, $count)));

        return [
            'event_id' => $data['i'],
            'protocol_version' => 3,
            'revision' => $revision,
            'severity' => $severity,
            'risk_score' => $riskScore,
            'severe' => $severe,
            'evidence' => [
                'version' => 3,
                'revision' => $revision,
                'count' => $count,
                'recent_count' => $recent,
                'pending_burst' => $pendingBurst,
                'short_runs' => $shortRuns,
                'rapid_intervals' => $rapid,
                'span_seconds' => $span,
                'window_seconds' => $effectiveWindow,
                'high_threshold' => $highThreshold,
                'persistent_threshold' => $persistentThreshold,
                'burst' => $burst,
                'persistent_severe' => $persistentSevere,
                'severe' => $severe,
            ],
        ];
    }

    /**
     * 人工解除设备封控后，历史永久总账继续保留，但只用基线之后新增的数据重新判定。
     *
     * @param  array{event_id: string, protocol_version: int, revision?: int, severity: string, risk_score: int, severe: bool, evidence: array<string, mixed>}  $telemetry
     * @param  array<string, mixed>  $previousEvidence
     * @return array{event_id: string, protocol_version: int, revision?: int, severity: string, risk_score: int, severe: bool, evidence: array<string, mixed>}
     */
    private function applyPardonBaseline(array $telemetry, array $previousEvidence): array
    {
        if ($telemetry['protocol_version'] !== 3) {
            return $telemetry;
        }

        $baselineRevision = (int) ($previousEvidence['pardon_revision'] ?? 0);
        $baselineCount = (int) ($previousEvidence['pardon_count'] ?? 0);
        $baselineShortRuns = (int) ($previousEvidence['pardon_short_runs'] ?? 0);
        $evidence = $telemetry['evidence'];
        $count = (int) ($evidence['count'] ?? 0);
        $shortRuns = (int) ($evidence['short_runs'] ?? 0);
        $deltaCount = max(0, $count - $baselineCount);
        $deltaShortRuns = max(0, $shortRuns - $baselineShortRuns);
        $burstCount = max((int) ($evidence['recent_count'] ?? 0), (int) ($evidence['pending_burst'] ?? 0));
        if ($baselineRevision > 0) {
            $burstCount = min($burstCount, $deltaCount);
        }
        $highThreshold = (int) ($evidence['high_threshold'] ?? 8);
        $persistentThreshold = (int) ($evidence['persistent_threshold'] ?? max(20, $highThreshold * 2));
        $persistentSevere = $deltaCount >= $persistentThreshold
            && ($deltaShortRuns * 100) >= ($deltaCount * 70);
        $severe = $burstCount >= $highThreshold || $persistentSevere;
        $burst = $burstCount >= 5 && ((int) ($evidence['rapid_intervals'] ?? 0) >= 2 || $deltaShortRuns >= 3);

        $telemetry['severe'] = $severe;
        $telemetry['severity'] = $severe ? 'high' : ($burst ? 'medium' : 'low');
        $telemetry['risk_score'] = $severe ? 100 : ($burst ? min(35, 8 + ($burstCount * 3)) : min(20, max(2, $deltaCount)));
        $telemetry['evidence'] = [
            ...$evidence,
            'pardon_revision' => $baselineRevision,
            'pardon_count' => $baselineCount,
            'pardon_short_runs' => $baselineShortRuns,
            'effective_count' => $deltaCount,
            'effective_short_runs' => $deltaShortRuns,
            'effective_burst' => $burstCount,
            'persistent_severe' => $persistentSevere,
            'severe' => $severe,
        ];

        return $telemetry;
    }

    private function boundedInteger(mixed $value, int $minimum, int $maximum): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false || $integer < $minimum || $integer > $maximum) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }

        return $integer;
    }

    /**
     * @param  array<mixed>  $values
     * @return list<int>
     */
    private function boundedIntegers(array $values, int $minimum, int $maximum): array
    {
        $normalized = [];
        foreach ($values as $value) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);
            if ($integer === false || $integer < $minimum || $integer > $maximum) {
                return [];
            }
            $normalized[] = $integer;
        }

        return $normalized;
    }

    /** @return array{mode: string, window_minutes: int, high_threshold: int} */
    private function settings(Application $application): array
    {
        $policy = $application->riskPolicy()->first();
        $mode = (string) ($policy->security_report_mode ?? 'observe');

        return [
            'mode' => in_array($mode, ['disabled', 'observe', 'enforce'], true) ? $mode : 'observe',
            'window_minutes' => min(60, max(15, (int) ($policy->abnormal_exit_window_minutes ?? 60))),
            'high_threshold' => min(20, max(3, (int) ($policy->abnormal_exit_high_threshold ?? 8))),
        ];
    }
}
