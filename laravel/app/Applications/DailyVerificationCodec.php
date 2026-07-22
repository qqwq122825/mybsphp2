<?php

namespace App\Applications;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class DailyVerificationCodec
{
    private const MASTER_KEY = 123456789;
    private const LCG = 1103515245;
    private const MIX = 0xA5A5A5A5;
    private const SALT_MIX = 0x9E3779B1;
    private const UINT32 = 0xFFFFFFFF;

    public function dailySignature(?DateTimeInterface $at = null): string
    {
        $mixed = (((int) $this->utc($at)->format('Ymd') + self::MASTER_KEY) * self::LCG) & self::UINT32;
        $mixed = ($mixed ^ self::MIX) & self::UINT32;
        $base36 = strtoupper(base_convert((string) $mixed, 10, 36));
        if (strlen($base36) < 8) {
            $base36 = str_pad($base36, 8, chr(65 + ($mixed & 0x0F)), STR_PAD_LEFT);
        }

        return substr($base36, 0, 8);
    }

    public function encodePayload(string $payload, ?DateTimeInterface $at = null): string
    {
        $value = trim($payload);
        if (preg_match('/^0x([0-9a-f]{1,8})$/i', $value, $matches)) {
            return $this->encryptUInt32((int) hexdec($matches[1]), $at);
        }
        if (preg_match('/^[0-9]{1,10}$/', $value) && (int) $value <= self::UINT32) {
            return $this->encryptUInt32((int) $value, $at);
        }

        return $payload;
    }

    public function encryptUInt32(int $value, ?DateTimeInterface $at = null, ?int $salt = null): string
    {
        $salt ??= random_int(0, 255);
        $key = $this->key($at, $salt);
        $plain = [($value >> 24) & 0xFF, ($value >> 16) & 0xFF, ($value >> 8) & 0xFF, $value & 0xFF];
        $encrypted = [];
        for ($i = 0; $i < 4; $i++) {
            $a = ($key >> (8 * $i)) & 0xFF;
            $b = ($key >> (8 * (3 - $i))) & 0xFF;
            $c = ($key >> (16 + $i * 4)) & 0xFF;
            $encrypted[$i] = $plain[$i] ^ $c ^ $b ^ $a;
        }

        return sprintf('%02X%02X%02X%02X%02X', $salt, ...$encrypted);
    }

    public function decryptUInt32(string $ciphertext, ?DateTimeInterface $at = null): ?int
    {
        if (! preg_match('/^[0-9A-Fa-f]{10}$/', $ciphertext)) return null;
        $bytes = array_map('hexdec', str_split($ciphertext, 2));
        $key = $this->key($at, $bytes[0]);
        $plain = [];
        for ($i = 0; $i < 4; $i++) {
            $a = ($key >> (8 * $i)) & 0xFF;
            $b = ($key >> (8 * (3 - $i))) & 0xFF;
            $c = ($key >> (16 + $i * 4)) & 0xFF;
            $plain[$i] = $bytes[$i + 1] ^ $c ^ $b ^ $a;
        }

        return (($plain[0] << 24) | ($plain[1] << 16) | ($plain[2] << 8) | $plain[3]) & self::UINT32;
    }

    private function key(?DateTimeInterface $at, int $salt): int
    {
        $ymd = (int) $this->utc($at)->format('Ymd');
        $key = (($ymd + self::MASTER_KEY) * self::LCG) & self::UINT32;
        $key = ($key ^ self::MIX ^ (($salt * self::SALT_MIX) & self::UINT32)) & self::UINT32;
        return $key;
    }

    private function utc(?DateTimeInterface $at): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($at ?? new DateTimeImmutable('now'))
            ->setTimezone(new DateTimeZone('UTC'));
    }
}
