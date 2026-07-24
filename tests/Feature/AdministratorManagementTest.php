<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\CardType;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdministratorManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();

        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/administrator-management.lock');
        @unlink($this->lockPath);
        config(['mybsphp.install_lock' => $this->lockPath]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => 'MyBsPHP2 Admins',
            'secondary_password_enabled' => true,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    public function test_super_administrator_can_create_and_freeze_an_administrator_account(): void
    {
        $root = Administrator::query()->create([
            'username' => 'root-admin',
            'password' => 'primary-secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $root->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
        $this->actingAs($root, 'admin');

        $this->get('/private-console/administrators')
            ->assertOk()
            ->assertSee('后台管理员')
            ->assertSee('管理员列表')
            ->assertSee('添加管理员')
            ->assertSee('/private-console/administrators/create', false)
            ->assertDontSee('data-admin-create-dialog', false)
            ->assertSee('admin-table-shell', false)
            ->assertSee('admin-data-table', false)
            ->assertSee('admin-data-table-toolbar', false)
            ->assertSee('data-table-export', false)
            ->assertSee('data-admin-manage-dialog', false)
            ->assertSee('data-window-dialog', false)
            ->assertSee('data-window-scope="workspace"', false)
            ->assertDontSee('data-window-action="minimize"', false)
            ->assertSee('data-window-action="maximize"', false)
            ->assertDontSee('批量操作')
            ->assertSee('系统管理员')
            ->assertSee('查看');

        $this->get('/private-console/administrators/create')
            ->assertOk()
            ->assertSee('添加管理员')
            ->assertSee('管理员账号')
            ->assertSee('确认保存')
            ->assertDontSee('name="role_code"', false)
            ->assertDontSee('data-admin-create-dialog', false);

        $this->post('/private-console/administrators', [
            'username' => 'manager-one',
            'password' => 'manager-secret',
            'password_confirmation' => 'manager-secret',
            'secondary_password' => 'second-secret',
            'secondary_password_confirmation' => 'second-secret',
        ])->assertRedirect('/private-console/administrators');

        $manager = Administrator::query()->where('username', 'manager-one')->sole();
        $this->assertTrue($manager->secondary_password_required);
        $this->assertTrue($manager->hasRole('admin'));
        $this->assertFalse($manager->hasRole('agent'));

        $this->put('/private-console/administrators/'.$manager->getKey(), [
            'role_code' => 'admin',
            'status' => 'frozen',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect();

        $this->assertSame('frozen', $manager->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'administrator.updated',
            'target_id' => (string) $manager->getKey(),
        ]);

        $this->delete('/private-console/administrators/'.$root->getKey())->assertStatus(422);
        $this->assertDatabaseHas('administrators', ['id' => $root->getKey(), 'role' => 'super_admin']);

        $managerId = $manager->getKey();
        $this->delete('/private-console/administrators/'.$managerId)->assertRedirect('/private-console/administrators');
        $this->assertDatabaseMissing('administrators', ['id' => $managerId]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'administrator.deleted', 'target_id' => (string) $managerId]);
    }

    public function test_agent_accounts_are_managed_separately_from_administrators(): void
    {
        $root = Administrator::query()->create([
            'username' => 'root-admin',
            'password' => 'primary-secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $root->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
        $this->actingAs($root, 'admin');

        $this->get('/private-console/agents')
            ->assertOk()
            ->assertSee('代理账号管理')
            ->assertSee('添加代理账号')
            ->assertSee('admin-data-table', false)
            ->assertDontSee('二级密码')
            ->assertDontSee('用户账号管理');

        $this->get('/private-console/agents/create')
            ->assertOk()
            ->assertSee('添加代理账号')
            ->assertSee('代理账号')
            ->assertSee('购卡折扣')
            ->assertSee('可用软件分配')
            ->assertSee('联系邮箱')
            ->assertSee('确认保存')
            ->assertDontSee('二级密码')
            ->assertDontSee('密保问题')
            ->assertDontSee('密保答案')
            ->assertDontSee('邀请人账号')
            ->assertDontSee('已经邀请人数');

        $credentials = app(ApplicationCredentialService::class)->generate();
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => 'AGENT-APP',
            'name' => '代理可用软件',
            'mode' => 'card_login',
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'payload_mode' => 'constant',
            'payload_value_ciphertext' => 'agent-profile-test',
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
            'code' => 'AGENT30',
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

        $this->post('/private-console/agents', [
            'username' => 'agent-one',
            'password' => 'agent-secret',
            'password_confirmation' => 'agent-secret',
            'purchase_discount' => '8.8',
            'agent_note' => '华东渠道代理',
            'contact_qq' => '12345678',
            'contact_email' => 'agent@example.test',
            'contact_phone' => '13800138000',
            'all_applications' => '0',
            'application_ids' => [$application->getKey()],
            'card_type_ids' => [$cardType->getKey()],
        ])->assertRedirect('/private-console/agents');

        $agent = Administrator::query()->where('username', 'agent-one')->sole();
        $this->assertTrue($agent->hasRole('agent'));
        $this->assertFalse($agent->hasRole('admin'));
        $this->assertFalse($agent->secondary_password_required);
        $this->assertNull($agent->secondary_password);
        $this->assertSame(8800, $agent->agent_discount_basis_points);
        $this->assertSame('华东渠道代理', $agent->agent_note);
        $this->assertSame('agent@example.test', $agent->contact_email);
        $this->assertFalse($agent->agent_all_applications);
        $this->assertDatabaseHas('agent_application_access', [
            'agent_id' => $agent->getKey(),
            'application_id' => $application->getKey(),
        ]);
        $this->assertDatabaseHas('agent_card_type_access', [
            'agent_id' => $agent->getKey(),
            'card_type_id' => $cardType->getKey(),
        ]);

        $this->get('/private-console/administrators')
            ->assertOk()
            ->assertDontSee('agent-one');

        $this->put('/private-console/agents/'.$agent->getKey(), [
            'username' => 'agent-renamed',
            'status' => 'frozen',
            'password' => '',
            'password_confirmation' => '',
            'purchase_discount' => '9.5',
            'all_applications' => '1',
        ])->assertRedirect();
        $agent->refresh();
        $this->assertSame('agent-renamed', $agent->username);
        $this->assertSame('frozen', $agent->status);
        $this->assertSame(9500, $agent->agent_discount_basis_points);
        $this->assertTrue($agent->agent_all_applications);
        $this->assertDatabaseMissing('agent_application_access', ['agent_id' => $agent->getKey()]);
        $this->assertDatabaseMissing('agent_card_type_access', ['agent_id' => $agent->getKey()]);

        $this->delete('/private-console/administrators/'.$agent->getKey())->assertNotFound();
        $this->delete('/private-console/agents/'.$agent->getKey())->assertRedirect('/private-console/agents');
        $this->assertDatabaseMissing('administrators', ['id' => $agent->getKey()]);
    }

    public function test_administrator_can_enable_update_and_disable_own_secondary_password(): void
    {
        $root = Administrator::query()->create([
            'username' => 'root-admin',
            'password' => 'primary-secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $root->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
        $this->actingAs($root, 'admin');

        $this->put('/private-console/administrators/security', [
            'current_password' => 'wrong-password',
            'secondary_action' => 'enable',
            'secondary_password' => 'second-secret',
            'secondary_password_confirmation' => 'second-secret',
        ])->assertSessionHasErrors('current_password');

        $this->put('/private-console/administrators/security', [
            'current_password' => 'primary-secret',
            'secondary_action' => 'enable',
            'secondary_password' => 'second-secret',
            'secondary_password_confirmation' => 'second-secret',
        ])->assertRedirect();

        $root->refresh();
        $this->assertTrue($root->secondary_password_required);
        $this->assertTrue(Hash::check('second-secret', (string) $root->secondary_password));

        $this->put('/private-console/administrators/security', [
            'current_password' => 'primary-secret',
            'secondary_action' => 'disable',
        ])->assertRedirect();

        $root->refresh();
        $this->assertFalse($root->secondary_password_required);
        $this->assertNull($root->secondary_password);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'administrator.security_updated',
            'target_id' => (string) $root->getKey(),
        ]);
    }
}
