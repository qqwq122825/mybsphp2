<?php

namespace App\Security;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Models\Application;
use App\Models\ClientSecuritySubjectState;
use App\Models\GlobalSecurityBlock;
use Illuminate\Support\Facades\DB;

final class DeviceSecurityStateService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function isBlocked(Application $application, string $deviceId): bool
    {
        $normalized = trim($deviceId);
        if ($normalized === '') {
            return false;
        }
        if (GlobalSecurityBlock::deviceBlocked($normalized)) {
            return true;
        }
        $state = ClientSecuritySubjectState::query()
            ->where('application_id', $application->getKey())
            ->where('device_hash', $this->hash($normalized))
            ->first();

        return $state?->status === 'blocked' && ($state->blocked_until === null || $state->blocked_until->isFuture());
    }

    public function block(Application $application, string $deviceId, string $reason, ?string $note = null): void
    {
        $normalized = trim($deviceId);
        if ($normalized === '') {
            return;
        }

        $alreadyBlocked = $this->isBlocked($application, $normalized);
        $detail = $note ?: $this->defaultNote($reason);
        ClientSecuritySubjectState::query()->updateOrCreate(
            ['application_id' => $application->getKey(), 'device_hash' => $this->hash($normalized)],
            ['risk_score' => 100, 'status' => 'blocked', 'blocked_until' => null, 'last_event_at' => now(), 'reason_code' => $reason],
        );
        if (! $alreadyBlocked || $note !== null) {
            GlobalSecurityBlock::rememberDevice($normalized, $reason, $detail);
        }
        if (! $alreadyBlocked) {
            $this->audit->record(new AuditEvent(
                action: 'client.security_auto_block',
                targetType: 'device',
                targetId: $normalized,
                applicationId: $application->getKey(),
                before: [
                    'application' => [
                        'public_id' => $application->public_id,
                        'name' => $application->name,
                    ],
                    'device' => $normalized,
                ],
                after: [
                    'diagnostic_code' => $reason,
                    'internal_code' => $reason,
                    'internal_message' => $detail,
                    'public_code' => 'INVALID_CARD',
                    'public_message' => '卡密不存在',
                    'http_status' => 200,
                    'subject_type' => 'device',
                ],
                reason: $detail,
                result: 'failed',
                actorRole: 'client',
            ));
        }
    }

    public function observe(Application $application, string $deviceId, int $riskScore, string $reason): void
    {
        if (trim($deviceId) === '' || $riskScore <= 0) {
            return;
        }

        DB::transaction(function () use ($application, $deviceId, $riskScore, $reason): void {
            $state = ClientSecuritySubjectState::query()
                ->where('application_id', $application->getKey())
                ->where('device_hash', $this->hash($deviceId))
                ->lockForUpdate()
                ->first();

            if ($state?->status === 'blocked') {
                $state->forceFill(['last_event_at' => now()])->save();

                return;
            }

            $currentScore = $state instanceof ClientSecuritySubjectState ? $state->risk_score : 0;
            $score = min(100, $currentScore + min(50, $riskScore));
            ClientSecuritySubjectState::query()->updateOrCreate(
                ['application_id' => $application->getKey(), 'device_hash' => $this->hash($deviceId)],
                [
                    'risk_score' => $score,
                    'status' => $score >= 50 ? 'quarantined' : 'observe',
                    'blocked_until' => null,
                    'last_event_at' => now(),
                    'reason_code' => $reason,
                ],
            );
        }, 3);
    }

    private function defaultNote(string $reason): string
    {
        return match ($reason) {
            'C0' => '套卡探测达到阈值，已拉黑设备。后续任意软件登录显示卡密不存在。',
            'D0' => '客户端蜜罐事件 D0，已拉黑设备。后续任意软件登录显示卡密不存在。',
            'D1' => '客户端蜜罐事件 D1，已拉黑设备。后续任意软件登录显示卡密不存在。',
            'R0' => '客户端蜜罐事件 R0（非法能力组合），已拉黑设备。后续任意软件登录显示卡密不存在。',
            'S0' => '日签校验失败 S0，已拉黑设备。后续任意软件登录显示卡密不存在。',
            'T1' => '异常退出次数达到阈值 T1，已拉黑设备。后续任意软件登录显示卡密不存在。',
            default => '设备已拉黑，全软件登录显示卡密不存在。',
        };
    }

    private function hash(string $deviceId): string
    {
        return hash('sha256', trim($deviceId));
    }
}
