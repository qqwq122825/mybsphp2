<?php

namespace App\Risk;

use App\Models\IpRiskState;
use App\Models\RiskPolicy;

final readonly class RiskDecision
{
    public function __construct(
        public string $level,
        public string $action,
        public int $attempts,
        public RiskPolicy $policy,
        public IpRiskState $state,
    ) {}

    public function shouldBlock(): bool
    {
        return $this->action === 'block';
    }

    public function shouldUseDecoy(): bool
    {
        return $this->action === 'decoy';
    }

    public function retryAfterSeconds(): ?int
    {
        if ($this->state->permanently_blocked || $this->state->blocked_until === null) {
            return null;
        }

        return max(1, (int) now()->diffInSeconds($this->state->blocked_until, false));
    }
}
