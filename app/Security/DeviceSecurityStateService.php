<?php

namespace App\Security;

use App\Models\Application;
use App\Models\ClientSecuritySubjectState;
use Illuminate\Support\Facades\DB;

final class DeviceSecurityStateService
{
    public function isBlocked(Application $application, string $deviceId): bool
    {
        if (trim($deviceId) === '') {
            return false;
        }
        $state = ClientSecuritySubjectState::query()->where('application_id', $application->getKey())->where('device_hash', $this->hash($deviceId))->first();

        return $state?->status === 'blocked' && ($state->blocked_until === null || $state->blocked_until->isFuture());
    }

    public function block(Application $application, string $deviceId, string $reason): void
    {
        ClientSecuritySubjectState::query()->updateOrCreate(
            ['application_id' => $application->getKey(), 'device_hash' => $this->hash($deviceId)],
            ['risk_score' => 100, 'status' => 'blocked', 'blocked_until' => null, 'last_event_at' => now(), 'reason_code' => $reason],
        );
    }

    public function observe(Application $application, string $deviceId, int $riskScore, string $reason): void
    {
        if (trim($deviceId) === '' || $riskScore <= 0) {
            return;
        }

        DB::transaction(function () use ($application, $deviceId, $riskScore, $reason): void {
            $state = ClientSecuritySubjectState::query()
                ->where('application_id', $application->getKey())
                ->where('device_hash', $this->hash($deviceId))
                ->lockForUpdate()
                ->first();

            if ($state?->status === 'blocked') {
                $state->forceFill(['last_event_at' => now()])->save();

                return;
            }

            $currentScore = $state instanceof ClientSecuritySubjectState ? $state->risk_score : 0;
            $score = min(100, $currentScore + min(50, $riskScore));
            ClientSecuritySubjectState::query()->updateOrCreate(
                ['application_id' => $application->getKey(), 'device_hash' => $this->hash($deviceId)],
                [
                    'risk_score' => $score,
                    'status' => $score >= 50 ? 'quarantined' : 'observe',
                    'blocked_until' => null,
                    'last_event_at' => now(),
                    'reason_code' => $reason,
                ],
            );
        }, 3);
    }

    private function hash(string $deviceId): string
    {
        return hash('sha256', trim($deviceId));
    }
}
