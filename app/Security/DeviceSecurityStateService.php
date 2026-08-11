<?php

namespace App\Security;

use App\Models\Application;
use App\Models\ClientSecuritySubjectState;

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

    private function hash(string $deviceId): string
    {
        return hash('sha256', trim($deviceId));
    }
}
