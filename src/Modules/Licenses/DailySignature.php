<?php

declare(strict_types=1);

namespace App\Modules\Licenses;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * 生成旧客户端使用的 UTC 每日固定八位签名。
 *
 * 该算法用于保持 Legacy v1 协议兼容；日期始终转换为 UTC，避免服务器时区差异。
 */
final class DailySignature
{
    private const LCG_MULTIPLIER = 1103515245;
    private const XOR_MASK = 0xA5A5A5A5;
    private const UINT32_MASK = 0xFFFFFFFF;

    public function __construct(private int $masterKey)
    {
    }

    public function generate(?DateTimeInterface $at = null): string
    {
        $utc = $this->toUtc($at ?? new DateTimeImmutable('now'));
        $ymd = (int) $utc->format('Ymd');

        // PHP 8.1 的目标运行环境为 64 位，先乘法再截取低 32 位。
        $mixed = (($ymd + $this->masterKey) * self::LCG_MULTIPLIER) & self::UINT32_MASK;
        $mixed ^= self::XOR_MASK;
        $mixed &= self::UINT32_MASK;

        $base36 = strtoupper(base_convert((string) $mixed, 10, 36));
        if (strlen($base36) < 8) {
            $padding = chr(65 + ($mixed & 0x0F));
            $base36 = str_pad($base36, 8, $padding, STR_PAD_LEFT);
        }

        return substr($base36, 0, 8);
    }

    public function verify(string $signature, ?DateTimeInterface $at = null): bool
    {
        return hash_equals($this->generate($at), strtoupper($signature));
    }

    private function toUtc(DateTimeInterface $at): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($at)->setTimezone(new DateTimeZone('UTC'));
    }
}
