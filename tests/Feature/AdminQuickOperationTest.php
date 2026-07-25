<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Cards\CardCredentialHasher;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\ApplicationClientSession;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use App\Models\Role;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminQuickOperationTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();
        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/admin-quick-operation.lock');
        @unlink($this->lockPath);
        config(['mybsphp.install_lock' => $this->lockPath]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'agent_path' => 'agent',
            'app_name' => 'Quick Operation',
            'secondary_password_enabled' => false,
        ]);
        $administrator = Administrator::query()->create([
            'username' => 'quick-admin',
            'password' => 'secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
        $this->actingAs($administrator, 'admin');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        @unlink($this->lockPath);
        parent::tearDown();
    }

    public function test_card_lookup_locates_recharged_account_and_supports_quick_actions(): void
    {
        CarbonImmutable::setTestNow('2026-07-25 12:00:00');
        [$application, $type, $batch] = $this->fixture('account_recharge');
        $account = ApplicationAccount::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'username' => 'located-user',
            'password_hash' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 'active',
            'expires_at' => now()->addDays(2),
        ]);
        $credential = 'AGENT-ACCOUNT-CARD-0001';
        $card = $this->card($application, $type, $batch, $credential, [
            'status' => 'consumed',
            'consumed_at' => now(),
            'redeemed_by_account_id' => $account->getKey(),
        ]);
        $account->forceFill([
            'bound_device_ciphertext' => 'ACCOUNT-DEVICE-001',
            'bound_device_mask' => 'ACCO•••001',
        ])->save();
        $session = $this->clientSession($application, account: $account);

        $this->get('/private-console/quick-operations')
            ->assertOk()
            ->assertSee('快速加时与卡密查询')
            ->assertSee('快办');

        $this->post('/private-console/quick-operations/search', ['credential' => $credential])
            ->assertRedirect('/private-console/quick-operations?card='.$card->public_id);

        $this->get('/private-console/quick-operations?card='.$card->public_id)
            ->assertOk()
            ->assertSee('located-user')
            ->assertSee('账号登录模式')
            ->assertSee($credential)
            ->assertSee('代理发卡')
            ->assertSee('agent-seller')
            ->assertSee('解除设备绑定')
            ->assertSee('删除账号')
            ->assertSee('data-add-days="1"', false)
            ->assertSee('type="button" data-add-days="30"', false)
            ->assertDontSee('删除/作废充值卡')
            ->assertDontSee('确认删除卡密');

        $this->post('/private-console/quick-operations/action', [
            'card_id' => $card->getKey(),
            'action' => 'add_time',
            'days' => 3,
        ])->assertSessionHasNoErrors()
            ->assertRedirect('/private-console/quick-operations?card='.$card->public_id);

        $this->assertSame(
            '2026-07-30 12:00:00',
            $account->refresh()->expires_at->format('Y-m-d H:i:s'),
        );

        $this->post('/private-console/quick-operations/action', [
            'card_id' => $card->getKey(),
            'action' => 'unbind_user',
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('success', '用户设备绑定已解除，现有登录连接已失效。')
            ->assertRedirect('/private-console/quick-operations?card='.$card->public_id);

        $account->refresh();
        $this->assertNull($account->bound_device_ciphertext);
        $this->assertNull($account->bound_device_mask);
        $this->assertSame('logged_out', $session->refresh()->status);
        $this->assertNotNull($session->logged_out_at);

        $this->from('/private-console/quick-operations?card='.$card->public_id)
            ->post('/private-console/quick-operations/action', [
                'card_id' => $card->getKey(),
                'action' => 'delete_card',
                'confirmed' => '1',
            ])->assertSessionHasErrors([
                'action' => '这张充值卡已经充值到账号，卡密将作为充值历史记录保留。',
            ]);
        $this->assertDatabaseHas('cards', [
            'id' => $card->getKey(),
            'status' => 'consumed',
            'redeemed_by_account_id' => $account->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'quick_operation.executed',
            'target_id' => $card->public_id,
        ]);
    }

    public function test_quick_delete_removes_an_unactivated_agent_card(): void
    {
        [$application, $type, $batch] = $this->fixture('card_login');
        $card = $this->card($application, $type, $batch, 'AGENT-UNUSED-CARD-0002');

        $this->post('/private-console/quick-operations/action', [
            'card_id' => $card->getKey(),
            'action' => 'delete_card',
            'confirmed' => '1',
        ])->assertRedirect('/private-console/quick-operations');

        $this->assertDatabaseMissing('cards', ['id' => $card->getKey()]);
    }

    public function test_card_user_uses_unbind_and_delete_actions_instead_of_freeze(): void
    {
        [$application, $type, $batch] = $this->fixture('card_login');
        $card = $this->card($application, $type, $batch, 'ACTIVE-CARD-USER-0003', [
            'status' => 'active',
            'user_status' => 'active',
            'activated_at' => now(),
            'expires_at' => now()->addDay(),
            'bound_device_ciphertext' => 'DEVICE-FINGERPRINT-001',
            'bound_device_mask' => 'DEVI•••001',
        ]);
        $session = $this->clientSession($application, card: $card);

        $this->get('/private-console/quick-operations?card='.$card->public_id)
            ->assertOk()
            ->assertSee('充值卡快捷操作')
            ->assertSee('解除设备绑定')
            ->assertSee('删除/作废充值卡')
            ->assertDontSee('冻结用户')
            ->assertDontSee('确认删除账号');

        $this->post('/private-console/quick-operations/action', [
            'card_id' => $card->getKey(),
            'action' => 'unbind_user',
        ])->assertSessionHasNoErrors()
            ->assertRedirect('/private-console/quick-operations?card='.$card->public_id);

        $card->refresh();
        $this->assertNull($card->bound_device_ciphertext);
        $this->assertNull($card->bound_device_mask);
        $this->assertSame('logged_out', $session->refresh()->status);
        $this->assertNotNull($session->logged_out_at);
    }

    /** @return array{Application, CardType, CardBatch} */
    private function fixture(string $mode): array
    {
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => Str::lower(Str::random(10)),
            'name' => $mode === 'account_recharge' ? '账号测试软件' : '卡串测试软件',
            'mode' => $mode,
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'client_secret_hash' => hash('sha256', Str::random(20)),
        ]);
        $type = CardType::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'name' => '测试天卡',
            'code' => Str::lower(Str::random(8)),
            'purpose' => $mode === 'account_recharge' ? 'account_recharge' : 'login_credential',
            'benefit_kind' => 'duration',
            'duration_seconds' => 86400,
            'duration_value' => 1,
            'duration_unit' => 'day',
            'code_length' => 24,
            'secret_length' => 0,
            'status' => 'active',
        ]);
        $batch = CardBatch::query()->create([
            'public_id' => (string) Str::ulid(),
            'card_type_id' => $type->getKey(),
            'batch_no' => 'QUICK-'.Str::upper(Str::random(10)),
            'source' => 'sale',
            'planned_quantity' => 1,
            'actual_quantity' => 1,
            'status' => 'ready',
            'maker_reference' => 'agent-seller',
        ]);

        return [$application, $type, $batch];
    }

    private function card(
        Application $application,
        CardType $type,
        CardBatch $batch,
        string $credential,
        array $values = [],
    ): Card {
        return Card::query()->create([
            'public_id' => (string) Str::ulid(),
            'batch_id' => $batch->getKey(),
            'card_type_id' => $type->getKey(),
            'application_id' => $application->getKey(),
            'code_lookup_hash' => app(CardCredentialHasher::class)->lookupHash($credential),
            'code_ciphertext' => $credential,
            'code_last4' => substr($credential, -4),
            'status' => 'issued',
            'card_is_frozen' => false,
            ...$values,
        ]);
    }

    private function clientSession(
        Application $application,
        ?Card $card = null,
        ?ApplicationAccount $account = null,
    ): ApplicationClientSession {
        return ApplicationClientSession::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'token_hash' => hash('sha256', Str::random(64)),
            'auth_mode' => $card ? 'card' : 'account',
            'card_id' => $card?->getKey(),
            'application_account_id' => $account?->getKey(),
            'device_id_ciphertext' => 'SESSION-DEVICE-001',
            'device_mask' => 'SESS•••001',
            'status' => 'active',
            'last_heartbeat_at' => now(),
        ]);
    }
}
