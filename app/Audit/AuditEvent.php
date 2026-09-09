<?php

namespace App\Audit;

final readonly class AuditEvent
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public string $action,
        public string $targetType,
        public ?string $targetId = null,
        public ?int $applicationId = null,
        public array $before = [],
        public array $after = [],
        public ?string $reason = null,
        public string $result = 'success',
        public ?string $actorRole = null,
        /** 仅管理员自查诊断（如登录 trace）跳过脱敏。 */
        public bool $raw = false,
    ) {}
}
