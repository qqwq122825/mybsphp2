<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\Card;
use App\Models\CardType;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentPortalTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();
        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/agent-portal.lock');
        @unlink($this->lockPath);
        config(['mybsphp.install_lock' => $this->lockPath]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => 'MyBsPHP2 Agent',
            'secondary_password_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    public function test_agent_entry_uses_extensionless_login_and_only_agent_accounts_can_login(): void
    {
        $agent = $this->agent();

        $this->get('/agent/login')
            ->assertOk()
            ->assertSee('代理商管理平台登录')
            ->assertSee('请输入代理账号信息')
            ->assertDontSee('图形验证码')
            ->assertDontSee('简体中文')
            ->assertDontSee('name="captcha"', false)
            ->assertDontSee('name="language"', false)
            ->assertSee('/assets/css/agent.css', false)
            ->assertSee('/agent/login"', false)
            ->assertDontSee('/agent/index.php"', false);

        $this->post('/agent/login', [
            'username' => $agent->username,
            'password' => 'agent-secret',
        ])->assertRedirect('/agent/dashboard');

        $this->get('/agent/dashboard')
            ->assertOk()
            ->assertSee('代理公告')
            ->assertSee('账户余额')
            ->assertSee('余额制卡')
            ->assertDontSee('我的用户管理')
            ->assertDontSee('我的代理')
            ->assertDontSee('库存制卡')
            ->assertDontSee('批量删除')
            ->assertSee($agent->username.'(1星)');

        $this->post('/agent/logout')->assertRedirect('/agent/login');
    }

    public function test_legacy_agent_php_entry_redirects_to_extensionless_login(): void
    {
        $this->get('/agent/index.php')->assertRedirect('/agent/login');
        $this->post('/agent/index.php', [
            'username' => 'legacy-agent',
            'password' => 'legacy-password',
        ])->assertStatus(307)->assertRedirect('/agent/login');

        $this->assertFileExists(public_path('agent/index.php'));
        $shim = (string) file_get_contents(public_path('agent/index.php'));
        $this->assertStringContainsString("'/login'", $shim);
        $this->assertStringContainsString('307', $shim);
    }

    public function test_agent_can_purchase_manage_issue_and_unbind_only_owned_cards(): void
    {
        $agent = $this->agent(10000);
        $administrator = Administrator::query()->create([
            'username' => 'admin-audit',
            'password' => 'admin-secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
        $agent->forceFill(['agent_discount_basis_points' => 8800])->save();
        [$application, $cardType] = $this->applicationAndType();
        $blockedType = $cardType->replicate();
        $blockedType->forceFill([
            'public_id' => (string) Str::ulid(),
            'name' => '90天卡',
            'code' => 'agent90',
        ])->save();
        $agent->forceFill(['agent_all_applications' => false])->save();
        $agent->agentApplications()->sync([$application->getKey()]);
        $agent->agentCardTypes()->sync([$cardType->getKey()]);
        // Reproduce a browser that is signed in to both portals. Agent actions
        // must still be attributed to the agent rather than the admin guard.
        $this->actingAs($administrator, 'admin');
        $this->actingAs($agent, 'agent');

        $this->get('/agent/purchase')
            ->assertOk()
            ->assertSee('余额购卡')
            ->assertSee('系统会自动计算当前代理价格')
            ->assertSee('测试软件 &gt; 30天卡，价格4.40元', false)
            ->assertDontSee('存到库存卡')
            ->assertDontSee('90天卡');

        $this->post('/agent/purchase', [
            'card_type_id' => $blockedType->getKey(),
            'quantity' => 1,
        ])->assertNotFound();

        $this->post('/agent/purchase', [
            'card_type_id' => $cardType->getKey(),
            'quantity' => 2,
            'note' => '代理验收制卡',
        ])->assertRedirect('/agent/purchase');

        $agent->refresh();
        $this->assertSame(9120, $agent->agent_balance_cents);
        $this->assertDatabaseHas('agent_transactions', [
            'agent_id' => $agent->getKey(),
            'type' => 'card_purchase',
            'amount_cents' => -880,
            'balance_after_cents' => 9120,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'agent.cards_purchased',
            'actor_user_id' => $agent->getKey(),
            'actor_role_snapshot' => 'agent',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'agent.cards_purchased',
            'actor_user_id' => $administrator->getKey(),
        ]);
        $this->get('/private-console/system-logs/card-making')
            ->assertOk()
            ->assertSee($agent->username)
            ->assertSee('代理制卡成功，软件 测试软件，卡类 30天卡，代理UID:'.$agent->getKey().'，2张')
            ->assertDontSee('代理制卡成功，软件 测试软件，卡类 30天卡，代理UID:'.$administrator->getKey().'，2张');
        $ownedCards = Card::query()->whereHas('batch', fn ($query) => $query->where('created_by_administrator_id', $agent->getKey()))->get();
        $this->assertCount(2, $ownedCards);
        $this->assertTrue($ownedCards->every(fn (Card $card): bool => $card->status === 'issued'));

        $this->get('/agent/cards')
            ->assertOk()
            ->assertSee('卡列表')
            ->assertSee('测试软件')
            ->assertSee((string) $ownedCards->first()->code_ciphertext)
            ->assertSee('搜索完整卡号或后4位')
            ->assertDontSee('全部软件')
            ->assertDontSee('全部卡类');
        $this->get('/agent/tools/query')
            ->assertOk()
            ->assertSee('批量查询')
            ->assertSee('请输入卡密后查询')
            ->assertDontSee('全部软件');
        $this->get('/agent/tools/query?keyword='.urlencode((string) $ownedCards->first()->code_ciphertext))
            ->assertOk()
            ->assertSee((string) $ownedCards->first()->code_ciphertext)
            ->assertDontSee((string) $ownedCards->last()->code_ciphertext);
        $this->get('/agent/tools/unbind')
            ->assertOk()
            ->assertSee('卡密')
            ->assertDontSee('软件选择')
            ->assertDontSee('新绑定');
        $card = $ownedCards->first();
        $this->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'freeze'])->assertRedirect();
        $this->assertTrue($card->fresh()->card_is_frozen);

        $this->get('/agent/tools/freeze')->assertOk()->assertSee('已使用卡密会弹出确认')->assertDontSee('全部软件');
        $this->post('/agent/tools/freeze', [
            'credential' => (string) $ownedCards->last()->code_ciphertext,
        ])->assertOk()->assertSee('未激活卡密已封卡');
        $this->assertTrue($ownedCards->last()->fresh()->card_is_frozen);

        $expiresAt = now()->addDays(30)->startOfSecond();
        $card->forceFill([
            'status' => 'active',
            'user_status' => 'active',
            'activated_at' => now(),
            'expires_at' => $expiresAt,
            'card_is_frozen' => false,
        ])->save();
        $this->post('/agent/tools/freeze', [
            'credential' => (string) $card->code_ciphertext,
        ])->assertOk()
            ->assertSee('卡密已经使用')
            ->assertSee('冻结用户')
            ->assertSee('删除卡密')
            ->assertSee((string) $card->code_ciphertext);
        $this->assertSame('active', $card->fresh()->user_status);

        $this->post('/agent/tools/freeze', [
            'credential' => (string) $card->code_ciphertext,
            'activated_action' => 'freeze_user',
        ])->assertOk()->assertSee('已激活用户已冻结，到期时间保持不变');
        $frozen = $card->fresh();
        $this->assertSame('frozen', $frozen->user_status);
        $this->assertSame($expiresAt->toDateTimeString(), $frozen->expires_at?->toDateTimeString());

        $this->post('/agent/tools/unfreeze', [
            'credential' => (string) $card->code_ciphertext,
        ])->assertOk()->assertSee('用户已解冻，到期时间保持不变');
        $restored = $card->fresh();
        $this->assertSame('active', $restored->user_status);
        $this->assertSame($expiresAt->toDateTimeString(), $restored->expires_at?->toDateTimeString());

        $usedCard = $ownedCards->last();
        $usedCard->forceFill([
            'status' => 'active',
            'user_status' => 'active',
            'activated_at' => now(),
            'expires_at' => $expiresAt,
            'card_is_frozen' => false,
        ])->save();
        $this->post('/agent/tools/freeze', [
            'credential' => (string) $usedCard->code_ciphertext,
            'activated_action' => 'delete_card',
        ])->assertOk()->assertSee('已使用卡密已删除，用户无法继续登录');
        $this->assertSame('revoked', $usedCard->fresh()->status);

        $card->forceFill(['bound_device_ciphertext' => 'DEVICE-AGENT-001', 'bound_device_mask' => 'DEV***'])->save();
        $this->post('/agent/tools/unbind', [
            'credential' => (string) $card->code_ciphertext,
        ])->assertRedirect()->assertSessionHas('success', '设备绑定已解除。');
        $this->assertNull($card->fresh()->bound_device_ciphertext);

        $this->get('/agent/inventory')->assertNotFound();
        $this->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'delete'])
            ->assertSessionHasErrors('action');
        $this->assertNotSame('revoked', $card->fresh()->status);

        $otherAgent = $this->agent(0, 'other-agent');
        $this->actingAs($otherAgent, 'agent');
        $this->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'freeze'])->assertNotFound();
    }

    public function test_agent_purchase_card_types_are_sorted_by_software(): void
    {
        $agent = $this->agent();
        [$application, $firstType] = $this->applicationAndType();
        $application->forceFill(['name' => '暗区国服越狱'])->save();
        $firstType->forceFill(['name' => '尊享卡', 'sort_order' => 5])->save();

        $secondType = $firstType->replicate();
        $secondType->forceFill([
            'public_id' => (string) Str::ulid(),
            'name' => '试用卡',
            'code' => 'agent-trial',
            'sort_order' => 1,
        ])->save();

        $otherCredentials = app(ApplicationCredentialService::class)->generate();
        $otherApplication = $application->replicate();
        $otherApplication->forceFill([
            'public_id' => (string) Str::ulid(),
            'code' => '3002',
            'name' => '三角洲国服巨魔',
            'client_secret_hash' => hash('sha256', $otherCredentials['communication_key_ciphertext']),
            ...$otherCredentials,
        ])->save();
        $otherFirst = $firstType->replicate();
        $otherFirst->forceFill([
            'public_id' => (string) Str::ulid(),
            'application_id' => $otherApplication->getKey(),
            'name' => '畅玩卡',
            'code' => 'agent-play',
            'sort_order' => 8,
        ])->save();
        $otherSecond = $firstType->replicate();
        $otherSecond->forceFill([
            'public_id' => (string) Str::ulid(),
            'application_id' => $otherApplication->getKey(),
            'name' => '入门卡',
            'code' => 'agent-intro',
            'sort_order' => 2,
        ])->save();

        $html = $this->actingAs($agent, 'agent')->get('/agent/purchase')->assertOk()->getContent();
        preg_match_all('/>([^<]+ &gt; [^，]+，价格)/u', $html, $matches);

        $this->assertSame([
            '三角洲国服巨魔 &gt; 入门卡，价格',
            '三角洲国服巨魔 &gt; 畅玩卡，价格',
            '暗区国服越狱 &gt; 试用卡，价格',
            '暗区国服越狱 &gt; 尊享卡，价格',
        ], $matches[1]);
    }

    private function agent(int $balanceCents = 0, string $username = 'agent-one'): Administrator
    {
        $agent = Administrator::query()->create([
            'username' => $username,
            'password' => 'agent-secret',
            'role' => 'agent',
            'status' => 'active',
            'agent_balance_cents' => $balanceCents,
            'agent_level' => 1,
        ]);
        $agent->roles()->attach(Role::query()->where('code', 'agent')->sole());

        return $agent;
    }

    /** @return array{Application, CardType} */
    private function applicationAndType(): array
    {
        $credentials = app(ApplicationCredentialService::class)->generate();
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => '3001',
            'name' => '测试软件',
            'mode' => 'card_login',
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'payload_mode' => 'constant',
            'payload_value_ciphertext' => 'agent-test',
            'client_secret_hash' => hash('sha256', $credentials['communication_key_ciphertext']),
            'bind_device_enabled' => true,
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 1,
            ...$credentials,
        ]);
        $cardType = CardType::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'name' => '30天卡',
            'code' => 'agent30',
            'purpose' => 'login_credential',
            'benefit_kind' => 'duration',
            'duration_seconds' => 30 * 86400,
            'duration_value' => 30,
            'duration_unit' => 'day',
            'face_price_cents' => 1000,
            'default_agent_price_cents' => 500,
            'code_prefix' => 'AG',
            'code_length' => 20,
            'secret_length' => 0,
            'status' => 'active',
            'max_activations' => 1,
        ]);

        return [$application, $cardType];
    }
}
