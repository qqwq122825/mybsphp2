<?php

namespace Tests\Unit;

use App\Security\CanaryIntegrityHint;
use PHPUnit\Framework\TestCase;

class CanaryIntegrityHintTest extends TestCase
{
    public function test_empty_or_invalid_payload_is_ignored(): void
    {
        $this->assertFalse(CanaryIntegrityHint::parse('')->present);
        $this->assertFalse(CanaryIntegrityHint::parse('{')->present);
        $this->assertFalse(CanaryIntegrityHint::parse('[]')->present);
        $this->assertFalse(CanaryIntegrityHint::parse('{"v":1,"o":0,"c":0,"dc":0,"s":[]}')->present);
    }

    public function test_decoy_confirm_count_is_high_confidence(): void
    {
        $hint = CanaryIntegrityHint::parse(json_encode([
            'v' => 1, 'o' => 2, 'c' => 1, 'dc' => 1, 's' => [1, 5, 9], 'ls' => 5, 'lk' => 'imp', 'lo' => 0,
        ], JSON_THROW_ON_ERROR));

        $this->assertTrue($hint->present);
        $this->assertTrue($hint->decoyConfirmed);
        $this->assertTrue($hint->hasRealSlot());
        $this->assertSame([1, 5, 9], $hint->slots);
        $this->assertStringContainsString('诱饵槽确认', $hint->blockNote());
        $this->assertSame('login.ih', $hint->evidence('login.ih')['source']);
    }

    public function test_decoy_slot_with_confirm_is_high_confidence_even_without_dc(): void
    {
        $hint = CanaryIntegrityHint::parse(json_encode([
            'v' => 1, 'o' => 1, 'c' => 1, 'dc' => 0, 's' => [3], 'ls' => 3, 'lk' => 'imp', 'lo' => 1,
        ], JSON_THROW_ON_ERROR));

        $this->assertTrue($hint->decoyConfirmed);
    }

    public function test_real_slot_only_stays_observe(): void
    {
        $hint = CanaryIntegrityHint::parse(json_encode([
            'v' => 1, 'o' => 2, 'c' => 1, 'dc' => 0, 's' => [9, 12], 'ls' => 9, 'lk' => 'code', 'lo' => 1,
        ], JSON_THROW_ON_ERROR));

        $this->assertTrue($hint->present);
        $this->assertFalse($hint->decoyConfirmed);
        $this->assertTrue($hint->hasRealSlot());
        $this->assertStringContainsString('真槽完整性异常仅观察', $hint->observeNote());
    }
}
