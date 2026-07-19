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

        $this->get('/private-console/applications/create')
            ->assertOk()
            ->assertSee('软件模式')
            ->assertSee('软件名称')
            ->assertSee('软件代号')
            ->assertDontSee('授权返回与设备')
            ->assertDontSee('启用设备绑定')
            ->assertDontSee('验证数据');

        $response = $this->post('/private-console/applications', [
            'code' => '10000001',
            'name' => '游戏 iOS',
            'mode' => 'card_login',
        ]);

        $application = Application::query()->where('code', '10000001')->sole();
        $response
            ->assertRedirect('/private-console/applications/'.$application->public_id.'/edit')
            ->assertSessionHas('client_secret');

        $this->assertSame('active', $application->status);
        $this->assertSame('constant', $application->payload_mode);
        $this->assertSame('', $application->verificationData());
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
            ->assertSee('卡密验证限时模式')
            ->assertSee('配置软件');

        $this->get('/private-console/applications/'.$application->public_id.'/edit')
            ->assertOk()
            ->assertSee('验证数据')
            ->assertSee('客户端返回数组 a[2]');

        $this->put('/private-console/applications/'.$application->public_id, [
            'name' => '游戏 iOS 正式版',
            'description' => '已更新',
            'status' => 'active',
            'verification_data' => "0x100000\nworld-server=gwrold.example",
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
        $this->assertSame("0x100000\nworld-server=gwrold.example", $application->verificationData());
        $this->assertSame('constant', $application->payload_mode);
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
