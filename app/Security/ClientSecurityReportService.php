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
        private readonly CanaryIntegrityHintService $canaryHints,
    ) {}

    private const EVENTS = [
        'D0' => ['high', 'freeze'], 'D1' => ['high', 'freeze'], 'R0' => ['high', 'freeze'],
        'S0' => ['high', 'freeze'],
        'I0' => ['medium', 'observe'], 'I1' => ['medium', 'observe'], 'CRC' => ['high', 'freeze'], 'T0' => ['low', 'observe'],
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

    public function report(Application $application, string $handle, string $device, string $token, string $eventId, string $eventCode, mixed $evidence, string $ip): string
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

        return DB::transaction(function () use ($application, $handle, $device, $token, $eventId, $eventCode, $evidence, $ip, $mode): string {
            $session = ApplicationClientSession::query()->where('application_id', $application->getKey())->where('token_hash', hash('sha256', $handle))->lockForUpdate()->first();
            if (! $session || trim($device) === '' || ! hash_equals((string) $session->device_id_ciphertext, trim($device))
                || ! $session->security_report_token_hash || ! hash_equals($session->security_report_token_hash, hash('sha256', $token))
                || ! $session->security_report_token_expires_at?->isFuture()) {
                throw new InvalidArgumentException('SECURITY_REPORT_INVALID');
            }
            $existing = ClientSecurityEvent::query()->where('application_id', $application->getKey())->where('event_id', $eventId)->first();
            if ($existing) {
                if ($existing->device_hash !== hash('sha256', trim($device)) || $existing->event_code !== $eventCode) {
                    throw new InvalidArgumentException('SECURITY_REPORT_INVALID');
                }

                return (string) $existing->action;
            }
            if ($session->status !== 'active') {
                throw new InvalidArgumentException('SECURITY_REPORT_INVALID');
            }
            [$severity, $mappedAction] = self::EVENTS[$eventCode];
            $evidenceArray = is_array($evidence) ? $evidence : [];
            $classified = $this->canaryHints->classifyReport($eventCode, $evidenceArray);
            $action = $this->canaryHints->shouldFreezeReport($eventCode, $mappedAction, $mode, $evidenceArray)
                ? 'freeze'
                : 'observe';
            if ($action === 'freeze') {
                $severity = 'high';
            }
            ClientSecurityEvent::query()->create(['public_id' => (string) Str::ulid(), 'application_id' => $application->getKey(), 'application_client_session_id' => $session->getKey(), 'card_id' => $session->card_id, 'application_account_id' => $session->application_account_id, 'event_id' => $eventId, 'event_code' => $eventCode, 'severity' => $severity, 'ip_hash' => hash('sha256', $ip), 'device_hash' => hash('sha256', trim($device)), 'evidence' => $evidence, 'action' => $action, 'created_at' => now()]);
            if ($action === 'freeze') {
                $this->deviceStates->block($application, $device, $eventCode, $classified['note']);
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

            return $action;
        }, 3);
    }

    private function mode(Application $application): string
    {
        $mode = (string) ($application->riskPolicy()->value('security_report_mode') ?? 'observe');

        return in_array($mode, ['disabled', 'observe', 'enforce'], true) ? $mode : 'observe';
    }
}
