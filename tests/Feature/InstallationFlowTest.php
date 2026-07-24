<?php

namespace Tests\Feature;

use App\Install\InstallationManager;
use App\Install\ReinstallationTokenManager;
use Tests\TestCase;

class InstallationFlowTest extends TestCase
{
    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lockPath = storage_path('framework/testing/installation-flow.lock');
        @unlink($this->lockPath);
        config([
            'mybsphp.installed' => false,
            'mybsphp.install_lock' => $this->lockPath,
            'mybsphp.admin_path' => 'admin',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);

        parent::tearDown();
    }

    public function test_uninstalled_site_redirects_to_the_five_step_installer(): void
    {
        $this->get('/')->assertRedirect('/install');

        $this->get('/install')
            ->assertOk()
            ->assertSee('环境检测')
            ->assertSee('步骤 1 / 5')
            ->assertSee('连接数据库')
            ->assertSee('站点设置')
            ->assertSee('创建管理员')
            ->assertSee('安装完成');

        $this->post('/install/requirements')
            ->assertRedirect('/install/database');
    }

    public function test_admin_entry_redirects_to_installer_until_installation_is_complete(): void
    {
        $this->get('/admin')->assertRedirect('/install');
        $this->get('/admin/dashboard')->assertRedirect('/install');
    }

    public function test_database_step_defaults_to_preserving_applications_and_cards(): void
    {
        $this->get('/install/database')
            ->assertOk()
            ->assertSee('如果数据库已有数据')
            ->assertSee('保留应用和卡密')
            ->assertSee('默认')
            ->assertSee('清空全部数据')
            ->assertSee('只重置后台管理员、角色权限与系统日志')
            ->assertSee('name="existing_data_action"', false)
            ->assertSee('value="preserve_business"', false)
            ->assertSee('checked', false);
    }

    public function test_reset_all_mode_has_no_repeated_database_name_confirmation(): void
    {
        $this->withSession([
            'install.database' => [
                'database' => 'mybsphp',
                'existing_data_action' => 'reset_all',
                'table_count' => 12,
            ],
            'install.site' => [
                'app_name' => 'MyBsPHP2',
                'app_url' => 'http://127.0.0.1:8091',
                'timezone' => 'Asia/Shanghai',
                'admin_path' => 'admin',
            ],
        ])->get('/install/administrator')
            ->assertOk()
            ->assertSee('确认清空并安装')
            ->assertDontSee('输入完整数据库名称')
            ->assertDontSee('确认清空全部数据并重新安装');
    }

    public function test_installed_site_shows_lock_file_and_deleting_it_reopens_installer(): void
    {
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => '动态授权平台',
            'secondary_password_enabled' => false,
        ]);

        $this->get('/install')
            ->assertOk()
            ->assertSee('已安装')
            ->assertSee('如果需要重新安装，请删除')
            ->assertSee('installed.lock')
            ->assertDontSee('先备份数据库');
        $this->get('/install/complete')
            ->assertOk()
            ->assertSee('已安装')
            ->assertSee('installed.lock');
        $this->get('/admin')->assertNotFound();
        $this->get('/private-console')
            ->assertOk()
            ->assertSee('管理员登录')
            ->assertSee('动态授权平台');
        $this->get('/')
            ->assertOk()
            ->assertSee('我的生活记录')
            ->assertDontSee('动态授权平台');

        config(['mybsphp.installed' => true]);
        @unlink($this->lockPath);

        $this->get('/install')
            ->assertOk()
            ->assertSee('环境检测')
            ->assertSee('步骤 1 / 5')
            ->assertDontSee('如果需要重新安装，请删除');
        $this->get('/private-console')->assertRedirect('/install');
    }

    public function test_one_time_link_opens_a_reinstallation_session_without_exposing_the_token_in_path(): void
    {
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => '动态授权平台',
            'secondary_password_enabled' => false,
        ]);
        $token = app(ReinstallationTokenManager::class)->issue();

        $this->get('/install/reinstall?token='.$token)
            ->assertRedirect('/install/database')
            ->assertSessionHas('install.reinstallation_authorized', true);

        $this->get('/install')->assertRedirect('/install/database');
        $this->get('/install/database')
            ->assertOk()
            ->assertSee('保留应用和卡密')
            ->assertSee('清空全部数据');

        $this->flushSession();
        $this->get('/install/reinstall?token='.$token)->assertNotFound();
    }
}
