<?php

namespace App\Accounts;

use App\Applications\DailyVerificationCodec;
use App\Cards\CardCredentialHasher;
use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\Card;
use App\Sessions\ClientSessionException;
use App\Sessions\ClientSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AccountAuthService
{
    public function __construct(
        private readonly CardCredentialHasher $cardHasher,
        private readonly DailyVerificationCodec $dailyCodec,
        private readonly ClientSessionService $sessions,
    ) {}

    /** @return array<string, mixed> */
    public function register(
        Application $application,
        string $username,
        string $password,
        string $deviceId = '',
        string $cardCode = '',
        string $cardSecret = '',
        string $invitationCode = '',
        string $appsafecode = '',
        string $sessionHandle = '',
    ): array {
        $this->assertAccountMode($application);
        $username = trim($username);
        $deviceId = trim($deviceId);
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_.-]{2,31}$/', $username)) {
            throw new AccountAuthException('INVALID_USERNAME', '账号需以字母开头，可使用字母、数字、下划线、点或短横线，长度 3-32 位。');
        }
        if (mb_strlen($password) < 6 || mb_strlen($password) > 72) {
            throw new AccountAuthException('INVALID_PASSWORD', '密码长度需为 6-72 位。');
        }
        if ($deviceId === '') {
            throw new AccountAuthException('DEVICE_REQUIRED', '当前软件要求提供绑定特征。');
        }

        return DB::transaction(function () use ($application, $username, $password, $deviceId, $cardCode, $cardSecret, $invitationCode, $appsafecode, $sessionHandle): array {
            if ($application->accounts()->where('username', $username)->lockForUpdate()->exists()) {
                throw new AccountAuthException('ACCOUNT_EXISTS', '该账号已经注册。', 409);
            }

            $inviter = null;
            if (trim($invitationCode) !== '') {
                $inviter = $application->accounts()
                    ->where('invitation_code', strtoupper(trim($invitationCode)))
                    ->lockForUpdate()
                    ->first();
                if (! $inviter instanceof ApplicationAccount) {
                    throw new AccountAuthException('INVALID_INVITATION_CODE', '邀请码不存在。');
                }
            }

            $account = $application->accounts()->create([
                'public_id' => (string) Str::ulid(),
                'username' => $username,
                'password_hash' => Hash::make($password),
                'invitation_code' => $this->newInvitationCode($application),
                'invited_by_account_id' => $inviter?->getKey(),
                'status' => 'active',
                'expires_at' => now(),
                'bound_device_ciphertext' => $deviceId !== '' ? $deviceId : null,
                'bound_device_mask' => $this->maskDevice($deviceId),
            ]);

            $registrationBonusSeconds = $application->registrationBonusSeconds();
            if ($registrationBonusSeconds > 0) {
                $this->extendAccount($account, $registrationBonusSeconds);
                $this->record($application, $account, null, 'registration_bonus', $registrationBonusSeconds, []);
            }

            if (trim($cardCode) !== '') {
                $this->redeemCard($application, $account, $cardCode, $cardSecret, 'registration_card');
            }

            $bonusSeconds = $application->invitationBonusSeconds();
            if ($inviter instanceof ApplicationAccount && $bonusSeconds > 0) {
                $this->extendAccount($account, $bonusSeconds);
                $this->extendAccount($inviter, $bonusSeconds);
                $this->record($application, $account, null, 'invite_invitee', $bonusSeconds, ['inviter' => $inviter->public_id]);
                $this->record($application, $inviter, null, 'invite_inviter', $bonusSeconds, ['invitee' => $account->public_id]);
            }

            $account->refresh();
            $boundHandle = $account->expires_at?->isFuture() ? $this->bindAccountSession($application, $account, $sessionHandle, $deviceId) : '';
            return $this->result($account, $application, $appsafecode, $boundHandle);
        }, 3);
    }

    /** @return array<string, mixed> */
    public function login(Application $application, string $username, string $password, string $deviceId = '', string $appsafecode = '', string $sessionHandle = ''): array
    {
        $this->assertAccountMode($application);
        $account = $application->accounts()->where('username', trim($username))->first();
        if (! $account instanceof ApplicationAccount || ! Hash::check($password, $account->password_hash)) {
            if ($account instanceof ApplicationAccount) {
                $account->increment('failed_attempt_count');
            }
            throw new AccountAuthException('INVALID_CREDENTIALS', '账号或密码错误。', 401);
        }
        $deviceId = trim($deviceId);
        if ($deviceId === '') {
            throw new AccountAuthException('DEVICE_REQUIRED', '当前软件要求提供绑定特征。');
        }

        $account = DB::transaction(function () use ($application, $account, $deviceId): ApplicationAccount {
            $lockedAccount = $application->accounts()
                ->whereKey($account->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $machineLimit = (int) ($lockedAccount->machine_concurrency_limit ?? 0);
            $machineLimit = $machineLimit !== 0 ? $machineLimit : (int) $application->max_bound_devices;

            if ($lockedAccount->status === 'frozen') {
                throw new AccountAuthException('USER_FROZEN', '该软件用户已冻结。', 403);
            }
            if ($lockedAccount->status !== 'active') {
                throw new AccountAuthException('USER_UNAVAILABLE', '该软件用户当前状态不可登录。', 403);
            }
            if (
                $lockedAccount->bound_device_ciphertext
                && $machineLimit !== -1
                && ! hash_equals((string) $lockedAccount->bound_device_ciphertext, $deviceId)
            ) {
                throw new AccountAuthException('DEVICE_MISMATCH', '账号已被绑定，当前设备特征不匹配。', 409);
            }
            if (! $lockedAccount->bound_device_ciphertext) {
                $lockedAccount->bound_device_ciphertext = $deviceId;
                $lockedAccount->bound_device_mask = $this->maskDevice($deviceId);
            }
            $lockedAccount->last_login_at = now();
            $lockedAccount->login_count++;
            $lockedAccount->failed_attempt_count = 0;
            $lockedAccount->save();

            return $lockedAccount;
        }, 3);

        if (! $account->expires_at || $account->expires_at->isPast()) {
            throw new AccountAuthException(
                'ACCOUNT_EXPIRED',
                '账号密码验证成功，但授权时间已经到期，请使用充值卡续期。',
                403,
                $this->expiredDetails($account),
            );
        }

        $account->refresh();
        return $this->result($account, $application, $appsafecode, $this->bindAccountSession($application, $account, $sessionHandle, $deviceId));
    }

    /** @return array<string, mixed> */
    public function recharge(Application $application, string $username, string $cardCode, string $cardSecret = '', string $appsafecode = ''): array
    {
        $this->assertAccountMode($application);

        return DB::transaction(function () use ($application, $username, $cardCode, $cardSecret, $appsafecode): array {
            $account = $application->accounts()->where('username', trim($username))->lockForUpdate()->first();
            if (! $account instanceof ApplicationAccount) {
                throw new AccountAuthException('ACCOUNT_NOT_FOUND', '账号不存在。', 404);
            }
            $this->redeemCard($application, $account, $cardCode, $cardSecret, 'recharge_card');

            return $this->result($account->refresh(), $application, $appsafecode);
        }, 3);
    }

    private function redeemCard(Application $application, ApplicationAccount $account, string $code, string $secret, string $type): void
    {
        $card = Card::query()->with('cardType')
            ->where('application_id', $application->getKey())
            ->where('code_lookup_hash', $this->cardHasher->lookupHash($code))
            ->lockForUpdate()->first();
        if (! $card instanceof Card) {
            throw new AccountAuthException('INVALID_CARD', '充值卡号错误。', 404);
        }
        if ($card->card_is_frozen || $card->status === 'frozen') {
            throw new AccountAuthException('CARD_FROZEN', '你选择卡串已冻结！');
        }
        if (! in_array($card->status, ['inventory', 'issued'], true) || $card->cardType->status !== 'active') {
            throw new AccountAuthException('CARD_UNAVAILABLE', '该充值卡已使用或当前状态不可用。');
        }

        $seconds = 0;
        if ($card->cardType->benefit_kind === 'duration') {
            $seconds = (int) $card->cardType->duration_seconds;
            $this->extendAccount($account, $seconds);
        } elseif ($card->cardType->benefit_kind === 'fixed_expiry') {
            $account->expires_at = $card->cardType->fixed_expires_at;
        } elseif ($card->cardType->benefit_kind === 'points') {
            $account->points += (int) $card->cardType->points_amount;
        }
        $account->balance_cents = max(0, $account->balance_cents + (int) $card->cardType->balance_recharge_cents);
        $account->save();

        $card->forceFill([
            'status' => 'consumed',
            'consumed_at' => now(),
            'redeemed_by_account_id' => $account->getKey(),
            'lock_version' => $card->lock_version + 1,
        ])->save();
        $this->record($application, $account, $card, $type, $seconds, [
            'card_type' => $card->cardType->public_id,
        ]);
    }

    private function extendAccount(ApplicationAccount $account, int $seconds): void
    {
        if ($seconds <= 0) return;
        $base = $account->expires_at && $account->expires_at->isFuture() ? $account->expires_at : now();
        $account->expires_at = $base->copy()->addSeconds($seconds);
        $account->save();
    }

    /** @param array<string, mixed> $metadata */
    private function record(Application $application, ApplicationAccount $account, ?Card $card, string $type, int $seconds, array $metadata): void
    {
        DB::table('account_entitlement_transactions')->insert([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'application_account_id' => $account->getKey(),
            'card_id' => $card?->getKey(),
            'type' => $type,
            'seconds' => $seconds,
            'balance_cents' => $card ? (int) $card->cardType->balance_recharge_cents : 0,
            'points' => $card && $card->cardType->benefit_kind === 'points' ? (int) $card->cardType->points_amount : 0,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(ApplicationAccount $account, Application $application, string $appsafecode, string $sessionHandle = ''): array
    {
        $signature = $this->dailyCodec->dailySignature();
        // An explicit offset keeps the JSON, legacy wire response and
        // administration table on the same configured application timezone.
        $expires = $account->expires_at?->toIso8601String() ?? '';
        $device = (string) ($account->bound_device_ciphertext ?? '');
        $payload = $application->verificationData();
        $encryptedPayload = $this->dailyCodec->encodePayload($payload);
        $legacy = implode('|', ['01', '1011', $device, $encryptedPayload, $expires, $appsafecode, $signature, $account->invitation_code, $account->username, $sessionHandle, '']);

        return [
            'account' => [
                'public_id' => $account->public_id,
                'username' => $account->username,
                'status' => $account->status,
                'expires_at' => $account->expires_at?->toIso8601String(),
                'invitation_code' => $account->invitation_code,
                'balance_cents' => $account->balance_cents,
                'points' => $account->points,
                'credentials_verified' => true,
            ],
            'authorization' => $this->authorization($account),
            'device' => $device,
            'payload' => $application->verificationData(),
            'encrypted_payload' => $encryptedPayload,
            'appsafecode' => $appsafecode,
            'BSphpSeSsL' => $sessionHandle,
            'legacy_response' => $legacy,
        ];
    }

    private function bindAccountSession(Application $application, ApplicationAccount $account, string $handle, string $deviceId): string
    {
        try { return $this->sessions->bindAccount($application, $account, $handle, $deviceId); }
        catch (ClientSessionException $exception) {
            throw new AccountAuthException($exception->errorCode, $exception->getMessage(), $exception->status);
        }
    }

    /** @return array<string, mixed> */
    private function expiredDetails(ApplicationAccount $account): array
    {
        return [
            'credentials_verified' => true,
            'account' => [
                'username' => $account->username,
                'status' => $account->status,
                'expires_at' => $account->expires_at?->toIso8601String(),
                'invitation_code' => $account->invitation_code,
            ],
            'authorization' => $this->authorization($account),
        ];
    }

    /** @return array{status: string, is_expired: bool, expires_at: ?string, remaining_seconds: int} */
    private function authorization(ApplicationAccount $account): array
    {
        $expiresAt = $account->expires_at;
        $expired = ! $expiresAt || $expiresAt->isPast();

        return [
            'status' => $expired ? 'expired' : 'active',
            'is_expired' => $expired,
            'expires_at' => $expiresAt?->toIso8601String(),
            'remaining_seconds' => $expired ? 0 : (int) max(0, now()->diffInSeconds($expiresAt, false)),
        ];
    }

    private function newInvitationCode(Application $application): string
    {
        do { $code = strtoupper(Str::random(10)); }
        while ($application->accounts()->where('invitation_code', $code)->exists());
        return $code;
    }

    private function assertAccountMode(Application $application): void
    {
        if ($application->mode !== 'account_recharge') throw new AccountAuthException('MODE_MISMATCH', '当前软件不是账号登录模式。', 409);
        if ($application->status !== 'active') throw new AccountAuthException('APP_DISABLED', $application->closedMessage(), 403, [
            'application_status' => $application->status,
            'is_open' => false,
        ]);
    }

    private function maskDevice(string $device): ?string
    {
        if ($device === '') return null;
        return strlen($device) <= 8 ? substr($device, 0, 2).'••'.substr($device, -2) : substr($device, 0, 4).'••••'.substr($device, -4);
    }
}
