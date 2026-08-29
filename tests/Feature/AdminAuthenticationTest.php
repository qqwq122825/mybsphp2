<?php

namespace Tests\Feature;

use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\AuditLog;
use App\Models\GlobalSecurityBlock;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
            ->assertSee('mybsphp.css?v=20260812-select-scroll', false)
            ->assertDontSee('语言');

        $this->post('/private-console/login', [
            'username' => 'admin',
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('login');

        $this->post('/private-console/login', [
            'username' => 'qqq',
            'password' => 'guess-secret',
        ])
            ->assertSessionHasErrors('login');

        $this->assertGuest('admin');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.login_failed',
            'result' => 'failed',
            'reason' => '密码不正确',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.login_failed',
            'result' => 'failed',
            'reason' => '账号不存在',
        ]);
        $failedLogin = AuditLog::query()->where('action', 'admin.login_failed')->where('reason', '密码不正确')->latest('id')->firstOrFail();
        $this->assertNotSame('wrong-password', data_get($failedLogin->after_data, 'attempted_secret_ciphertext'));
        $this->assertSame('wrong-password', $failedLogin->revealAttemptedLoginSecret());

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
            ->assertSee('封控中心')
            ->assertDontSee('风险事件')
            ->assertSee('管理登录日志')
            ->assertSee('系统制卡日志')
            ->assertSee('系统安全防护日志')
            ->assertSee('客户端请求日志')
            ->assertDontSee('诊断编号查询')
            ->assertDontSee('用户反馈')
            ->assertDontSee('邮件设置')
            ->assertDontSee('在线支付');

        $loginLog = AuditLog::query()->where('action', 'admin.login')->firstOrFail();
        $this->get('/private-console/system-logs/login')
            ->assertOk()
            ->assertSee('管理登录日志')
            ->assertSee('后台登录成功')
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
        $this->get('/private-console/system-logs/protection')->assertOk()->assertSee('系统安全防护日志')->assertSee('后台登录失败：账号 admin，密码 wrong-password，密码不正确')->assertSee('后台登录失败：账号 qqq，密码 guess-secret，账号不存在')->assertSee('封禁此 IP')->assertDontSee('查看尝试')->assertDontSee('admin.login_failed');
        $this->get('/private-console/system-logs/client-requests')
            ->assertOk()
            ->assertSee('客户端请求日志')
            ->assertSee('详细请求')
            ->assertDontSee('客户端日志模式')
            ->assertSee('卡密不存在、空卡密、参数错误也会进入此列表');

        AuditLog::query()->create([
            'public_id' => (string) Str::ulid(),
            'actor_role_snapshot' => 'client',
            'action' => 'client.card_login_trace',
            'target_type' => 'application',
            'target_id' => 'trace-app',
            'request_id' => (string) Str::ulid(),
            'ip' => '203.0.113.8',
            'before_data' => [
                'application' => ['name' => '诊断测试软件'],
                'credential_masked' => 'TES***ARD',
                'credential_fingerprint' => 'trace-card',
                'device' => 'REQUEST-DEVICE',
            ],
            'after_data' => [
                'diagnostic_code' => 'DEVICE_MISMATCH',
                'http_status' => 200,
                'bound_device' => 'BOUND-DEVICE',
                'returned_device' => 'RETURNED-DEVICE',
                'request_device_matches_binding' => false,
            ],
            'reason' => '设备对比测试',
            'result' => 'failed',
        ]);

        $this->get('/private-console/system-logs/protection')
            ->assertOk()
            ->assertSee('系统安全防护日志')
            ->assertSee('后台登录失败：账号 admin，密码 wrong-password，密码不正确')
            ->assertSee('封禁此 IP')
            ->assertDontSee('查看尝试')
            ->assertDontSee('admin.login_failed')
            ->assertDontSee('DEVICE_MISMATCH')
            ->assertDontSee('203.0.113.8');

        $this->get('/private-console/system-logs/client-requests?view=detailed')
            ->assertOk()
            ->assertSee('响应摘要')
            ->assertSee('查看详情')
            ->assertSee('快速拉黑')
            ->assertSee('客户端请求详情')
            ->assertSee('data-client-trace-detail', false)
            ->assertSee('DEVICE_MISMATCH')
            ->assertSee('REQUEST-DEVICE')
            ->assertDontSee('client-trace-details');

        AuditLog::query()->create([
            'public_id' => (string) Str::ulid(),
            'actor_role_snapshot' => 'client',
            'action' => 'client.auth_failure_masked',
            'target_type' => 'application',
            'target_id' => 'diagnostic-app',
            'request_id' => (string) Str::ulid(),
            'ip' => '203.0.113.24',
            'before_data' => [
                'application' => ['public_id' => 'diagnostic-app', 'name' => '编号查询软件'],
                'credential' => 'FULL-CARD-LOOKUP-001',
                'credential_masked' => '•••••••••-001',
                'credential_fingerprint' => substr(hash('sha256', 'FULL-CARD-LOOKUP-001'), 0, 16),
                'device' => 'DIAGNOSTIC-DEVICE',
            ],
            'after_data' => [
                'internal_code' => 'DEVICE_BLOCKED',
                'internal_message' => '设备已进入安全封控。',
                'public_code' => 'INVALID_CARD',
                'public_message' => '卡密不存在',
                'http_status' => 200,
            ],
            'reason' => 'DEVICE_BLOCKED',
            'result' => 'failed',
        ]);

        $this->get('/private-console/system-logs/client-requests?view=simple')
            ->assertOk()
            ->assertSee('编号查询软件')
            ->assertSee('DEVICE_BLOCKED')
            ->assertSee('卡密不存在')
            ->assertSee('FULL-CARD-LOOKUP-001')
            ->assertDontSee('•••••••••-001')
            ->assertSee('详细请求')
            ->assertSee('快速拉黑')
            ->assertSee('data-client-trace-detail', false)
            ->assertSee('客户端请求详情');
        $this->from('/private-console/system-logs/client-requests?view=simple')
            ->post('/private-console/risk-control/global-blocks', [
                'device_serial' => 'DIAGNOSTIC-DEVICE',
                'ip' => '203.0.113.24',
                'note' => '客户端请求日志快速拉黑',
            ])
            ->assertRedirect('/private-console/system-logs/client-requests?view=simple')
            ->assertSessionHas('success');
        $this->assertDatabaseHas('global_security_blocks', [
            'subject_type' => 'device',
            'subject_hash' => GlobalSecurityBlock::hashValue('DIAGNOSTIC-DEVICE'),
            'subject_value' => 'DIAGNOSTIC-DEVICE',
        ]);
        $this->assertDatabaseHas('global_security_blocks', [
            'subject_type' => 'ip',
            'subject_hash' => GlobalSecurityBlock::hashValue('203.0.113.24'),
            'subject_value' => '203.0.113.24',
        ]);
        $this->get('/private-console/system-logs/client-requests?view=simple')
            ->assertOk()
            ->assertSee('已封禁')
            ->assertDontSee('快速拉黑');
        $this->get('/private-console/system-logs/client-requests?view=simple&keyword=FULL-CARD-LOOKUP-001')
            ->assertOk()
            ->assertSee('DEVICE_BLOCKED')
            ->assertSee('DIAGNOSTIC-DEVICE');

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
        $this->get('/private-console/system-logs/protection')
            ->assertOk()
            ->assertSee('后台登录失败：账号 admin，未填写二级密码')
            ->assertSee('后台登录失败：账号 admin，二级密码 wrong-secondary，二级密码不正确')
            ->assertDontSee('primary-secret')
            ->assertSee('封禁此 IP');
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
