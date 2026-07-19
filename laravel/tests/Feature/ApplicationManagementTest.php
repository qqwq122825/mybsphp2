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
            ->assertSee('页面标签导航')
            ->assertSee('快捷入口指引')
            ->assertSee('切换侧栏显示')
            ->assertSee('data-quick-entry', false)
            ->assertSee('mybsphp.css?v=20260719-w418-ephemeral-tabs', false)
            ->assertDontSee('后台入口')
            ->assertSee('软件管理')
            ->assertSee('添加新软件')
            ->assertSee('软件模式')
            ->assertSee('软件名称')
            ->assertSee('软件代号')
            ->assertDontSee('授权返回与设备')
            ->assertDontSee('启用设备绑定')
            ->assertDontSee('验证数据');

        $this->from('/private-console/applications/create')
            ->post('/private-console/applications', [
                'code' => 'game_ios',
                'name' => '非法代号软件',
                'mode' => 'card_login',
            ])
            ->assertRedirect('/private-console/applications/create')
            ->assertSessionHasErrors('code');

        $response = $this->post('/private-console/applications', [
            'code' => '10000001',
            'name' => '游戏 iOS',
            'mode' => 'card_login',
        ]);

        $application = Application::query()->where('code', '10000001')->sole();
        $response
            ->assertRedirect('/private-console/applications/'.$application->public_id.'/edit')
            ->assertSessionHas('client_secret', $application->communicationKey());

        $this->assertSame('active', $application->status);
        $this->assertSame('constant', $application->payload_mode);
        $this->assertSame('', $application->verificationData());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $application->communicationKey());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (string) $application->gateway_token);
        $this->assertStringContainsString('BEGIN RSA PUBLIC KEY', (string) $application->server_public_key);
        $this->assertStringContainsString('BEGIN PRIVATE KEY', $application->serverPrivateKey());
        $this->assertSame(18, strlen($application->legacyPacketKey()));
        $this->assertSame('hybrid_rsa_aes_hmac', $application->encryption_mode);
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
            ->assertSee('卡串验证限时模式')
            ->assertSee('软件配置')
            ->assertSee('用户管理')
            ->assertSee('充值卡列表')
            ->assertSee('卡串类型')
            ->assertSee('现卡制作')
            ->assertSee('data-workspace-open', false)
            ->assertSee($application->code.'-卡串类型');

        $this->get('/private-console/applications/'.$application->public_id.'/users')
            ->assertOk()
            ->assertSee('卡串用户')
            ->assertDontSee('用户分组');

        $this->get('/private-console/applications/'.$application->public_id.'/cards/make')
            ->assertOk()
            ->assertSee('请先添加充值卡类型')
            ->assertDontSee('确认制作');

        $this->get('/private-console/applications/'.$application->public_id.'/card-types')
            ->assertOk()
            ->assertSee('添加充值卡类型')
            ->assertSee('代号 '.$application->code)
            ->assertDontSee('全部卡密')
            ->assertDontSee('累计生成')
            ->assertDontSee('用户分组')
            ->assertDontSee('卡类图片');

        $this->get('/private-console/applications/'.$application->public_id.'/edit')
            ->assertOk()
            ->assertSee('验证数据')
            ->assertSee('客户端返回数组 a[2]')
            ->assertSee('通信认证 Key')
            ->assertSee('BSPHP_MUTUALKEY')
            ->assertSee('/AppEn.php?appid=10000001&amp;md5=', false)
            ->assertSee('现代混合加密')
            ->assertSee('10000001-软件配置')
            ->assertSee('role="tablist"', false)
            ->assertSee('data-config-tab="basic"', false)
            ->assertSee('data-config-tab="communication"', false)
            ->assertSee('data-config-tab="limits"', false)
            ->assertSee('基本配置')
            ->assertSee('通信安全')
            ->assertSee('限开控制')
            ->assertSee('默认登录 API')
            ->assertSee('卡串模式固定使用 .ic')
            ->assertDontSee('name="login_api_name"', false);

        $this->put('/private-console/applications/'.$application->public_id, [
            'name' => '游戏 iOS 正式版',
            'description' => '已更新',
            'status' => 'active',
            'verification_data' => "0x100000\nworld-server=gwrold.example",
            'encryption_mode' => 'hybrid_rsa_aes_hmac',
            'bind_device_enabled' => '0',
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 2,
            'version_name' => '1.1.0',
            'legacy_enabled' => '1',
            'login_api_name' => 'login.lg',
            'max_clock_skew_seconds' => 600,
        ])->assertRedirect();

        $application->refresh();
        $this->assertSame('游戏 iOS 正式版', $application->name);
        $this->assertSame("0x100000\nworld-server=gwrold.example", $application->verificationData());
        $this->assertSame('constant', $application->payload_mode);
        $this->assertFalse($application->bind_device_enabled);
        $this->assertSame('login.ic', $application->legacyConfig()->sole()->login_api_name);

        $this->post('/private-console/applications/'.$application->public_id.'/toggle')
            ->assertRedirect();
        $this->assertSame('suspended', $application->fresh()->status);

        $this->post('/private-console/applications', [
            'code' => '10000002',
            'name' => '账户模式软件',
            'mode' => 'account_recharge',
        ])->assertRedirect();

        $accountApplication = Application::query()->where('code', '10000002')->sole();
        $accountLegacy = $accountApplication->legacyConfig()->sole();
        $this->assertSame('login.lg', $accountLegacy->login_api_name);
        $this->assertSame('user', $accountLegacy->credential_param);
        $this->assertSame('pwd', $accountLegacy->credential_secret_param);
        $this->get('/private-console/applications/'.$accountApplication->public_id.'/edit')
            ->assertOk()
            ->assertSee('login.lg')
            ->assertSee('账户模式固定使用 .lg');
        $this->get('/private-console/applications/'.$accountApplication->public_id.'/users')
            ->assertOk()
            ->assertSee('账户用户')
            ->assertSee('余额')
            ->assertSee('点数')
            ->assertDontSee('用户分组');

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
