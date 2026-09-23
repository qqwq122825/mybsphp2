<?php

namespace Tests\Unit;

use App\Models\ApplicationAccount;
use App\Models\Card;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SoftwareUserStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-23 10:00:00'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public static function cardStates(): array
    {
        return [
            'active' => [[], 'active', '正常'],
            'card deleted but user active' => [['status' => 'revoked'], 'revoked', '已撤销'],
            'user revoked but card active' => [['user_status' => 'revoked'], 'revoked', '已撤销'],
            'retained revocation timestamp' => [['revoked_at' => '2026-09-22 12:00:00'], 'revoked', '已撤销'],
            'revocation precedes freeze' => [['status' => 'revoked', 'user_status' => 'frozen'], 'revoked', '已撤销'],
            'used card freeze is independent' => [['card_is_frozen' => true], 'active', '正常'],
            'user freeze' => [['user_status' => 'frozen'], 'frozen', '已冻结'],
            'expiry boundary' => [['expires_at' => '2026-09-23 10:00:00'], 'expired', '已到期'],
            'expired state' => [['status' => 'expired'], 'expired', '已到期'],
            'revocation precedes expiry' => [['status' => 'revoked', 'expires_at' => '2026-09-22 10:00:00'], 'revoked', '已撤销'],
            'no expiry stays a platform user' => [['expires_at' => null], 'active', '正常'],
            'unknown is not normal' => [['status' => 'unexpected'], 'unexpected', '未知状态'],
        ];
    }

    #[DataProvider('cardStates')]
    public function test_card_status_is_derived_without_mutating_authorization(array $changes, string $state, string $label): void
    {
        $user = (new Card)->forceFill(array_replace([
            'status' => 'active', 'user_status' => 'active', 'expires_at' => now()->addDays(4),
        ], $changes));
        $before = $user->getAttributes();
        $this->assertSame($state, $user->softwareUserStatus());
        $this->blade('<x-admin.software-user-status :user="$user" />', ['user' => $user])
            ->assertSee('class="status-tag status-'.$state.'"', false)->assertSee($label);
        $this->assertSame($before, $user->getAttributes());
    }

    public static function accountStates(): array
    {
        return [
            ['active', '2026-09-27 10:00:00', 'active', '正常'],
            ['revoked', '2026-09-27 10:00:00', 'revoked', '已撤销'],
            ['frozen', '2026-09-27 10:00:00', 'frozen', '已冻结'],
            ['active', '2026-09-23 10:00:00', 'expired', '已到期'],
            ['active', null, 'active', '正常'],
            ['disabled', null, 'disabled', '已停用'],
        ];
    }

    #[DataProvider('accountStates')]
    public function test_account_mode_uses_explicit_status_labels(string $status, ?string $expiry, string $state, string $label): void
    {
        $user = (new ApplicationAccount)->forceFill(['status' => $status, 'expires_at' => $expiry]);
        $before = $user->getAttributes();
        $this->assertSame($state, $user->softwareUserStatus());
        $this->blade('<x-admin.software-user-status :user="$user" />', ['user' => $user])
            ->assertSee('class="status-tag status-'.$state.'"', false)->assertSee($label);
        $this->assertSame($before, $user->getAttributes());
    }
}
