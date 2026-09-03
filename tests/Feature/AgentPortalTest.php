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

        $this->get('/agent/cards')->assertOk()->assertSee('卡列表')->assertSee('测试软件')->assertSee((string) $ownedCards->first()->code_ciphertext);
        $card = $ownedCards->first();
        $this->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'freeze'])->assertRedirect();
        $this->assertTrue($card->fresh()->card_is_frozen);

        $card->forceFill(['bound_device_ciphertext' => 'DEVICE-AGENT-001', 'bound_device_mask' => 'DEV***'])->save();
        $this->post('/agent/tools/unbind', [
            'application_id' => $cardType->application_id,
            'credential' => (string) $card->code_ciphertext,
            'new_binding' => '',
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
