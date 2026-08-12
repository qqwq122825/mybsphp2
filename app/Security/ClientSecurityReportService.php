<?php

namespace App\Security;

use App\Models\Application;
use App\Models\ApplicationClientSession;
use App\Models\ClientSecurityEvent;
use App\Risk\IpRiskControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ClientSecurityReportService
{
    public function __construct(
        private readonly DeviceSecurityStateService $deviceStates,
        private readonly IpRiskControlService $ipRisk,
    ) {}

    private const EVENTS = [
        'D0' => ['high', 'freeze'], 'D1' => ['high', 'freeze'], 'R0' => ['high', 'freeze'],
        'S0' => ['high', 'freeze'],
        'I0' => ['medium', 'observe'], 'I1' => ['medium', 'observe'], 'T0' => ['low', 'observe'],
    ];

    public function rotateToken(Application $application, string $handle): string
    {
        if ($this->mode($application) === 'disabled') {
            return '';
        }
        if (! preg_match('/^[A-Za-z0-9_-]{64}$/', $handle)) {
            return '';
        }
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        ApplicationClientSession::query()->where('application_id', $application->getKey())->where('token_hash', hash('sha256', $handle))
            ->where('status', 'active')->update(['security_report_token_hash' => hash('sha256', $token), 'security_report_token_expires_at' => now()->addMinutes(30)]);

        return $token;
    }

    public function report(Application $application, string $handle, string $device, string $token, string $eventId, string $eventCode, mixed $evidence, string $ip): void
    {
        $mode = $this->mode($application);
        if ($mode === 'disabled') {
            throw new InvalidArgumentException('SECURITY_REPORT_DISABLED');
        }
        if (! preg_match('/^[A-Za-z0-9_-]{64}$/', $handle) || ! preg_match('/^[A-Za-z0-9_-]{8,64}$/', $eventId) || ! isset(self::EVENTS[$eventCode])) {
            throw new InvalidArgumentException('SECURITY_REPORT_INVALID');
        }
        $encoded = json_encode($evidence, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false || strlen($encoded) > 512) {
            throw new InvalidArgumentException('SECURITY_REPORT_INVALID');
        }

        DB::transaction(function () use ($application, $handle, $device, $token, $eventId, $eventCode, $evidence, $ip, $mode): void {
            $session = ApplicationClientSession::query()->where('application_id', $application->getKey())->where('token_hash', hash('sha256', $handle))->lockForUpdate()->first();
            if (! $session || trim($device) === '' || ! hash_equals((string) $session->device_id_ciphertext, trim($device))
                || ! $session->security_report_token_hash || ! hash_equals($session->security_report_token_hash, hash('sha256', $token))
                || ! $session->security_report_token_expires_at?->isFuture()) {
                throw new InvalidArgumentException('SECURITY_REPORT_INVALID');
            }
            if (ClientSecurityEvent::query()->where('application_id', $application->getKey())->where('event_id', $eventId)->exists()) {
                return;
            }
            if ($session->status !== 'active') {
                throw new InvalidArgumentException('SECURITY_REPORT_INVALID');
            }
            [$severity, $action] = self::EVENTS[$eventCode];
            if ($mode !== 'enforce') {
                $action = 'observe';
            }
            ClientSecurityEvent::query()->create(['public_id' => (string) Str::ulid(), 'application_id' => $application->getKey(), 'application_client_session_id' => $session->getKey(), 'card_id' => $session->card_id, 'application_account_id' => $session->application_account_id, 'event_id' => $eventId, 'event_code' => $eventCode, 'severity' => $severity, 'ip_hash' => hash('sha256', $ip), 'device_hash' => hash('sha256', trim($device)), 'evidence' => $evidence, 'action' => $action]);
            if ($action === 'freeze') {
                $this->deviceStates->block($application, $device, $eventCode);
                $this->ipRisk->blockForClientSecurityEvent(
                    $application,
                    $ip,
                    $device,
                    'security.report',
                    $eventCode,
                );
                $session->forceFill(['status' => 'security_blocked'])->save();
                if ($session->card_id) {
                    DB::table('cards')->where('id', $session->card_id)->update(['user_status' => 'frozen', 'updated_at' => now()]);
                }
                if ($session->application_account_id) {
                    DB::table('application_accounts')->where('id', $session->application_account_id)->update(['status' => 'frozen', 'updated_at' => now()]);
                }
            }
        }, 3);
    }

    private function mode(Application $application): string
    {
        $mode = (string) ($application->riskPolicy()->value('security_report_mode') ?? 'observe');

        return in_array($mode, ['disabled', 'observe', 'enforce'], true) ? $mode : 'observe';
    }
}
