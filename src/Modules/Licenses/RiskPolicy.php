<?php

declare(strict_types=1);

namespace App\Modules\Licenses;

use InvalidArgumentException;

/**
 * 登录风控策略值对象。
 *
 * 登录次数和设备数分别使用 observe、decoy、block 三级阈值；
 * 任意一个维度达到阈值即进入对应级别。
 */
final class RiskPolicy
{
    public function __construct(
        private bool $enabled = true,
        private int $windowSeconds = 60,
        private int $observeLoginThreshold = 3,
        private int $decoyLoginThreshold = 6,
        private int $blockLoginThreshold = 12,
        private int $observeDeviceThreshold = 2,
        private int $decoyDeviceThreshold = 3,
        private int $blockDeviceThreshold = 5,
        private string $decoyValue = '0x00000000'
    ) {
        if ($this->windowSeconds < 1) {
            throw new InvalidArgumentException('风控时间窗口必须大于零。');
        }

        self::assertThresholds(
            $this->observeLoginThreshold,
            $this->decoyLoginThreshold,
            $this->blockLoginThreshold,
            '登录次数'
        );
        self::assertThresholds(
            $this->observeDeviceThreshold,
            $this->decoyDeviceThreshold,
            $this->blockDeviceThreshold,
            '设备数'
        );
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function windowSeconds(): int
    {
        return $this->windowSeconds;
    }

    public function observeLoginThreshold(): int
    {
        return $this->observeLoginThreshold;
    }

    public function decoyLoginThreshold(): int
    {
        return $this->decoyLoginThreshold;
    }

    public function blockLoginThreshold(): int
    {
        return $this->blockLoginThreshold;
    }

    public function observeDeviceThreshold(): int
    {
        return $this->observeDeviceThreshold;
    }

    public function decoyDeviceThreshold(): int
    {
        return $this->decoyDeviceThreshold;
    }

    public function blockDeviceThreshold(): int
    {
        return $this->blockDeviceThreshold;
    }

    public function decoyValue(): string
    {
        return $this->decoyValue;
    }

    private static function assertThresholds(int $observe, int $decoy, int $block, string $label): void
    {
        if ($observe < 1 || $decoy < $observe || $block < $decoy) {
            throw new InvalidArgumentException(
                sprintf('%s阈值必须满足 1 <= observe <= decoy <= block。', $label)
            );
        }
    }
}
