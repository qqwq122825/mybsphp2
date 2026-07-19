<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();

        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/application-management.lock');
        @unlink($this->lockPath);
        config([
            'mybsphp.installed' => false,
            'mybsphp.install_lock' => $this->lockPath,
        ]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => 'MyBsPHP2 Apps',
            'secondary_password_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    public function test_super_administrator_can_create_edit_and_disable_an_application(): void
    {
        $administrator = $this->administrator();
        $this->actingAs($administrator, 'admin');

        $response = $this->post('/private-console/applications', [
            'code' => 'game_ios',
            'name' => '游戏 iOS',
            'description' => '卡密登录演示',
            'mode' => 'card_login',
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'payload_mode' => 'constant',
            'payload_value_ciphertext' => 'A1B2C3D4E5',
            'bind_device_enabled' => '1',
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 1,
            'version_name' => '1.0.0',
        ]);

        $application = Application::query()->where('code', 'game_ios')->sole();
        $response
            ->assertRedirect('/private-console/applications/'.$application->public_id.'/edit')
            ->assertSessionHas('client_secret');

        $this->assertSame('A1B2C3D4E5', $application->payload_value_ciphertext);
        $this->assertDatabaseHas('application_legacy_configs', [
            'application_id' => $application->getKey(),
            'login_api_name' => 'login.ic',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application.created',
            'target_id' => $application->public_id,
        ]);

        $this->get('/private-console/applications')
            ->assertOk()
            ->assertSee('游戏 iOS')
            ->assertSee('卡密登录');

        $this->put('/private-console/applications/'.$application->public_id, [
            'code' => 'game_ios',
            'name' => '游戏 iOS 正式版',
            'description' => '已更新',
            'mode' => 'card_login',
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'payload_mode' => 'empty',
            'payload_value_ciphertext' => '',
            'bind_device_enabled' => '0',
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 2,
            'version_name' => '1.1.0',
            'legacy_enabled' => '1',
            'login_api_name' => 'login.ic',
            'max_clock_skew_seconds' => 600,
        ])->assertRedirect();

        $application->refresh();
        $this->assertSame('游戏 iOS 正式版', $application->name);
        $this->assertSame('empty', $application->payload_mode);
        $this->assertFalse($application->bind_device_enabled);

        $this->post('/private-console/applications/'.$application->public_id.'/toggle')
            ->assertRedirect();
        $this->assertSame('suspended', $application->fresh()->status);
    }

    private function administrator(): Administrator
    {
        $administrator = Administrator::query()->create([
            'username' => 'app-admin',
            'password' => 'primary-secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());

        return $administrator;
    }
}
