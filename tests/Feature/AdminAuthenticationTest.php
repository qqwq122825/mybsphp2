<?php

namespace Tests\Feature;

use App\Auth\AdminSession;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\AuditLog;
use App\Models\GlobalSecurityBlock;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->assertSee('mybsphp.css?v='.filemtime(public_path('assets/css/mybsphp.css')), false)
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
        $this->assertNull($failedLogin->revealAttemptedLoginSecret());
        $this->assertSame(['username' => 'admin'], $failedLogin->after_data);

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
            ->assertSee('黑名单请求日志')
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
        $this->get('/private-console/system-logs/protection')->assertOk()->assertSee('系统安全防护日志')->assertSee('后台登录失败：账号 admin，密码不正确')->assertSee('后台登录失败：账号 qqq，账号不存在')->assertDontSee('封禁此 IP')->assertDontSee('查看尝试')->assertDontSee('admin.login_failed');
        $this->get('/private-console/system-logs/client-requests')
            ->assertOk()
            ->assertSee('客户端请求日志')
            ->assertSee('详细请求')
            ->assertDontSee('客户端日志模式')
            ->assertSee('错误、万能卡密，以及全部模式下的正常登录')
            ->assertSee('卡密不存在、空卡密、参数错误也会进入此列表');
        $this->get('/private-console/system-logs/blocklist-requests')
            ->assertOk()
            ->assertSee('黑名单请求日志')
            ->assertSee('设备已拉黑后仍继续登录的请求')
            ->assertDontSee('快速拉黑')
            ->assertDontSee('>处置</th>', false);

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
            ->assertSee('后台登录失败：账号 admin，密码不正确')
            ->assertDontSee('封禁此 IP')
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
                'internal_code' => 'CARD_EXPIRED',
                'internal_message' => '该卡密已经到期。',
                'public_code' => 'INVALID_CARD',
                'public_message' => '卡密不存在',
                'http_status' => 200,
            ],
            'reason' => 'CARD_EXPIRED',
            'result' => 'failed',
        ]);

        $this->get('/private-console/system-logs/client-requests?view=simple')
            ->assertOk()
            ->assertSee('编号查询软件')
            ->assertSee('CARD_EXPIRED')
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
                'source' => 'quick',
                'note' => '客户端请求日志快速拉黑',
            ])
            ->assertRedirect('/private-console/system-logs/client-requests?view=simple')
            ->assertSessionHas('success', '已快速拉黑该设备，任意软件登录将显示卡密不存在。');
        $this->assertDatabaseHas('global_security_blocks', [
            'subject_type' => 'device',
            'subject_hash' => GlobalSecurityBlock::hashValue('DIAGNOSTIC-DEVICE'),
            'subject_value' => 'DIAGNOSTIC-DEVICE',
            'reason' => 'QUICK_BLOCK',
        ]);
        $this->assertDatabaseMissing('global_security_blocks', [
            'subject_type' => 'ip',
            'subject_hash' => GlobalSecurityBlock::hashValue('203.0.113.24'),
            'subject_value' => '203.0.113.24',
            'reason' => 'QUICK_BLOCK',
        ]);
        $this->get('/private-console/system-logs/client-requests?view=simple')
            ->assertOk()
            ->assertSee('已封禁')
            ->assertDontSee('client-trace-block-button', false);
        $this->get('/private-console/system-logs/client-requests?view=simple&keyword=FULL-CARD-LOOKUP-001')
            ->assertOk()
            ->assertSee('CARD_EXPIRED')
            ->assertSee('DIAGNOSTIC-DEVICE');

        AuditLog::query()->create([
            'public_id' => (string) Str::ulid(),
            'actor_role_snapshot' => 'client',
            'action' => 'client.blocklist_hit',
            'target_type' => 'application',
            'target_id' => 'diagnostic-app',
            'request_id' => (string) Str::ulid(),
            'ip' => '203.0.113.24',
            'before_data' => [
                'application' => ['public_id' => 'diagnostic-app', 'name' => '编号查询软件'],
                'credential' => 'FULL-CARD-LOOKUP-001',
                'device' => 'DIAGNOSTIC-DEVICE',
            ],
            'after_data' => [
                'internal_code' => 'DEVICE_BLOCKED',
                'internal_message' => '设备已拉黑，登录 IP 也在黑名单。',
                'public_code' => 'INVALID_CARD',
                'public_message' => '卡密不存在',
                'http_status' => 200,
            ],
            'reason' => 'DEVICE_BLOCKED',
            'result' => 'failed',
        ]);
        $this->get('/private-console/system-logs/blocklist-requests')
            ->assertOk()
            ->assertSee('DEVICE_BLOCKED')
            ->assertSee('设备已拉黑，登录 IP 也在黑名单。')
            ->assertDontSee('CARD_EXPIRED')
            ->assertDontSee('快速拉黑')
            ->assertDontSee('已封禁');
        $this->get('/private-console/system-logs/client-requests?view=simple')
            ->assertOk()
            ->assertDontSee('设备已拉黑，登录 IP 也在黑名单。');

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
            ->assertSee('后台登录失败：账号 admin，二级密码不正确')
            ->assertDontSee('primary-secret')
            ->assertDontSee('封禁此 IP');
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

    public function test_new_login_replaces_old_token_and_old_logout_does_not_revoke_new_login(): void
    {
        $this->install();
        $admin = $this->administrator();
        $credentials = ['username' => 'admin', 'password' => 'primary-secret'];
        $this->post('/private-console/login', $credentials)->assertRedirect('/private-console/dashboard');
        $old = session()->all();
        $this->post('/private-console/login', $credentials)->assertRedirect('/private-console/dashboard');
        $new = session()->all();
        $this->assertNotSame($old[AdminSession::KEY], $new[AdminSession::KEY]);
        $this->assertSame(hash('sha256', $new[AdminSession::KEY]), $admin->fresh()->admin_session_token_hash);

        $this->switchBrowser($old);
        $this->post('/private-console/logout')->assertRedirect('/private-console');
        $this->assertSame(hash('sha256', $new[AdminSession::KEY]), $admin->fresh()->admin_session_token_hash);
        $this->switchBrowser($old);
        $this->get('/private-console/dashboard')->assertRedirect('/private-console');
        $this->switchBrowser($new);
        $this->get('/private-console/dashboard')->assertOk();
        $this->post('/private-console/logout')->assertRedirect('/private-console');
        $this->assertNull($admin->fresh()->admin_session_token_hash);
        $this->switchBrowser($new);
        $this->get('/private-console/dashboard')->assertRedirect('/private-console');
    }

    public function test_missing_token_and_frozen_account_are_rejected(): void
    {
        $this->install();
        $admin = $this->administrator();
        $this->post('/private-console/login', ['username' => 'admin', 'password' => 'primary-secret']);
        $browser = session()->all();
        $missing = $browser;
        unset($missing[AdminSession::KEY]);
        $this->switchBrowser($missing);
        $this->get('/private-console/dashboard')->assertRedirect('/private-console');
        $admin->update(['status' => 'frozen']);
        $this->assertNull($admin->fresh()->admin_session_token_hash);
        $this->switchBrowser($browser);
        $this->get('/private-console/dashboard')->assertRedirect('/private-console');
    }

    public function test_password_reset_invalidates_session_and_inflight_login(): void
    {
        $this->install();
        $admin = $this->administrator();
        $this->post('/private-console/login', ['username' => 'admin', 'password' => 'primary-secret']);
        $stale = $admin->fresh();
        $admin->update(['password' => 'replacement-secret']);
        $this->assertNull($admin->fresh()->admin_session_token_hash);
        $request = Request::create('/');
        $request->setLaravelSession(session()->driver());
        $this->assertFalse(app(AdminSession::class)->issue($request, $stale));
        $this->get('/private-console/dashboard')->assertRedirect('/private-console');
    }

    public function test_self_security_requires_both_existing_passwords_and_reauthenticates_after_rename(): void
    {
        $this->install();
        $admin = $this->administrator('second-secret');
        $this->post('/private-console/login', ['username' => 'admin', 'password' => 'primary-secret', 'secondary_password' => 'second-secret']);
        $this->get('/private-console/administrators/security')->assertOk()->assertSee('当前二级密码');
        $data = ['username' => 'renamed-admin', 'current_password' => 'primary-secret', 'secondary_action' => 'keep'];
        $this->put('/private-console/administrators/security', $data)->assertSessionHasErrors('current_secondary_password');
        $this->put('/private-console/administrators/security', $data + ['current_secondary_password' => 'second-secret'])->assertRedirect('/private-console');
        $this->assertGuest('admin');
        $this->assertNull($admin->fresh()->admin_session_token_hash);
        $this->post('/private-console/login', ['username' => 'renamed-admin', 'password' => 'primary-secret', 'secondary_password' => 'second-secret'])->assertRedirect('/private-console/dashboard');
    }

    private function switchBrowser(array $session): void
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->withSession($session);
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
