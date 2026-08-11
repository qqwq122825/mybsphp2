<?php

namespace App\Cards;

use App\Applications\DailyVerificationCodec;
use App\Models\Application;
use App\Models\Card;
use App\Security\DeviceSecurityStateService;
use App\Sessions\ClientSessionException;
use App\Sessions\ClientSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CardActivationService
{
    public function __construct(
        private readonly CardCredentialHasher $hasher,
        private readonly DailyVerificationCodec $dailyCodec,
        private readonly ClientSessionService $sessions,
        private readonly DeviceSecurityStateService $deviceStates,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function activate(
        string $code,
        string $secret,
        string $deviceId,
        string $appsafecode = '',
        string $sessionHandle = '',
    ): array {
        return DB::transaction(function () use ($code, $secret, $deviceId, $appsafecode, $sessionHandle): array {
            $card = Card::query()
                ->with(['application', 'cardType'])
                ->where('code_lookup_hash', $this->hasher->lookupHash($code))
                ->lockForUpdate()
                ->first();

            if (! $card instanceof Card) {
                throw new CardActivationException('INVALID_CARD', '卡密不存在。', 404);
            }

            $application = $card->application;
            $cardType = $card->cardType;

            if (
                (int) $cardType->secret_length > 0
                && (! is_string($card->secret_hash) || ! Hash::check($secret, $card->secret_hash))
            ) {
                throw new CardActivationException('INVALID_CARD', '卡密不存在。', 404);
            }

            if ($application->status !== 'active') {
                throw new CardActivationException('APP_DISABLED', $application->closedMessage(), 403);
            }
            if ($cardType->status !== 'active') {
                throw new CardActivationException('CARD_TYPE_DISABLED', '该卡种当前未启用。');
            }
            if ($card->status !== 'active' && ($card->card_is_frozen || $card->status === 'frozen')) {
                throw new CardActivationException('CARD_FROZEN', '你选择卡串已冻结！');
            }
            if ($card->status === 'expired') {
                throw new CardActivationException('CARD_EXPIRED', '该卡密已经到期。', 403);
            }
            if (in_array($card->status, ['revoked', 'consumed'], true)) {
                throw new CardActivationException('CARD_UNAVAILABLE', '该卡密当前状态不可激活。');
            }
            $normalizedDevice = trim($deviceId);
            if ($normalizedDevice === '') {
                throw new CardActivationException('DEVICE_REQUIRED', '当前应用要求提供设备标识。');
            }
            if ($this->deviceStates->isBlocked($application, $normalizedDevice)) {
                return $this->result($card, $appsafecode, '', true);
            }

            if ($card->status === 'active') {
                if ($card->user_status === 'frozen') {
                    throw new CardActivationException('USER_FROZEN', '该软件用户已冻结。', 403);
                }
                $boundDevice = trim((string) ($card->bound_device_ciphertext ?? ''));
                if (
                    $boundDevice !== ''
                    && $this->machineBindingLimit($application, $card) !== -1
                    && ! hash_equals($boundDevice, $normalizedDevice)
                ) {
                    throw new CardActivationException('DEVICE_MISMATCH', '该卡密已被绑定，当前设备特征不匹配。', 409);
                }

                if ($boundDevice === '') {
                    $card->forceFill([
                        'bound_device_ciphertext' => $normalizedDevice,
                        'bound_device_mask' => $this->maskDevice($normalizedDevice),
                    ]);
                }

                if ($card->expires_at?->isPast()) {
                    $card->forceFill(['status' => 'expired'])->save();
                    throw new CardActivationException('CARD_EXPIRED', '该卡密已经到期。', 403);
                }

                $card->forceFill(['last_online_at' => now()])->save();

                return $this->result($card->refresh(), $appsafecode, $this->bindSession($card, $sessionHandle, $normalizedDevice));
            }

            $expiresAt = match ($cardType->benefit_kind) {
                'duration' => now()->addSeconds((int) $cardType->duration_seconds),
                'fixed_expiry' => $cardType->fixed_expires_at,
                default => null,
            };

            $card->forceFill([
                'status' => 'active',
                'user_status' => 'active',
                'activated_at' => now(),
                'last_online_at' => now(),
                'expires_at' => $expiresAt,
                'bound_device_ciphertext' => $normalizedDevice !== '' ? $normalizedDevice : null,
                'bound_device_mask' => $this->maskDevice($normalizedDevice),
                'activation_count' => $card->activation_count + 1,
                'lock_version' => $card->lock_version + 1,
            ])->save();

            $card->refresh();
            $card->load(['application', 'cardType']);

            return $this->result($card, $appsafecode, $this->bindSession($card, $sessionHandle, $normalizedDevice));
        }, 3);
    }

    /**
     * @return array<string, mixed>
     */
    private function result(Card $card, string $appsafecode, string $sessionHandle = '', bool $deviceBlocked = false): array
    {
        $application = $card->application;
        $payload = $deviceBlocked ? '' : $application->verificationData();
        $encryptedPayload = $this->dailyCodec->encodePayload($payload);
        $signature = $this->dailyCodec->dailySignature();
        // Keep the configured application timezone in the wire value. Carbon's
        // toISOString() normalizes to UTC ("Z"), which made the raw server value
        // appear eight hours behind the administration table in Asia/Shanghai.
        $expiresAt = $card->expires_at?->toIso8601String();
        $device = (string) ($card->bound_device_ciphertext ?? '');
        $legacy = implode('|', [
            '01',
            'xx',
            $encryptedPayload,
            $appsafecode,
            $signature,
            '1',
            $expiresAt ?? '',
            '1',
            $device,
            $sessionHandle,
            '',
        ]);

        return [
            'application' => [
                'code' => $application->code,
                'name' => $application->name,
            ],
            'card' => [
                'public_id' => $card->public_id,
                'masked' => '••••-'.($card->code_last4),
                'status' => $card->status,
                'activated_at' => $card->activated_at?->toIso8601String(),
                'expires_at' => $expiresAt,
                'points' => $card->cardType->benefit_kind === 'points'
                    ? $card->cardType->points_amount
                    : null,
            ],
            'device' => $card->bound_device_mask,
            'payload' => $payload,
            'encrypted_payload' => $encryptedPayload,
            'daily_signature' => $signature,
            'appsafecode' => $appsafecode,
            'BSphpSeSsL' => $sessionHandle,
            'legacy_response' => $legacy,
        ];
    }

    private function bindSession(Card $card, string $handle, string $deviceId): string
    {
        try {
            return $this->sessions->bindCard($card->application, $card, $handle, $deviceId);
        } catch (ClientSessionException $exception) {
            throw new CardActivationException($exception->errorCode, $exception->getMessage(), $exception->status);
        }
    }

    private function maskDevice(string $device): ?string
    {
        if ($device === '') {
            return null;
        }

        return strlen($device) <= 8
            ? substr($device, 0, 2).'••'.substr($device, -2)
            : substr($device, 0, 4).'••••'.substr($device, -4);
    }

    private function machineBindingLimit(Application $application, Card $card): int
    {
        $cardType = $card->relationLoaded('cardType')
            ? $card->cardType
            : $card->cardType()->first();
        $cardLimit = (int) ($card->machine_concurrency_limit ?? 0);
        $typeLimit = (int) ($cardType?->machine_concurrency_limit ?? 0);

        return $cardLimit !== 0
            ? $cardLimit
            : ($typeLimit !== 0 ? $typeLimit : (int) $application->max_bound_devices);
    }
}
