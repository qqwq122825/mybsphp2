<?php

namespace Tests\Unit;

use App\Applications\DailyVerificationCodec;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class DailyVerificationCodecTest extends TestCase
{
    public function test_daily_signature_and_secondary_cipher_match_uiinitializer_algorithm(): void
    {
        $codec = new DailyVerificationCodec();
        $at = new DateTimeImmutable('2026-07-19 12:00:00', new DateTimeZone('UTC'));
        $signature = $codec->dailySignature($at);
        $ciphertext = $codec->encryptUInt32(0x00100000, $at, 0x5A);

        $this->assertSame(8, strlen($signature));
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{8}$/', $signature);
        $this->assertSame('5A', substr($ciphertext, 0, 2));
        $this->assertSame(0x00100000, $codec->decryptUInt32($ciphertext, $at));
        $this->assertSame(0x00100000, $codec->decryptUInt32($codec->encodePayload('0x00100000', $at), $at));
        $this->assertSame('plain-text', $codec->encodePayload('plain-text', $at));
    }
}
