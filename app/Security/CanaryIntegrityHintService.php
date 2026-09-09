<?php

namespace App\Security;

use App\Models\Application;
use App\Models\ClientSecurityEvent;
use App\Risk\IpRiskControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CanaryIntegrityHintService
{
    public function __construct(
        private readonly DeviceSecurityStateService $deviceStates,
        private readonly IpRiskControlService $ipRisk,
    ) {}

    /**
     * @return array{should_block: bool, public_code: string, public_message: string, internal_code: string, internal_message: string}
     */
    public function inspectLogin(
        Application $application,
        string $device,
        string $rawHint,
        string $ip,
    ): array {
        $noop = [
            'should_block' => false,
            'public_code' => '',
            'public_message' => '',
            'internal_code' => '',
            'internal_message' => '',
        ];
        $mode = $this->mode($application);
        $device = trim($device);
        if ($mode === 'disabled' || $device === '') {
            return $noop;
        }

        $hint = CanaryIntegrityHint::parse($rawHint);
        if (! $hint->present) {
            return $noop;
        }

        $shouldBlock = $mode === 'enforce' && ($hint->decoyConfirmed || $hint->crcMismatch);
        $action = $shouldBlock ? 'freeze' : 'observe';
        $eventCode = $hint->decoyConfirmed ? 'D0' : ($hint->crcMismatch ? 'CRC' : 'I0');
        $message = $shouldBlock ? $hint->blockNote() : $hint->observeNote();

        DB::transaction(function () use ($application, $device, $ip, $hint, $shouldBlock, $action, $eventCode, $message): void {
            $eventId = 'ih-'.substr(hash('sha256', $application->getKey().'|'.hash('sha256', $device)), 0, 29);
            $event = ClientSecurityEvent::query()
                ->where('application_id', $application->getKey())
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();
            $values = [
                'application_id' => $application->getKey(),
                'application_client_session_id' => null,
                'card_id' => null,
                'application_account_id' => null,
                'event_id' => $eventId,
                'event_code' => $eventCode,
                'severity' => $hint->decoyConfirmed ? 'high' : 'medium',
                'ip_hash' => hash('sha256', $ip),
                'device_hash' => hash('sha256', $device),
                'evidence' => $hint->evidence('login.ih'),
                'action' => $action,
                'created_at' => now(),
            ];
            if ($event instanceof ClientSecurityEvent) {
                if ($shouldBlock && $event->action !== 'freeze') {
                    $event->forceFill($values)->save();
                } else {
                    $event->forceFill([
                        'evidence' => $values['evidence'],
                        'severity' => $values['severity'],
                        'ip_hash' => $values['ip_hash'],
                    ])->save();
                }
            } else {
                ClientSecurityEvent::query()->create([
                    'public_id' => (string) Str::ulid(),
                    ...$values,
                ]);
            }

            if ($shouldBlock) {
                $this->deviceStates->block($application, $device, $eventCode, $message);
                $this->ipRisk->blockForClientSecurityEvent(
                    $application,
                    $ip,
                    $device,
                    'login.ih',
                    $eventCode,
                );
            } else {
                $this->deviceStates->observe($application, $device, $hint->decoyConfirmed ? 20 : 8, $eventCode);
            }
        }, 3);

        return [
            'should_block' => $shouldBlock,
            'public_code' => 'INVALID_CARD',
            'public_message' => '卡密不存在',
            'internal_code' => $hint->crcMismatch && ! $hint->decoyConfirmed ? 'CRC_BLOCKED' : 'IH_BLOCKED',
            'internal_message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $evidence
     * @return array{decoy: bool, real_slot_only: bool, slot: int, note: string}
     */
    public function classifyReport(string $eventCode, array $evidence): array
    {
        $slot = 0;
        if (is_numeric($evidence['slot'] ?? null)) {
            $slot = (int) $evidence['slot'];
        }
        $decoy = $this->truthy($evidence['decoy'] ?? null)
            || $this->truthy($evidence['invoked'] ?? null)
            || in_array($slot, CanaryIntegrityHint::DECOY_SLOTS, true);
        $realSlot = in_array($slot, CanaryIntegrityHint::REAL_SLOTS, true);
        $kind = is_string($evidence['kind'] ?? null) ? (string) $evidence['kind'] : '';
        $persona = is_string($evidence['persona'] ?? null) ? (string) $evidence['persona'] : '';
        $detail = [];
        if ($slot > 0) {
            $detail[] = 'slot '.$slot;
        }
        if ($kind !== '') {
            $detail[] = 'kind='.$kind;
        }
        if ($persona !== '') {
            $detail[] = 'persona='.$persona;
        }
        if ($this->truthy($evidence['invoked'] ?? null)) {
            $detail[] = '诱饵方法被调用';
        }
        $suffix = $detail === [] ? '' : '（'.implode(' ', $detail).'）';

        if ($decoy) {
            $note = 'security.report '.$eventCode.'：诱饵命中'.$suffix.'，已拉黑设备。后续任意软件登录显示卡密不存在。';
        } elseif ($realSlot) {
            $note = 'security.report '.$eventCode.'：真槽完整性信号'.$suffix.'，先观察不自动拉黑。';
        } else {
            $note = 'security.report '.$eventCode.'：高置信蜜罐事件'.$suffix.'，已拉黑设备。后续任意软件登录显示卡密不存在。';
        }

        return [
            'decoy' => $decoy,
            'real_slot_only' => $realSlot && ! $decoy,
            'slot' => $slot,
            'note' => $note,
        ];
    }

    public function shouldFreezeReport(string $eventCode, string $mappedAction, string $mode, array $evidence): bool
    {
        if ($mode !== 'enforce' || $mappedAction !== 'freeze') {
            return false;
        }
        if ($eventCode !== 'D0') {
            return true;
        }

        return ! $this->classifyReport($eventCode, $evidence)['real_slot_only'];
    }

    private function mode(Application $application): string
    {
        $mode = (string) ($application->riskPolicy()->value('security_report_mode') ?? 'observe');

        return in_array($mode, ['disabled', 'observe', 'enforce'], true) ? $mode : 'observe';
    }

    private function truthy(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
