<?php

namespace App\Sessions;

use App\Cards\UniversalCardService;
use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\ApplicationClientSession;
use App\Models\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ClientSessionService
{
    public function issueHandle(string $candidate = ''): string
    {
        $candidate = trim($candidate);
        if ($candidate !== '' && preg_match('/^[A-Za-z0-9_-]{64}$/', $candidate)) return $candidate;

        return bin2hex(random_bytes(32));
    }

    public function bindCard(Application $application, Card $card, string $handle, string $deviceId): string
    {
        return $this->bind($application, $handle, $deviceId, 'card', $card, null);
    }

    public function bindAccount(Application $application, ApplicationAccount $account, string $handle, string $deviceId): string
    {
        return $this->bind($application, $handle, $deviceId, 'account', null, $account);
    }

    public function bindUniversal(Application $application, string $handle, string $deviceId): string
    {
        if (trim($handle) === '') return '';
        $handle = $this->issueHandle($handle);
        $tokenHash = hash('sha256', $handle);

        return DB::transaction(function () use ($application, $handle, $tokenHash, $deviceId): string {
            $existing = ApplicationClientSession::query()
                ->where('application_id', $application->getKey())
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();
            if ($existing && $existing->auth_mode !== 'universal') {
                throw new ClientSessionException('SESSION_CONFLICT', 'BSphpSeSsL 已绑定其他登录用户。', 409);
            }

            ApplicationClientSession::query()->updateOrCreate(
                ['application_id' => $application->getKey(), 'token_hash' => $tokenHash],
                [
                    'public_id' => $existing?->public_id ?? (string) Str::ulid(),
                    'auth_mode' => 'universal',
                    'card_id' => null,
                    'application_account_id' => null,
                    'device_id_ciphertext' => trim($deviceId) ?: null,
                    'device_mask' => $this->maskDevice($deviceId),
                    'status' => 'active',
                    'last_heartbeat_at' => now(),
                    'logged_out_at' => null,
                ],
            );

            return $handle;
        }, 3);
    }

    /** @return array{code: string, message: string, handle: string, server_time: string} */
    public function heartbeat(Application $application, string $handle, string $deviceId = ''): array
    {
        $handle = trim($handle);
        if (! preg_match('/^[A-Za-z0-9_-]{64}$/', $handle)) return $this->heartbeatResult('5026', '登录连接标记无效', $handle);

        $session = ApplicationClientSession::query()
            ->with(['card', 'account'])
            ->where('application_id', $application->getKey())
            ->where('token_hash', hash('sha256', $handle))
            ->first();
        if (! $session instanceof ApplicationClientSession || $session->status !== 'active') {
            return $this->heartbeatResult('5026', '登录连接已失效', $handle);
        }
        if ($session->last_heartbeat_at->lt(now()->subSeconds((int) $application->connection_timeout_seconds))) {
            $session->forceFill(['status' => 'timed_out'])->save();
            return $this->heartbeatResult('5026', '登录连接超时', $handle);
        }
        if ($deviceId !== '' && $session->device_id_ciphertext && ! hash_equals((string) $session->device_id_ciphertext, trim($deviceId))) {
            return $this->heartbeatResult('5026', '登录设备不匹配', $handle);
        }

        if ($session->auth_mode === 'universal') {
            if (! app(UniversalCardService::class)->isEnabled()) {
                $session->forceFill(['status' => 'logged_out', 'logged_out_at' => now()])->save();

                return $this->heartbeatResult('5026', '登录连接已失效', $handle);
            }
        } elseif ($session->auth_mode === 'card') {
            $card = $session->card;
            if (! $card instanceof Card || $card->user_status === 'frozen') return $this->heartbeatResult('5026', '网络连接异常', $handle);
            if ($card->status !== 'active' || ! $card->expires_at || $card->expires_at->isPast()) return $this->heartbeatResult('5030', '授权时间已到期', $handle);
            $card->forceFill(['last_online_at' => now()])->save();
        } else {
            $account = $session->account;
            if (! $account instanceof ApplicationAccount || $account->status === 'frozen') return $this->heartbeatResult('5026', '网络连接异常', $handle);
            if ($account->status !== 'active' || ! $account->expires_at || $account->expires_at->isPast()) return $this->heartbeatResult('5030', '授权时间已到期', $handle);
        }

        $session->forceFill(['last_heartbeat_at' => now()])->save();

        return $this->heartbeatResult('5031', '状态正常', $handle);
    }

    public function revokeUniversalSessions(): int
    {
        return ApplicationClientSession::query()
            ->where('auth_mode', 'universal')
            ->where('status', 'active')
            ->update([
                'status' => 'logged_out',
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function bind(
        Application $application,
        string $handle,
        string $deviceId,
        string $mode,
        ?Card $card,
        ?ApplicationAccount $account,
    ): string {
        if (trim($handle) === '') return '';
        $handle = $this->issueHandle($handle);
        $tokenHash = hash('sha256', $handle);

        return DB::transaction(function () use ($application, $handle, $tokenHash, $deviceId, $mode, $card, $account): string {
            $existing = ApplicationClientSession::query()
                ->where('application_id', $application->getKey())
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()->first();
            if ($existing && (($card && $existing->card_id !== $card->getKey()) || ($account && $existing->application_account_id !== $account->getKey()))) {
                throw new ClientSessionException('SESSION_CONFLICT', 'BSphpSeSsL 已绑定其他登录用户。', 409);
            }

            $subjectColumn = $card ? 'card_id' : 'application_account_id';
            $subjectId = $card?->getKey() ?? $account?->getKey();
            $activeSessions = ApplicationClientSession::query()
                ->where('application_id', $application->getKey())
                ->where($subjectColumn, $subjectId)
                ->where('status', 'active')
                ->where('last_heartbeat_at', '>=', now()->subSeconds((int) $application->connection_timeout_seconds))
                ->when($existing, fn ($query) => $query->where('id', '!=', $existing->getKey()))
                ->get(['id', 'device_id_ciphertext']);

            $accountLimit = $this->accountSessionLimit($application, $card, $account);
            if ($accountLimit >= 0 && $activeSessions->count() >= $accountLimit) {
                throw new ClientSessionException('SESSION_LIMIT', '已达到最大并发登录数。', 409);
            }

            $machineLimit = $this->machineSessionLimit($application, $card, $account);
            if ($machineLimit >= 0) {
                $deviceCount = $activeSessions
                    ->pluck('device_id_ciphertext')
                    ->map(fn ($device): string => trim((string) $device))
                    ->filter()
                    ->push(trim($deviceId))
                    ->unique()
                    ->count();
                if ($deviceCount > $machineLimit) {
                    throw new ClientSessionException('SESSION_LIMIT', '已达到最大绑定设备数。', 409);
                }
            }

            ApplicationClientSession::query()->updateOrCreate(
                ['application_id' => $application->getKey(), 'token_hash' => $tokenHash],
                [
                    'public_id' => $existing?->public_id ?? (string) Str::ulid(), 'auth_mode' => $mode,
                    'card_id' => $card?->getKey(), 'application_account_id' => $account?->getKey(),
                    'device_id_ciphertext' => trim($deviceId) ?: null, 'device_mask' => $this->maskDevice($deviceId),
                    'status' => 'active', 'last_heartbeat_at' => now(), 'logged_out_at' => null,
                ],
            );

            return $handle;
        }, 3);
    }

    private function accountSessionLimit(Application $application, ?Card $card, ?ApplicationAccount $account): int
    {
        if ($account instanceof ApplicationAccount) {
            $accountLimit = (int) ($account->account_concurrency_limit ?? 0);
            return $accountLimit !== 0 ? $accountLimit : (int) $application->max_concurrent_sessions;
        }
        if (! $card instanceof Card) {
            return (int) $application->max_concurrent_sessions;
        }

        $cardType = $card->relationLoaded('cardType')
            ? $card->cardType
            : $card->cardType()->first();
        $cardLimit = (int) ($card->account_concurrency_limit ?? 0);
        $typeLimit = (int) ($cardType?->account_concurrency_limit ?? 0);

        return $cardLimit !== 0
            ? $cardLimit
            : ($typeLimit !== 0 ? $typeLimit : (int) $application->max_concurrent_sessions);
    }

    private function machineSessionLimit(Application $application, ?Card $card, ?ApplicationAccount $account): int
    {
        if ($account instanceof ApplicationAccount) {
            $accountLimit = (int) ($account->machine_concurrency_limit ?? 0);
            return $accountLimit !== 0 ? $accountLimit : (int) $application->max_bound_devices;
        }
        if (! $card instanceof Card) {
            return (int) $application->max_bound_devices;
        }

        $cardType = $card->relationLoaded('cardType')
            ? $card->cardType
            : $card->cardType()->first();
        $cardLimit = (int) ($card->machine_concurrency_limit ?? 0);
        $typeLimit = (int) ($cardType?->machine_concurrency_limit ?? 0);

        return $cardLimit !== 0
            ? $cardLimit
            : ($typeLimit !== 0 ? $typeLimit : (int) $application->max_bound_devices);
    }

    /** @return array{code: string, message: string, handle: string, server_time: string} */
    private function heartbeatResult(string $code, string $message, string $handle): array
    {
        return ['code' => $code, 'message' => $message, 'handle' => $handle, 'server_time' => now()->utc()->toISOString()];
    }

    private function maskDevice(string $device): ?string
    {
        $device = trim($device);
        if ($device === '') return null;
        return strlen($device) <= 8 ? substr($device, 0, 2).'••'.substr($device, -2) : substr($device, 0, 4).'••••'.substr($device, -4);
    }
}
