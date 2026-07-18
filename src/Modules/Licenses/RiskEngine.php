<?php

declare(strict_types=1);

namespace App\Modules\Licenses;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * 纯内存登录风控判定器。
 *
 * 本类只处理已提供的登录事件或聚合数量，不负责读取日志、数据库或网络。
 */
final class RiskEngine
{
    public function __construct(private RiskPolicy $policy)
    {
    }

    /**
     * 使用已在最近窗口内聚合好的登录次数与去重设备数判定。
     */
    public function decide(int $recentLoginCount, int $recentDeviceCount): RiskDecision
    {
        if ($recentLoginCount < 0 || $recentDeviceCount < 0) {
            throw new InvalidArgumentException('最近登录次数和设备数不得为负数。');
        }

        if (!$this->policy->enabled()) {
            return RiskDecision::observe(
                false,
                ['policy_disabled'],
                $recentLoginCount,
                $recentDeviceCount
            );
        }

        $blockReasons = $this->thresholdReasons(
            $recentLoginCount,
            $recentDeviceCount,
            $this->policy->blockLoginThreshold(),
            $this->policy->blockDeviceThreshold(),
            'block'
        );
        if ($blockReasons !== []) {
            return RiskDecision::block($blockReasons, $recentLoginCount, $recentDeviceCount);
        }

        $decoyReasons = $this->thresholdReasons(
            $recentLoginCount,
            $recentDeviceCount,
            $this->policy->decoyLoginThreshold(),
            $this->policy->decoyDeviceThreshold(),
            'decoy'
        );
        if ($decoyReasons !== []) {
            return RiskDecision::decoy(
                $this->policy->decoyValue(),
                $decoyReasons,
                $recentLoginCount,
                $recentDeviceCount
            );
        }

        $observeReasons = $this->thresholdReasons(
            $recentLoginCount,
            $recentDeviceCount,
            $this->policy->observeLoginThreshold(),
            $this->policy->observeDeviceThreshold(),
            'observe'
        );

        return RiskDecision::observe(
            $observeReasons !== [],
            $observeReasons === [] ? ['below_threshold'] : $observeReasons,
            $recentLoginCount,
            $recentDeviceCount
        );
    }

    /**
     * 从原始事件中筛选策略时间窗口，并计算登录次数及去重设备数。
     *
     * 每个事件格式：
     * - occurred_at: Unix 时间戳或 DateTimeInterface
     * - device_id: 设备标识；空标识参与登录计数但不计入设备数
     *
     * @param iterable<array{occurred_at: int|DateTimeInterface, device_id?: string}> $events
     */
    public function decideFromEvents(iterable $events, ?int $nowTimestamp = null): RiskDecision
    {
        $nowTimestamp ??= time();
        $windowStart = $nowTimestamp - $this->policy->windowSeconds() + 1;
        $loginCount = 0;
        $devices = [];

        foreach ($events as $event) {
            if (!is_array($event) || !array_key_exists('occurred_at', $event)) {
                throw new InvalidArgumentException('登录事件必须包含 occurred_at。');
            }

            $occurredAt = $event['occurred_at'];
            if ($occurredAt instanceof DateTimeInterface) {
                $timestamp = $occurredAt->getTimestamp();
            } elseif (is_int($occurredAt)) {
                $timestamp = $occurredAt;
            } else {
                throw new InvalidArgumentException('occurred_at 必须是 Unix 时间戳或 DateTimeInterface。');
            }

            // 忽略窗口外和未来事件，避免异常客户端时间污染当前判定。
            if ($timestamp < $windowStart || $timestamp > $nowTimestamp) {
                continue;
            }

            ++$loginCount;
            $deviceId = $event['device_id'] ?? '';
            if (!is_string($deviceId)) {
                throw new InvalidArgumentException('device_id 必须是字符串。');
            }
            if ($deviceId !== '') {
                $devices[$deviceId] = true;
            }
        }

        return $this->decide($loginCount, count($devices));
    }

    /**
     * @return list<string>
     */
    private function thresholdReasons(
        int $loginCount,
        int $deviceCount,
        int $loginThreshold,
        int $deviceThreshold,
        string $level
    ): array {
        $reasons = [];

        if ($loginCount >= $loginThreshold) {
            $reasons[] = sprintf('login_count_%s', $level);
        }
        if ($deviceCount >= $deviceThreshold) {
            $reasons[] = sprintf('device_count_%s', $level);
        }

        return $reasons;
    }
}
