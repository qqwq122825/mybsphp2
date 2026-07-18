<?php

declare(strict_types=1);

namespace App\Modules\Licenses;

/**
 * 一次登录风控判定结果。
 */
final class RiskDecision
{
    public const OBSERVE = 'observe';
    public const DECOY = 'decoy';
    public const BLOCK = 'block';

    /**
     * @param list<string> $reasons 稳定的原因代码，供日志和后台展示层转换。
     */
    private function __construct(
        private string $action,
        private bool $triggered,
        private array $reasons,
        private int $recentLoginCount,
        private int $recentDeviceCount,
        private ?string $decoyValue = null
    ) {
    }

    /**
     * @param list<string> $reasons
     */
    public static function observe(
        bool $triggered,
        array $reasons,
        int $recentLoginCount,
        int $recentDeviceCount
    ): self {
        return new self(
            self::OBSERVE,
            $triggered,
            array_values($reasons),
            $recentLoginCount,
            $recentDeviceCount
        );
    }

    /**
     * @param list<string> $reasons
     */
    public static function decoy(
        string $decoyValue,
        array $reasons,
        int $recentLoginCount,
        int $recentDeviceCount
    ): self {
        return new self(
            self::DECOY,
            true,
            array_values($reasons),
            $recentLoginCount,
            $recentDeviceCount,
            $decoyValue
        );
    }

    /**
     * @param list<string> $reasons
     */
    public static function block(array $reasons, int $recentLoginCount, int $recentDeviceCount): self
    {
        return new self(
            self::BLOCK,
            true,
            array_values($reasons),
            $recentLoginCount,
            $recentDeviceCount
        );
    }

    public function action(): string
    {
        return $this->action;
    }

    public function isTriggered(): bool
    {
        return $this->triggered;
    }

    public function shouldObserve(): bool
    {
        return $this->action === self::OBSERVE;
    }

    public function shouldUseDecoy(): bool
    {
        return $this->action === self::DECOY;
    }

    public function shouldBlock(): bool
    {
        return $this->action === self::BLOCK;
    }

    /**
     * @return list<string>
     */
    public function reasons(): array
    {
        return $this->reasons;
    }

    public function recentLoginCount(): int
    {
        return $this->recentLoginCount;
    }

    public function recentDeviceCount(): int
    {
        return $this->recentDeviceCount;
    }

    public function decoyValue(): ?string
    {
        return $this->decoyValue;
    }

    /**
     * 普通/观察状态保留真实值；诱饵状态替换为策略值。
     */
    public function businessValue(string $realValue): string
    {
        return $this->shouldUseDecoy() ? (string) $this->decoyValue : $realValue;
    }
}
