<?php

declare(strict_types=1);

namespace App\Modules\Licenses;

use InvalidArgumentException;

/**
 * 旧版客户端竖线响应契约。
 *
 * 字段位置必须保持稳定，避免应用自定义返回时破坏既有客户端解析逻辑。
 */
final class LegacyResponseContract
{
    public const SUCCESS_INDEX = 0;
    public const CLIENT_MARKER_INDEX = 1;
    public const BUSINESS_VALUE_INDEX = 2;
    public const APP_SAFE_CODE_INDEX = 3;
    public const DAILY_SIGNATURE_INDEX = 4;
    public const EXPIRES_AT_INDEX = 6;
    public const DEVICE_ID_INDEX = 8;

    public function __construct(
        private string $successMarker = '01',
        private string $clientMarker = 'xx',
        private string $businessValue = '',
        private string $appSafeCode = '',
        private string $dailySignature = '',
        private string $expiresAt = '',
        private string $deviceId = '',
        private string $loginFlag = '1',
        private string $bindingFlag = '1'
    ) {
        foreach ($this->toArray() as $value) {
            self::assertFieldIsSafe($value);
        }
    }

    /**
     * 按旧客户端固定下标输出完整数组。
     *
     * @return array<int, string>
     */
    public function toArray(): array
    {
        return [
            self::SUCCESS_INDEX => $this->successMarker,
            self::CLIENT_MARKER_INDEX => $this->clientMarker,
            self::BUSINESS_VALUE_INDEX => $this->businessValue,
            self::APP_SAFE_CODE_INDEX => $this->appSafeCode,
            self::DAILY_SIGNATURE_INDEX => $this->dailySignature,
            5 => $this->loginFlag,
            self::EXPIRES_AT_INDEX => $this->expiresAt,
            7 => $this->bindingFlag,
            self::DEVICE_ID_INDEX => $this->deviceId,
            9 => '',
            10 => '',
        ];
    }

    /**
     * 生成与旧版 PrInfo 返回一致的竖线文本，结尾固定保留两个空字段。
     */
    public function toPipeString(): string
    {
        return implode('|', $this->toArray());
    }

    /**
     * 使用新的业务值复制响应，便于风控命中后替换 arr[2]。
     */
    public function withBusinessValue(string $businessValue): self
    {
        return new self(
            $this->successMarker,
            $this->clientMarker,
            $businessValue,
            $this->appSafeCode,
            $this->dailySignature,
            $this->expiresAt,
            $this->deviceId,
            $this->loginFlag,
            $this->bindingFlag
        );
    }

    public function __toString(): string
    {
        return $this->toPipeString();
    }

    private static function assertFieldIsSafe(string $value): void
    {
        if (str_contains($value, '|') || str_contains($value, "\r") || str_contains($value, "\n")) {
            throw new InvalidArgumentException('旧版响应字段不得包含竖线或换行符。');
        }
    }
}
