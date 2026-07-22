<?php

namespace Tests\Feature;

use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lockPath = storage_path('framework/testing/admin-authentication.lock');
        @unlink($this->lockPath);
        config([
            'mybsphp.installed' => false,
            'mybsphp.install_lock' => $this->lockPath,
            'mybsphp.login.failure_delay_ms' => 0,
        ]);
        RateLimiter::clear('admin-login:ip:'.hash('sha256', '127.0.0.1'));
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);

        parent::tearDown();
    }

    public function test_administrator_can_log_in_view_dynamic_dashboard_and_log_out(): void
    {
        $this->install();
        $administrator = $this->administrator();

        $this->get('/private-console')
            ->assertOk()
            ->assertSee('管理员登录')
            ->assertSee('安全码')
            ->assertSee('二级密码')
            ->assertDontSee('语言');

        $this->post('/private-console/login', [
            'username' => 'admin',
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('login');

        $this->assertGuest('admin');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.login_failed',
            'result' => 'failed',
        ]);

        $this->post('/private-console/login', [
            'username' => 'admin',
            'password' => 'primary-secret',
        ])
            ->assertRedirect('/private-console/dashboard');

        $this->assertAuthenticatedAs($administrator->fresh(), 'admin');

        $this->get('/private-console/dashboard')
            ->assertOk()
            ->assertSee('管理首页')
            ->assertSee('以下数据来自当前数据库')
            ->assertSee('admin')
            ->assertSee('系统运行正常')
            ->assertSee('控制台')
            ->assertSee('应用管理')
            ->assertDontSee('卡密管理')
            ->assertSee('账号管理')
            ->assertSee('系统配置')
            ->assertDontSee('风险事件')
            ->assertSee('管理登录日志')
            ->assertSee('系统制卡日志')
            ->assertSee('系统安全防护日志')
            ->assertDontSee('用户反馈')
            ->assertDontSee('邮件设置')
            ->assertDontSee('在线支付');

        $loginLog = AuditLog::query()->where('action', 'admin.login')->firstOrFail();
        $this->get('/private-console/system-logs/login')
            ->assertOk()
            ->assertSee('管理登录日志')
            ->assertSee('admin.login')
            ->assertSee('data-card-select-all', false)
            ->assertSee('name="log_ids[]"', false)
            ->assertSee('删除选定');
        $this->post('/private-console/system-logs/login/maintenance', [
            'action' => 'delete_selected',
            'log_ids' => [$loginLog->getKey()],
        ])->assertRedirect();
        $this->assertDatabaseMissing('audit_logs', ['id' => $loginLog->getKey()]);
        $this->get('/private-console/system-logs/card-making')
            ->assertOk()
            ->assertSee('代理制卡日志')
            ->assertSee('data-table-density-toggle', false)
            ->assertSee('data-table-export', false)
            ->assertDontSee('<th>结果</th>', false);
        $this->get('/private-console/system-logs/protection')->assertOk()->assertSee('系统安全防护日志')->assertSee('admin.login_failed');

        $this->post('/private-console/logout')
            ->assertRedirect('/private-console');
        $this->assertGuest('admin');
        $this->get('/private-console/dashboard')->assertRedirect('/private-console');
    }

    public function test_secondary_password_is_required_by_account_even_when_install_flag_is_disabled(): void
    {
        $this->install(secondaryPasswordEnabled: false);
        $this->administrator(secondaryPassword: 'second-secret');

        $this->get('/private-console')
            ->assertOk()
            ->assertSee('二级密码');

        $this->post('/private-console/login', [
            'username' => 'admin',
            'password' => 'primary-secret',
        ])->assertSessionHasErrors('login');

        $this->assertGuest('admin');

        $this->post('/private-console/login', [
            'username' => 'admin',
            'password' => 'primary-secret',
            'secondary_password' => 'wrong-secondary',
        ])->assertSessionHasErrors('login');

        $this->assertGuest('admin');

        $this->post('/private-console/login', [
            'username' => 'admin',
            'password' => 'primary-secret',
            'secondary_password' => 'second-secret',
        ])->assertRedirect('/private-console/dashboard');

        $this->assertAuthenticated('admin');
    }

    public function test_login_rate_limit_is_applied_by_account_and_ip(): void
    {
        $this->install();
        $this->administrator();
        config(['mybsphp.login.maximum_attempts' => 2]);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post('/private-console/login', [
                'username' => 'admin',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('login');
        }

        $this->post('/private-console/login', [
            'username' => 'admin',
            'password' => 'primary-secret',
        ])
            ->assertSessionHasErrors('login');

        $this->assertGuest('admin');
        $message = session('errors')?->first('login');
        $this->assertIsString($message);
        $this->assertStringContainsString('秒后重试', $message);
    }

    private function install(bool $secondaryPasswordEnabled = false): void
    {
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => 'MyBsPHP2 Dynamic',
            'secondary_password_enabled' => $secondaryPasswordEnabled,
        ]);
    }

    private function administrator(?string $secondaryPassword = null): Administrator
    {
        $administrator = Administrator::query()->create([
            'username' => 'admin',
            'password' => 'primary-secret',
            'secondary_password' => $secondaryPassword,
            'secondary_password_required' => $secondaryPassword !== null,
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $administrator->roles()->attach(
            Role::query()->where('code', 'super_admin')->sole(),
        );

        return $administrator;
    }
}
