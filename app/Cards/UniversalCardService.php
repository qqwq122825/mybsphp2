<?php

namespace App\Cards;

use App\Applications\DailyVerificationCodec;
use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Models\Application;
use App\Models\SystemSetting;
use App\Security\DeviceSecurityStateService;
use App\Sessions\ClientSessionException;
use App\Sessions\ClientSessionService;
use Illuminate\Http\Request;

final class UniversalCardService
{
    public function __construct(
        private readonly DailyVerificationCodec $dailyCodec,
        private readonly ClientSessionService $sessions,
        private readonly DeviceSecurityStateService $deviceStates,
        private readonly AuditLogger $audit,
    ) {}

    public function matches(string $credential): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $configuredHash = strtolower(trim((string) config('universal_card.code_hash', '')));

        return preg_match('/^[a-f0-9]{64}$/', $configuredHash) === 1
            && hash_equals($configuredHash, hash('sha256', trim($credential)));
    }

    private function enabled(): bool
    {
        if (! config('universal_card.enabled', false)) {
            return false;
        }

        return (string) SystemSetting::query()
            ->whereKey('universal_card_enabled')
            ->value('value') === '1';
    }

    /** @return array<string, mixed> */
    public function login(
        Application $application,
        string $deviceId,
        string $appsafecode,
        string $sessionHandle,
        Request $request,
    ): array {
        $device = trim($deviceId);
        if ($device === '') {
            throw new CardActivationException('DEVICE_REQUIRED', '当前应用要求提供设备标识。');
        }
        if ($this->deviceStates->isBlocked($application, $device)) {
            throw new CardActivationException('DEVICE_BLOCKED', '设备已进入安全封控。', 403);
        }

        try {
            $handle = $this->sessions->bindUniversal($application, $sessionHandle, $device);
        } catch (ClientSessionException $exception) {
            throw new CardActivationException($exception->errorCode, $exception->getMessage(), $exception->status);
        }

        $payload = $application->verificationData();
        $encryptedPayload = $this->dailyCodec->encodePayload($payload);
        $signature = $this->dailyCodec->dailySignature();
        $expiresAt = (string) config('universal_card.expires_at', '2099-12-31T23:59:59+08:00');
        $legacy = implode('|', [
            '01', 'xx', $encryptedPayload, $appsafecode, $signature, '1', $expiresAt,
            '1', $device, $handle, '',
        ]);

        $this->audit->record(new AuditEvent(
            action: 'client.universal_card_login',
            targetType: 'application',
            targetId: $application->public_id,
            applicationId: $application->getKey(),
            before: [
                'application' => ['code' => $application->code, 'name' => $application->name],
                'credential_masked' => '万能卡密',
                'device' => $device,
            ],
            after: [
                'internal_code' => 'UNIVERSAL_CARD_ACCEPTED',
                'internal_message' => '万能卡密登录成功。',
                'public_message' => '登录成功',
                'http_status' => 200,
                'expires_at' => $expiresAt,
                'bound_device' => $device,
                'returned_device' => $device,
            ],
            reason: 'UNIVERSAL_CARD_ACCEPTED',
            result: 'success',
            actorRole: 'client',
        ), $request);

        return [
            'application' => ['code' => $application->code, 'name' => $application->name],
            'card' => [
                'public_id' => 'UNIVERSAL',
                'masked' => '万能卡密',
                'status' => 'active',
                'activated_at' => now()->toIso8601String(),
                'expires_at' => $expiresAt,
                'points' => null,
            ],
            'device' => $device,
            'payload' => $payload,
            'encrypted_payload' => $encryptedPayload,
            'daily_signature' => $signature,
            'appsafecode' => $appsafecode,
            'BSphpSeSsL' => $handle,
            'legacy_response' => $legacy,
            'universal_card' => true,
        ];
    }
}
