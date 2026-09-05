<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Auth\SystemAccessSynchronizer;
use App\Cards\CardCredentialHasher;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\Card;
use App\Models\CardBatch;
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
            ->assertDontSee('批量冻结')
            ->assertDontSee('批量解冻')
            ->assertDontSee('解除用户绑定')
            ->assertDontSee('代理工具')
            ->assertDontSee('批量查询')
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
            ->assertDontSee('全部卡类')
            ->assertDontSee('代理工具')
            ->assertDontSee('批量查询');
        $this->get('/agent/tools/query')->assertRedirect('/agent/cards');
        $this->get('/agent/cards?keyword='.urlencode((string) $ownedCards->first()->code_ciphertext))
            ->assertOk()
            ->assertSee((string) $ownedCards->first()->code_ciphertext)
            ->assertSee('冻结')
            ->assertSee('删除')
            ->assertDontSee('value="unbind"', false)
            ->assertDontSee((string) $ownedCards->last()->code_ciphertext);

        $systemCode = 'SYSTEM-MADE-CARD-9001';
        $systemBatch = CardBatch::query()->create([
            'public_id' => (string) Str::ulid(),
            'card_type_id' => $cardType->getKey(),
            'batch_no' => 'SYS-'.Str::upper(Str::random(8)),
            'source' => 'generated',
            'planned_quantity' => 1,
            'actual_quantity' => 1,
            'status' => 'ready',
            'created_by_administrator_id' => $administrator->getKey(),
        ]);
        $systemCard = Card::query()->create([
            'public_id' => (string) Str::ulid(),
            'batch_id' => $systemBatch->getKey(),
            'card_type_id' => $cardType->getKey(),
            'application_id' => $application->getKey(),
            'code_lookup_hash' => app(CardCredentialHasher::class)->lookupHash($systemCode),
            'code_ciphertext' => $systemCode,
            'code_last4' => substr($systemCode, -4),
            'status' => 'issued',
        ]);
        $this->get('/agent/cards?keyword='.urlencode($systemCode))
            ->assertOk()
            ->assertSee('共 0 张');
        $this->post('/agent/cards/'.$systemCard->getKey().'/action', ['action' => 'freeze'])->assertNotFound();
        $this->get('/agent/tools/unbind')->assertRedirect('/agent/cards');
        $this->get('/agent/tools/freeze')->assertRedirect('/agent/cards');
        $this->get('/agent/tools/unfreeze')->assertRedirect('/agent/cards');
        $card = $ownedCards->first();
        $this->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'freeze'])->assertRedirect();
        $this->assertTrue($card->fresh()->card_is_frozen);
        $this->post('/agent/cards/'.$ownedCards->last()->getKey().'/action', ['action' => 'freeze'])->assertRedirect();
        $this->assertTrue($ownedCards->last()->fresh()->card_is_frozen);

        $expiresAt = now()->addDays(30)->startOfSecond();
        $card->forceFill([
            'status' => 'active',
            'user_status' => 'active',
            'activated_at' => now(),
            'expires_at' => $expiresAt,
            'card_is_frozen' => false,
        ])->save();
        $queryUrl = '/agent/cards?keyword='.urlencode((string) $card->code_ciphertext);
        $this->get($queryUrl)
            ->assertOk()
            ->assertSee('删除')
            ->assertSee('解绑')
            ->assertDontSee('value="freeze"', false);
        $this->from($queryUrl)->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'freeze'])
            ->assertRedirect($queryUrl)
            ->assertSessionHasErrors('action');
        $this->assertSame('active', $card->fresh()->user_status);

        $card->forceFill(['bound_device_ciphertext' => 'DEVICE-AGENT-001', 'bound_device_mask' => 'DEV***'])->save();
        $this->from($queryUrl)->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'unbind'])
            ->assertRedirect($queryUrl)
            ->assertSessionHas('success', '设备绑定已解除，未扣除时间。')
            ->assertSessionMissing('agent_unbind_card');
        $cleared = $card->fresh();
        $this->assertNull($cleared->bound_device_ciphertext);
        $this->assertSame($expiresAt->toDateTimeString(), $cleared->expires_at?->toDateTimeString());

        $application->forceFill([
            'rebind_deduction_value' => 2,
            'rebind_deduction_unit' => 'hour',
        ])->save();
        $card->forceFill(['bound_device_ciphertext' => 'DEVICE-AGENT-002', 'bound_device_mask' => 'DEV***'])->save();
        $this->from($queryUrl)->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'unbind'])
            ->assertRedirect($queryUrl)
            ->assertSessionHas('agent_unbind_card');
        $this->assertSame('DEVICE-AGENT-002', $card->fresh()->bound_device_ciphertext);
        $this->get($queryUrl)
            ->assertOk()
            ->assertSee('确认解绑')
            ->assertSee('扣除时间')
            ->assertSee('不扣时间')
            ->assertSee('解绑扣时 2小时');

        $this->from($queryUrl)->post('/agent/cards/'.$card->getKey().'/action', [
            'action' => 'unbind',
            'deduct_time' => '1',
        ])
            ->assertRedirect($queryUrl)
            ->assertSessionHas('success', '设备绑定已解除，并已扣除解绑时间。');
        $deducted = $card->fresh();
        $this->assertNull($deducted->bound_device_ciphertext);
        $this->assertSame($expiresAt->copy()->subHours(2)->toDateTimeString(), $deducted->expires_at?->toDateTimeString());

        $this->from($queryUrl)->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'delete_card'])
            ->assertRedirect($queryUrl)
            ->assertSessionHas('agent_delete_card');
        $this->get($queryUrl)
            ->assertOk()
            ->assertSee('确认删除')
            ->assertSee('4.40')
            ->assertSee('确认删除并退款');
        $this->assertSame('active', $card->fresh()->status);
        $this->assertSame(9120, $agent->fresh()->agent_balance_cents);

        $this->from($queryUrl)->post('/agent/cards/'.$card->getKey().'/action', [
            'action' => 'delete_card',
            'confirmed' => '1',
        ])
            ->assertRedirect($queryUrl)
            ->assertSessionHas('success');
        $this->assertSame('revoked', $card->fresh()->status);
        $this->assertSame(9560, $agent->fresh()->agent_balance_cents);
        $this->assertDatabaseHas('agent_transactions', [
            'agent_id' => $agent->getKey(),
            'type' => 'card_refund',
            'amount_cents' => 440,
            'balance_after_cents' => 9560,
            'reference_id' => (string) $card->public_id,
        ]);
        $this->get($queryUrl)
            ->assertOk()
            ->assertSee('共 0 张');
        $this->get('/private-console/cards?keyword='.urlencode((string) $card->code_ciphertext))
            ->assertOk()
            ->assertSee((string) $card->code_ciphertext)
            ->assertSee('已删除');

        $usedCard = $ownedCards->last();
        $usedCard->forceFill([
            'status' => 'active',
            'user_status' => 'active',
            'activated_at' => now(),
            'expires_at' => $expiresAt,
            'card_is_frozen' => false,
        ])->save();
        $this->from($queryUrl)->post('/agent/cards/'.$usedCard->getKey().'/action', [
            'action' => 'delete_card',
            'confirmed' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('revoked', $usedCard->fresh()->status);
        $this->assertSame(10000, $agent->fresh()->agent_balance_cents);

        $this->get('/agent/inventory')->assertNotFound();
        $this->post('/agent/cards/'.$card->getKey().'/action', ['action' => 'delete'])
            ->assertSessionHasErrors('action');
        $this->assertSame('revoked', $card->fresh()->status);

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
