<?php

namespace App\Audit;

use App\Models\AuditLog;
use App\Support\SensitiveDataRedactor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditLogger
{
    public function __construct(
        private readonly SensitiveDataRedactor $redactor,
    ) {}

    public function record(AuditEvent $event, ?Request $request = null): AuditLog
    {
        $request ??= request();
        $requestId = $request->attributes->get('request_id');
        $userAgent = $request->userAgent();

        if (! is_string($requestId) || ! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $requestId)) {
            $requestId = (string) Str::ulid();
        }

        return AuditLog::query()->create([
            'public_id' => (string) Str::ulid(),
            'actor_user_id' => $request->user()?->getAuthIdentifier(),
            'actor_role_snapshot' => $event->actorRole,
            'action' => $event->action,
            'target_type' => $event->targetType,
            'target_id' => $event->targetId,
            'application_id' => $event->applicationId,
            'request_id' => $requestId,
            'ip' => $request->ip(),
            'user_agent_hash' => is_string($userAgent) ? hash('sha256', $userAgent) : null,
            'before_data' => $event->before === [] ? null : $this->redactor->redact($event->before),
            'after_data' => $event->after === [] ? null : $this->redactor->redact($event->after),
            'reason' => is_string($event->reason)
                ? $this->redactor->redactMessage($event->reason)
                : null,
            'result' => $event->result,
        ]);
    }
}
