<?php

namespace App\Cards;

use App\Applications\DailyVerificationCodec;
use App\Models\Card;
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
            if (in_array($card->status, ['revoked', 'expired', 'consumed'], true)) {
                throw new CardActivationException('CARD_UNAVAILABLE', '该卡密当前状态不可激活。');
            }
            $normalizedDevice = trim($deviceId);
            if ($application->bind_device_enabled && $normalizedDevice === '') {
                throw new CardActivationException('DEVICE_REQUIRED', '当前应用要求提供设备标识。');
            }

            if ($card->status === 'active') {
                if ($card->user_status === 'frozen') {
                    throw new CardActivationException('USER_FROZEN', '该软件用户已冻结。', 403);
                }
                if (
                    $application->bind_device_enabled
                    && is_string($card->bound_device_ciphertext)
                    && ! hash_equals($card->bound_device_ciphertext, $normalizedDevice)
                ) {
                    throw new CardActivationException('DEVICE_MISMATCH', '该卡密已绑定其他设备。', 409);
                }

                if ($card->expires_at?->isPast()) {
                    $card->forceFill(['status' => 'expired'])->save();
                    throw new CardActivationException('CARD_EXPIRED', '该卡密已经到期。');
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
    private function result(Card $card, string $appsafecode, string $sessionHandle = ''): array
    {
        $application = $card->application;
        $payload = $application->verificationData();
        $encryptedPayload = $this->dailyCodec->encodePayload($payload);
        $signature = $this->dailyCodec->dailySignature();
        $expiresAt = $card->expires_at?->toISOString();
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
                'activated_at' => $card->activated_at?->toISOString(),
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
}
