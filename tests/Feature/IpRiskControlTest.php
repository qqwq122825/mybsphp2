<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\Role;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class IpRiskControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_login_silently_uses_decoy_then_disguises_high_control_as_server_failure(): void
    {
        $application = $this->application('REAL-VERIFICATION-VALUE');
        $application->riskPolicy()->create([
            'enabled' => true,
            'window_seconds' => 60,
            'low_threshold' => 2,
            'high_threshold' => 3,
            'block_duration_minutes' => 30,
            'low_action' => 'decoy',
            'block_http_status' => 429,
            'block_code' => 'LOGIN_RATE_BLOCKED',
            'block_message' => '登录过于频繁。',
        ]);

        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
            'username' => 'riskuser', 'password' => 'secret123', 'device_id' => 'RISK-DEVICE-001',
        ])->assertCreated();

        $payload = ['username' => 'riskuser', 'password' => 'secret123', 'device_id' => 'RISK-DEVICE-001'];
        $first = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
            ->assertOk()->assertJsonPath('data.payload', 'REAL-VERIFICATION-VALUE');

        $second = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
            ->assertOk()->assertJsonMissingPath('data.risk_control');
        $this->assertNotSame('REAL-VERIFICATION-VALUE', $second->json('data.payload'));
        $this->assertSame($first->json('data.account.expires_at'), $second->json('data.account.expires_at'));

        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
            ->assertStatus(503)
            ->assertHeaderMissing('Retry-After')
            ->assertJsonPath('error.code', 'SERVER_UNAVAILABLE')
            ->assertJsonPath('error.message', '服务器暂时故障，请稍后再试。')
            ->assertJsonMissingPath('error.details');

        $this->assertDatabaseHas('ip_risk_states', [
            'application_id' => $application->getKey(), 'level' => 'high', 'window_attempts' => 3,
        ]);
        $this->assertDatabaseCount('risk_events', 2);
    }

    public function test_debug_mode_returns_plain_low_and_high_control_details(): void
    {
        SystemSetting::query()->create([
            'key' => 'operational_mode', 'value' => 'debug', 'type' => 'string', 'group' => 'security',
        ]);
        $application = $this->application('DEBUG-VERIFICATION-VALUE');
        $application->riskPolicy()->create([
            'enabled' => true, 'window_seconds' => 60, 'low_threshold' => 2, 'high_threshold' => 3,
            'block_duration_minutes' => 30, 'low_action' => 'decoy', 'block_http_status' => 429,
            'block_code' => 'LOGIN_RATE_BLOCKED', 'block_message' => '登录过于频繁。',
        ]);
        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
            'username' => 'debug-risk', 'password' => 'secret123', 'device_id' => 'DEBUG-RISK-DEVICE',
        ])->assertCreated();
        $payload = ['username' => 'debug-risk', 'password' => 'secret123', 'device_id' => 'DEBUG-RISK-DEVICE'];

        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)->assertOk();
        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
            ->assertOk()
            ->assertJsonPath('data.risk_control.level', 'low')
            ->assertJsonPath('data.risk_control.window_attempts', 2);
        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('error.code', 'LOGIN_RATE_BLOCKED')
            ->assertJsonPath('error.details.control_level', 'high')
            ->assertJsonPath('error.details.window_attempts', 3)
            ->assertJsonPath('error.details.high_threshold', 3);
    }

    public function test_application_without_verification_data_uses_observation_at_low_level(): void
    {
        $application = $this->application('');
        $application->riskPolicy()->create([
            'enabled' => true, 'window_seconds' => 60, 'low_threshold' => 2, 'high_threshold' => 10,
            'block_duration_minutes' => 30, 'low_action' => 'decoy', 'block_http_status' => 429,
            'block_code' => 'ACCESS_TEMPORARILY_RESTRICTED', 'block_message' => '请求过于频繁。',
        ]);
        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
            'username' => 'observer', 'password' => 'secret123', 'device_id' => 'OBSERVE-DEVICE',
        ])->assertCreated();
        $payload = ['username' => 'observer', 'password' => 'secret123', 'device_id' => 'OBSERVE-DEVICE'];
        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)->assertOk();
        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
            ->assertOk()->assertJsonPath('data.payload', '');
        $this->assertDatabaseHas('risk_events', ['application_id' => $application->getKey(), 'level' => 'low', 'action' => 'observe']);
    }

    public function test_sliding_window_keeps_recent_requests_when_the_first_request_has_expired(): void
    {
        Carbon::setTestNow('2026-07-23 12:00:00');
        try {
            $application = $this->application('REAL-SLIDING-VALUE');
            $application->riskPolicy()->create([
                'enabled' => true, 'window_seconds' => 10, 'low_threshold' => 3, 'high_threshold' => 8,
                'block_duration_minutes' => 30, 'low_action' => 'decoy', 'block_http_status' => 429,
                'block_code' => 'ACCESS_TEMPORARILY_RESTRICTED', 'block_message' => '请求过于频繁。',
            ]);
            $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
                'username' => 'slider', 'password' => 'secret123', 'device_id' => 'SLIDING-DEVICE',
            ])->assertCreated();
            $payload = ['username' => 'slider', 'password' => 'secret123', 'device_id' => 'SLIDING-DEVICE'];

            $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
                ->assertOk()->assertJsonPath('data.payload', 'REAL-SLIDING-VALUE');

            Carbon::setTestNow(now()->addSeconds(3));
            $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
                ->assertOk()->assertJsonPath('data.payload', 'REAL-SLIDING-VALUE');

            Carbon::setTestNow(now()->addSeconds(3));
            $third = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)->assertOk();
            $this->assertNotSame('REAL-SLIDING-VALUE', $third->json('data.payload'));

            // 第 11 秒：第 0 秒请求移出，但第 3、6、11 秒仍是最近 10 秒内的 3 次。
            Carbon::setTestNow(now()->addSeconds(5));
            $fourth = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)->assertOk();
            $this->assertNotSame('REAL-SLIDING-VALUE', $fourth->json('data.payload'));
            $this->assertDatabaseHas('ip_risk_states', [
                'application_id' => $application->getKey(), 'level' => 'low', 'window_attempts' => 3,
            ]);

            // 第 17 秒：窗口内只剩第 11、17 秒两次，自动恢复正常返回。
            Carbon::setTestNow(now()->addSeconds(6));
            $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
                ->assertOk()->assertJsonPath('data.payload', 'REAL-SLIDING-VALUE');
            $this->assertDatabaseHas('ip_risk_states', [
                'application_id' => $application->getKey(), 'level' => 'normal', 'window_attempts' => 2,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_administrator_can_open_control_center_and_update_policy(): void
    {
        app(SystemAccessSynchronizer::class)->sync();
        $lockPath = storage_path('framework/testing/ip-risk-control.lock');
        @unlink($lockPath);
        config(['mybsphp.installed' => false, 'mybsphp.install_lock' => $lockPath]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console', 'app_name' => '风控测试后台', 'secondary_password_enabled' => false,
        ]);

        try {
            $administrator = Administrator::query()->create([
                'username' => 'risk-admin', 'password' => 'secret', 'role' => 'super_admin', 'status' => 'active',
            ]);
            $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
            $application = $this->application('VERIFY');
            $this->actingAs($administrator, 'admin');

            $this->get('/private-console/risk-control')
                ->assertOk()
                ->assertSee('IP 封控中心')
                ->assertSee('最近 10 秒滚动窗口')
                ->assertSee('请求 4 · 仍计 3 次')
                ->assertSee('当前软件策略')
                ->assertSee('上线运行：谨慎响应')
                ->assertSee('返回软件配置')
                ->assertSee('/private-console/applications/'.$application->public_id.'/edit', false)
                ->assertSee('低级封控')
                ->assertSee('高级封控')
                ->assertSee('风险 IP 列表');

            $this->put('/private-console/risk-control/applications/'.$application->public_id.'/policy', [
                'enabled' => '1', 'window_seconds' => 90, 'low_threshold' => 5, 'high_threshold' => 11,
                'block_duration_minutes' => 120, 'low_action' => 'observe', 'decoy_value' => '',
                'block_http_status' => 403, 'block_code' => 'RISK_BLOCKED', 'block_message' => '当前访问受限。',
            ])->assertRedirect()->assertSessionHas('success');

            $this->assertDatabaseHas('risk_policies', [
                'application_id' => $application->getKey(), 'window_seconds' => 90,
                'low_threshold' => 5, 'high_threshold' => 11, 'low_action' => 'observe',
            ]);
        } finally {
            @unlink($lockPath);
        }
    }

    private function application(string $verificationData): Application
    {
        $credentials = app(ApplicationCredentialService::class)->generate();
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => (string) random_int(700000, 799999),
            'name' => '风控测试软件',
            'mode' => 'account_recharge',
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'payload_mode' => 'constant',
            'payload_value_ciphertext' => $verificationData,
            'client_secret_hash' => hash('sha256', $credentials['communication_key_ciphertext']),
            'bind_device_enabled' => true,
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 1,
            'registration_bonus_value' => 1,
            'registration_bonus_unit' => 'hour',
            ...$credentials,
        ]);
        $application->legacyConfig()->create($application->legacyLoginDefaults());

        return $application;
    }
}
