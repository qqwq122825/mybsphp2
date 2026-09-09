<?php

namespace App\Security;

final readonly class CanaryIntegrityHint
{
    /** @var list<int> */
    public const DECOY_SLOTS = [1, 2, 3, 4, 5, 6];

    /** @var list<int> */
    public const REAL_SLOTS = [8, 9, 10, 11, 12, 13, 14, 15, 16];

    /**
     * @param  list<int>  $slots
     */
    public function __construct(
        public bool $present,
        public bool $decoyConfirmed,
        public bool $integrityNoted,
        public int $observeCount,
        public int $confirmCount,
        public int $decoyConfirmCount,
        public array $slots,
        public int $lastSlot,
        public string $lastKind,
        public bool $lastOutside,
        public bool $crcMismatch,
    ) {}

    public static function empty(): self
    {
        return new self(false, false, false, 0, 0, 0, [], 0, '', false, false);
    }

    public static function parse(string $raw): self
    {
        $raw = trim($raw);
        if ($raw === '' || strlen($raw) > 512) {
            return self::empty();
        }

        $data = json_decode($raw, true);
        if (! is_array($data) || array_is_list($data)) {
            return self::empty();
        }

        $observe = self::boundedInt($data['o'] ?? 0);
        $confirm = self::boundedInt($data['c'] ?? 0);
        $decoyConfirm = self::boundedInt($data['dc'] ?? 0);
        $slots = self::boundedSlots($data['s'] ?? []);
        $lastSlot = self::boundedInt($data['ls'] ?? 0, 23);
        $lastKind = self::boundedKind($data['lk'] ?? '');
        $lastOutside = (int) ($data['lo'] ?? 0) === 1;
        // Compact login signal: cm=1 means the locally measured executable
        // digest did not match the embedded release record.  The digest itself
        // is intentionally never accepted from the client.
        $crcMismatch = self::truthy($data['cm'] ?? false);
        $decoySlots = array_values(array_intersect($slots, self::DECOY_SLOTS));
        $decoyConfirmed = $decoyConfirm > 0 || ($confirm > 0 && $decoySlots !== []);
        $integrityNoted = $observe > 0 || $confirm > 0 || $slots !== [] || $crcMismatch;

        if (! $integrityNoted) {
            return self::empty();
        }

        return new self(
            true,
            $decoyConfirmed,
            $integrityNoted,
            $observe,
            $confirm,
            $decoyConfirm,
            $slots,
            $lastSlot,
            $lastKind,
            $lastOutside,
            $crcMismatch,
        );
    }

    public function hasRealSlot(): bool
    {
        return array_intersect($this->slots, self::REAL_SLOTS) !== [];
    }

    /** @return array<string, mixed> */
    public function evidence(string $source): array
    {
        return [
            'source' => $source,
            'v' => 1,
            'o' => $this->observeCount,
            'c' => $this->confirmCount,
            'dc' => $this->decoyConfirmCount,
            's' => $this->slots,
            'ls' => $this->lastSlot,
            'lk' => $this->lastKind,
            'lo' => $this->lastOutside ? 1 : 0,
            'decoy' => $this->decoyConfirmed,
            'cm' => $this->crcMismatch ? 1 : 0,
        ];
    }

    public function blockNote(): string
    {
        $slots = $this->slots === [] ? '未标注' : implode(',', $this->slots);
        $kind = $this->lastKind !== '' ? $this->lastKind : 'unknown';

        $crc = $this->crcMismatch ? '，CRC 失配' : '';
        return '登录 ih：诱饵槽确认被 Hook（dc='.$this->decoyConfirmCount
            .' c='.$this->confirmCount.' 槽 '.$slots.' kind='.$kind.$crc.'），已拉黑设备。后续任意软件登录显示卡密不存在。';
    }

    public function observeNote(): string
    {
        $slots = $this->slots === [] ? '未标注' : implode(',', $this->slots);

        $crc = $this->crcMismatch ? '，CRC 失配' : '';
        return '登录 ih：真槽完整性异常仅观察（o='.$this->observeCount
            .' c='.$this->confirmCount.' 槽 '.$slots.$crc.'），不自动拉黑。';
    }

    private static function boundedInt(mixed $value, int $maximum = 100000): int
    {
        if (! is_numeric($value)) {
            return 0;
        }

        return max(0, min($maximum, (int) $value));
    }

    private static function truthy(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }

    /**
     * @return list<int>
     */
    private static function boundedSlots(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $slots = [];
        foreach (array_slice($value, 0, 24) as $item) {
            if (! is_numeric($item)) {
                continue;
            }
            $slot = (int) $item;
            if ($slot >= 1 && $slot <= 23) {
                $slots[] = $slot;
            }
        }

        return array_values(array_unique($slots));
    }

    private static function boundedKind(mixed $value): string
    {
        $kind = is_string($value) ? trim($value) : '';
        if ($kind === '') {
            return '';
        }
        if (strlen($kind) > 12) {
            $kind = substr($kind, 0, 12);
        }

        return preg_match('/^[A-Za-z0-9_-]+$/', $kind) === 1 ? $kind : 'unknown';
    }
}
