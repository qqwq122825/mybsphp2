<?php

namespace App\Cards;

use App\Models\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CardActivationService
{
    public function __construct(
        private readonly CardCredentialHasher $hasher,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function activate(
        string $code,
        string $secret,
        string $deviceId,
        string $appsafecode = '',
    ): array {
        return DB::transaction(function () use ($code, $secret, $deviceId, $appsafecode): array {
            $card = Card::query()
                ->with(['application', 'cardType'])
                ->where('code_lookup_hash', $this->hasher->lookupHash($code))
                ->lockForUpdate()
                ->first();

            if (! $card instanceof Card || ! is_string($card->secret_hash) || ! Hash::check($secret, $card->secret_hash)) {
                throw new CardActivationException('INVALID_CARD', '卡号或卡密密码错误。', 404);
            }

            $application = $card->application;
            $cardType = $card->cardType;

            if ($application->status !== 'active') {
                throw new CardActivationException('APP_DISABLED', '该应用当前未启用。');
            }
            if ($cardType->status !== 'active') {
                throw new CardActivationException('CARD_TYPE_DISABLED', '该卡种当前未启用。');
            }
            if (in_array($card->status, ['frozen', 'revoked', 'expired', 'consumed'], true)) {
                throw new CardActivationException('CARD_UNAVAILABLE', '该卡密当前状态不可激活。');
            }
            if ($card->status === 'inventory') {
                throw new CardActivationException('CARD_NOT_ISSUED', '该卡密尚未发出。');
            }

            $normalizedDevice = trim($deviceId);
            if ($application->bind_device_enabled && $normalizedDevice === '') {
                throw new CardActivationException('DEVICE_REQUIRED', '当前应用要求提供设备标识。');
            }

            if ($card->status === 'active') {
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

                return $this->result($card, $appsafecode);
            }

            $expiresAt = match ($cardType->benefit_kind) {
                'duration' => now()->addSeconds((int) $cardType->duration_seconds),
                'fixed_expiry' => $cardType->fixed_expires_at,
                default => null,
            };

            $card->forceFill([
                'status' => 'active',
                'activated_at' => now(),
                'expires_at' => $expiresAt,
                'bound_device_ciphertext' => $normalizedDevice !== '' ? $normalizedDevice : null,
                'bound_device_mask' => $this->maskDevice($normalizedDevice),
                'activation_count' => $card->activation_count + 1,
                'lock_version' => $card->lock_version + 1,
            ])->save();

            $card->refresh();
            $card->load(['application', 'cardType']);

            return $this->result($card, $appsafecode);
        }, 3);
    }

    /**
     * @return array<string, mixed>
     */
    private function result(Card $card, string $appsafecode): array
    {
        $application = $card->application;
        $payload = $application->payload_mode === 'empty'
            ? ''
            : (string) ($application->payload_value_ciphertext ?? '');
        $signature = strtoupper(substr(hash_hmac(
            'sha256',
            $application->code.'|'.now()->utc()->format('Y-m-d'),
            (string) config('app.key'),
        ), 0, 10));
        $expiresAt = $card->expires_at?->toISOString();
        $device = (string) ($card->bound_device_ciphertext ?? '');
        $legacy = implode('|', [
            '01',
            'xx',
            $payload,
            $appsafecode,
            $signature,
            '1',
            $expiresAt ?? '',
            '1',
            $device,
            '',
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
            'daily_signature' => $signature,
            'appsafecode' => $appsafecode,
            'legacy_response' => $legacy,
        ];
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
