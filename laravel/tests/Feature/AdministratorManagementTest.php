<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_super_administrator_can_create_and_freeze_an_agent_account(): void
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
            ->assertSee('代理角色默认没有删卡');

        $this->post('/private-console/administrators', [
            'username' => 'agent-one',
            'password' => 'agent-secret',
            'password_confirmation' => 'agent-secret',
            'secondary_password' => 'second-secret',
            'secondary_password_confirmation' => 'second-secret',
            'role_code' => 'agent',
        ])->assertRedirect();

        $agent = Administrator::query()->where('username', 'agent-one')->sole();
        $this->assertTrue($agent->secondary_password_required);
        $this->assertTrue($agent->hasRole('agent'));
        $this->assertTrue($agent->hasPermission('cards.issue'));
        $this->assertFalse($agent->hasPermission('devices.unbind'));

        $this->put('/private-console/administrators/'.$agent->getKey(), [
            'role_code' => 'agent',
            'status' => 'frozen',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect();

        $this->assertSame('frozen', $agent->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'administrator.updated',
            'target_id' => (string) $agent->getKey(),
        ]);
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
