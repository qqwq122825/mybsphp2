<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemInformationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();
        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/system-information-settings.lock');
        @unlink($this->lockPath);
        config(['mybsphp.install_lock' => $this->lockPath]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => 'Original System',
            'secondary_password_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    public function test_super_administrator_can_update_operational_system_information(): void
    {
        $administrator = Administrator::query()->create([
            'username' => 'root-admin',
            'password' => 'primary-secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
        $this->actingAs($administrator, 'admin');

        $this->get('/private-console/settings/system')
            ->assertOk()
            ->assertSee('系统信息设置')
            ->assertSee('系统名称')
            ->assertSee('系统地址')
            ->assertSee('时区设置')
            ->assertSee('客户端服务')
            ->assertSee('暂停提示')
            ->assertDontSee('个性化设置')
            ->assertDontSee('角色与权限')
            ->assertDontSee('设备解绑')
            ->assertDontSee('API 与 Legacy 配置')
            ->assertDontSee('系统密钥KEY')
            ->assertDontSee('COOKIES加密')
            ->assertDontSee('系统时间差');

        $this->from('/private-console/settings/system')->put('/private-console/settings/system', [
            'app_name' => '授权运营中心',
            'app_url' => 'https://license.example.test',
            'app_timezone' => 'Asia/Shanghai',
            'client_service_enabled' => '0',
            'client_service_message' => '服务升级维护中，请稍后重新连接。',
        ])->assertRedirect('/private-console/settings/system');

        $this->assertDatabaseHas('system_settings', ['key' => 'app_name', 'value' => '授权运营中心']);
        $this->assertDatabaseHas('system_settings', ['key' => 'client_service_enabled', 'value' => '0']);

        $this->get('/private-console/settings/system')
            ->assertOk()
            ->assertSee('授权运营中心')
            ->assertSee('服务升级维护中，请稍后重新连接。');

        $this->postJson('/AppEn.php', [])
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'SERVICE_DISABLED')
            ->assertJsonPath('error.message', '服务升级维护中，请稍后重新连接。');
    }
}
