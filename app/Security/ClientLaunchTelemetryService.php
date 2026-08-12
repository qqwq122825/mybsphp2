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

            if (ClientSecurityEvent::query()
                ->where('application_id', $application->getKey())
                ->where('event_id', $telemetry['event_id'])
                ->exists()) {
                return false;
            }

            $enforce = $mode === 'enforce' && $telemetry['severe'];
            $action = $enforce ? 'freeze' : 'observe';

            ClientSecurityEvent::query()->create([
                'public_id' => (string) Str::ulid(),
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
            ]);

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
     * @return array{event_id: string, severity: string, risk_score: int, severe: bool, evidence: array<string, mixed>}
     */
    private function normalize(string $payload, int $policyWindowSeconds, int $highThreshold): array
    {
        if (strlen($payload) > 1024) {
            throw new InvalidArgumentException('LAUNCH_TELEMETRY_INVALID');
        }

        $data = json_decode($payload, true);
        if (! is_array($data)
            || ($data['v'] ?? null) !== 1
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
