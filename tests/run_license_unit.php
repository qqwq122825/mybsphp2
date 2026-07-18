<?php

declare(strict_types=1);

use App\Modules\Licenses\DailySignature;
use App\Modules\Licenses\LegacyResponseContract;
use App\Modules\Licenses\RiskDecision;
use App\Modules\Licenses\RiskEngine;
use App\Modules\Licenses\RiskPolicy;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * 不依赖测试框架的最小断言工具。
 */
function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            sprintf(
                "%s\n期望: %s\n实际: %s",
                $message,
                var_export($expected, true),
                var_export($actual, true)
            )
        );
    }
}

function assertTrueValue(bool $actual, string $message): void
{
    assertSameValue(true, $actual, $message);
}

$emptyBusinessResponse = new LegacyResponseContract(
    businessValue: '',
    appSafeCode: 'NONCE-1',
    dailySignature: 'ABCDEFGH',
    expiresAt: '2026-08-18 12:00:00',
    deviceId: 'DEVICE-1'
);
assertSameValue(
    '01|xx||NONCE-1|ABCDEFGH|1|2026-08-18 12:00:00|1|DEVICE-1||',
    $emptyBusinessResponse->toPipeString(),
    'arr[2] 为空时仍须保留固定位置'
);
assertSameValue('', $emptyBusinessResponse->toArray()[2], '业务值固定在 arr[2]');
assertSameValue('NONCE-1', $emptyBusinessResponse->toArray()[3], 'appsafecode 固定在 arr[3]');
assertSameValue('ABCDEFGH', $emptyBusinessResponse->toArray()[4], '每日签名固定在 arr[4]');
assertSameValue('2026-08-18 12:00:00', $emptyBusinessResponse->toArray()[6], '到期时间固定在 arr[6]');
assertSameValue('DEVICE-1', $emptyBusinessResponse->toArray()[8], '设备号固定在 arr[8]');

$responseWithDecoy = $emptyBusinessResponse->withBusinessValue('0xBAD0C0DE');
assertSameValue('0xBAD0C0DE', $responseWithDecoy->toArray()[2], '可单独替换 arr[2]');
assertSameValue('', $emptyBusinessResponse->toArray()[2], '复制响应不得修改原对象');

$dailySignature = new DailySignature(123456789);
$utcMoment = new DateTimeImmutable('2026-07-18 00:30:00', new DateTimeZone('UTC'));
$sameMomentInShanghai = $utcMoment->setTimezone(new DateTimeZone('Asia/Shanghai'));
$signature = $dailySignature->generate($utcMoment);
assertSameValue(8, strlen($signature), '每日签名固定为八位');
assertSameValue($signature, $dailySignature->generate($sameMomentInShanghai), '同一时刻跨时区签名应相同');
assertTrueValue($dailySignature->verify(strtolower($signature), $utcMoment), '签名校验兼容小写输入');
assertSameValue(
    false,
    $dailySignature->verify('00000000', $utcMoment),
    '错误签名不应通过校验'
);

$policy = new RiskPolicy(
    enabled: true,
    windowSeconds: 60,
    observeLoginThreshold: 3,
    decoyLoginThreshold: 5,
    blockLoginThreshold: 8,
    observeDeviceThreshold: 2,
    decoyDeviceThreshold: 3,
    blockDeviceThreshold: 5,
    decoyValue: 'DECOY_GWROLD'
);
$engine = new RiskEngine($policy);

$normal = $engine->decide(2, 1);
assertSameValue(RiskDecision::OBSERVE, $normal->action(), '低风险请求处于观察放行状态');
assertSameValue(false, $normal->isTriggered(), '低风险请求不标记为触发');

$observed = $engine->decide(3, 1);
assertTrueValue($observed->shouldObserve(), '达到第一层阈值进入 observe');
assertTrueValue($observed->isTriggered(), '达到第一层阈值应标记触发');

$decoy = $engine->decide(5, 1);
assertTrueValue($decoy->shouldUseDecoy(), '达到第二层阈值进入 decoy');
assertSameValue('DECOY_GWROLD', $decoy->businessValue('REAL_GWROLD'), 'decoy 替换真实 arr[2] 值');

$blocked = $engine->decide(2, 5);
assertTrueValue($blocked->shouldBlock(), '设备数达到最高阈值进入 block');

$now = 2_000_000_000;
$eventDecision = $engine->decideFromEvents([
    ['occurred_at' => $now - 70, 'device_id' => 'OUTSIDE'],
    ['occurred_at' => $now - 50, 'device_id' => 'A'],
    ['occurred_at' => $now - 20, 'device_id' => 'A'],
    ['occurred_at' => $now - 10, 'device_id' => 'B'],
    ['occurred_at' => $now + 1, 'device_id' => 'FUTURE'],
], $now);
assertSameValue(3, $eventDecision->recentLoginCount(), '只统计当前时间窗口内的事件');
assertSameValue(2, $eventDecision->recentDeviceCount(), '设备数按非空标识去重');
assertTrueValue($eventDecision->shouldObserve(), '原始事件聚合后正确进入 observe');

$disabledEngine = new RiskEngine(new RiskPolicy(enabled: false));
$disabled = $disabledEngine->decide(999, 999);
assertTrueValue($disabled->shouldObserve(), '关闭风控后保持观察放行状态');
assertSameValue(false, $disabled->isTriggered(), '关闭风控后不标记触发');

echo "License unit tests passed.\n";
