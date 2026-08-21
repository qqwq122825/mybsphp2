<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Role;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class SystemInformationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    private string $environmentPath;

    protected function setUp(): void
    {
        parent::setUp();
        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/system-information-settings.lock');
        $this->environmentPath = storage_path('framework/testing/system-information-settings.env');
        @unlink($this->lockPath);
        @unlink($this->environmentPath);
        file_put_contents($this->environmentPath, "ADMIN_PATH=\"private-console\"\nAGENT_PATH=\"agent\"\n");
        config([
            'mybsphp.install_lock' => $this->lockPath,
            'mybsphp.environment_file' => $this->environmentPath,
        ]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => 'Original System',
            'secondary_password_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        @unlink($this->environmentPath);
        @unlink($this->environmentPath.'.installing');
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
            ->assertSee('后台入口')
            ->assertSee('代理入口')
            ->assertSee('时区设置')
            ->assertSee('当前模式')
            ->assertSee('上线运行')
            ->assertSee('调试模式')
            ->assertSee('客户端服务')
            ->assertSee('暂停提示')
            ->assertSee('卡密登录请求日志')
            ->assertSee('开启诊断记录')
            ->assertSee('是否启用万能卡密')
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
            'admin_path' => 'new-control-center',
            'agent_path' => 'partner-gateway',
            'operational_mode' => 'debug',
            'client_service_enabled' => '0',
            'client_service_message' => '服务升级维护中，请稍后重新连接。',
            'card_login_trace_enabled' => '1',
            'universal_card_enabled' => '1',
        ])->assertRedirect('/new-control-center/settings/system');

        $this->assertDatabaseHas('system_settings', ['key' => 'app_name', 'value' => '授权运营中心']);
        $this->assertDatabaseHas('system_settings', ['key' => 'admin_path', 'value' => 'new-control-center']);
        $this->assertDatabaseHas('system_settings', ['key' => 'agent_path', 'value' => 'partner-gateway']);
        $this->assertDatabaseHas('system_settings', ['key' => 'client_service_enabled', 'value' => '0']);
        $this->assertDatabaseHas('system_settings', ['key' => 'card_login_trace_enabled', 'value' => '1', 'group' => 'diagnostics']);
        $this->assertDatabaseHas('system_settings', ['key' => 'universal_card_enabled', 'value' => '1', 'group' => 'security']);
        $this->assertDatabaseHas('system_settings', ['key' => 'operational_mode', 'value' => 'debug', 'group' => 'security']);
        $this->assertSame('new-control-center', app(InstallationManager::class)->adminPath());
        $this->assertSame('partner-gateway', app(InstallationManager::class)->agentPath());
        $this->assertStringContainsString('ADMIN_PATH="new-control-center"', (string) file_get_contents($this->environmentPath));
        $this->assertStringContainsString('AGENT_PATH="partner-gateway"', (string) file_get_contents($this->environmentPath));

        $this->get('/private-console/settings/system')->assertNotFound();
        $this->get('/new-control-center/settings/system')
            ->assertOk()
            ->assertSee('授权运营中心')
            ->assertSee('new-control-center')
            ->assertSee('partner-gateway')
            ->assertSee('调试信息正在开放')
            ->assertSee('卡密登录诊断正在记录')
            ->assertSee('服务升级维护中，请稍后重新连接。');

        $this->postJson('/AppEn.php', [])
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'SERVICE_DISABLED')
            ->assertJsonPath('error.message', '服务升级维护中，请稍后重新连接。');
    }

    public function test_runtime_mode_controls_unhandled_api_error_details(): void
    {
        Route::get('/api/testing/runtime-mode-error', static function (): never {
            throw new RuntimeException('调试故障定位信息');
        });

        $this->getJson('/api/testing/runtime-mode-error')
            ->assertStatus(500)
            ->assertJsonPath('error.code', 'INTERNAL_ERROR')
            ->assertJsonMissingPath('error.details');

        SystemSetting::query()->updateOrCreate(['key' => 'operational_mode'], [
            'value' => 'debug', 'type' => 'string', 'group' => 'security',
        ]);

        $this->getJson('/api/testing/runtime-mode-error')
            ->assertStatus(500)
            ->assertJsonPath('error.code', 'INTERNAL_ERROR')
            ->assertJsonPath('error.details.exception', RuntimeException::class)
            ->assertJsonPath('error.details.message', '调试故障定位信息');
    }

    public function test_public_home_uses_diary_page_only_in_production_mode(): void
    {
        SystemSetting::query()->updateOrCreate(['key' => 'operational_mode'], [
            'value' => 'production', 'type' => 'string', 'group' => 'security',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('我的生活记录')
            ->assertSee('今日随笔')
            ->assertDontSee('Original System')
            ->assertDontSee('授权与发卡管理服务正在运行');

        SystemSetting::query()->whereKey('operational_mode')->update(['value' => 'debug']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Original System')
            ->assertSee('授权与发卡管理服务正在运行')
            ->assertDontSee('今日随笔');
    }
}
