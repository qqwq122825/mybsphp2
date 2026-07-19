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

    public function test_installed_site_shows_lock_file_and_deleting_it_reopens_installer(): void
    {
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => '动态授权平台',
            'secondary_password_enabled' => false,
        ]);

        $this->get('/install')
            ->assertOk()
            ->assertSee('系统已安装成功')
            ->assertSee('需要删除的文件')
            ->assertSee('storage/framework/testing/installation-flow.lock')
            ->assertSee('/private-console');
        $this->get('/install/complete')
            ->assertOk()
            ->assertSee('系统已安装成功')
            ->assertSee('/private-console');
        $this->get('/admin')->assertNotFound();
        $this->get('/private-console')
            ->assertOk()
            ->assertSee('管理员登录')
            ->assertSee('动态授权平台');
        $this->get('/')->assertOk()->assertSee('动态授权平台');

        config(['mybsphp.installed' => true]);
        @unlink($this->lockPath);

        $this->get('/install')
            ->assertOk()
            ->assertSee('环境检测')
            ->assertSee('步骤 1 / 5')
            ->assertDontSee('系统已安装成功');
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
            ->assertSee('覆盖现有数据库');

        $this->flushSession();
        $this->get('/install/reinstall?token='.$token)->assertNotFound();
    }
}
