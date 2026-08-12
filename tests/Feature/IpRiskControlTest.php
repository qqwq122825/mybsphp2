<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Auth\SystemAccessSynchronizer;
use App\Cards\CardCredentialHasher;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\ApplicationClientSession;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use App\Models\ClientSecurityEvent;
use App\Models\ClientSecuritySubjectState;
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
            ->assertOk()->assertJsonMissingPath('data.payload');

        $second = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
            ->assertOk()->assertJsonMissingPath('data.risk_control');
        $second->assertJsonMissingPath('data.payload');
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
            ->assertOk()->assertJsonMissingPath('data.payload');
        $this->assertDatabaseHas('risk_events', ['application_id' => $application->getKey(), 'level' => 'low', 'action' => 'observe']);
    }

    public function test_active_session_restarts_are_deduplicated_but_failed_passwords_are_counted(): void
    {
        $application = $this->application('RESTART-VALUE');
        $application->riskPolicy()->create([
            'enabled' => true, 'window_seconds' => 10, 'restart_grace_seconds' => 4,
            'low_threshold' => 5, 'high_threshold' => 7, 'block_duration_minutes' => 30,
            'low_action' => 'observe', 'block_http_status' => 429,
            'block_code' => 'ACCESS_TEMPORARILY_RESTRICTED', 'block_message' => '请求过于频繁。',
        ]);
        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
            'username' => 'restart-user', 'password' => 'secret123', 'device_id' => 'RESTART-DEVICE',
        ])->assertCreated();
        $payload = [
            'username' => 'restart-user', 'password' => 'secret123', 'device_id' => 'RESTART-DEVICE',
            'BSphpSeSsL' => str_repeat('r', 64),
        ];
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)->assertOk();
        }
        $this->assertDatabaseHas('ip_risk_states', [
            'application_id' => $application->getKey(), 'window_attempts' => 1, 'total_attempts' => 5,
        ]);

        for ($i = 0; $i < 4; $i++) {
            $failed = $payload;
            $failed['password'] = 'wrong-password-'.$i;
            $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $failed)->assertStatus(401);
        }
        $this->assertDatabaseHas('ip_risk_states', [
            'application_id' => $application->getKey(), 'window_attempts' => 5, 'total_attempts' => 9,
        ]);
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
                ->assertOk()->assertJsonMissingPath('data.payload');

            Carbon::setTestNow(now()->addSeconds(3));
            $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
                ->assertOk()->assertJsonMissingPath('data.payload');

            Carbon::setTestNow(now()->addSeconds(3));
            $third = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)->assertOk();
            $third->assertJsonMissingPath('data.payload');

            // 第 11 秒：第 0 秒请求移出，但第 3、6、11 秒仍是最近 10 秒内的 3 次。
            Carbon::setTestNow(now()->addSeconds(5));
            $fourth = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)->assertOk();
            $fourth->assertJsonMissingPath('data.payload');
            $this->assertDatabaseHas('ip_risk_states', [
                'application_id' => $application->getKey(), 'level' => 'low', 'window_attempts' => 3,
            ]);

            // 第 17 秒：窗口内只剩第 11、17 秒两次，自动恢复正常返回。
            Carbon::setTestNow(now()->addSeconds(6));
            $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
                ->assertOk()->assertJsonMissingPath('data.payload');
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
            ClientSecuritySubjectState::query()->create([
                'application_id' => $application->getKey(), 'device_hash' => hash('sha256', 'BLOCKED-DEVICE'),
                'risk_score' => 100, 'status' => 'blocked', 'last_event_at' => now(), 'reason_code' => 'S0',
            ]);
            $securityCardType = CardType::query()->create([
                'public_id' => (string) Str::ulid(), 'application_id' => $application->getKey(),
                'name' => '风控登录卡', 'code' => 'risk-login', 'purpose' => 'login_credential',
                'benefit_kind' => 'duration', 'duration_seconds' => 86400,
                'duration_value' => 1, 'duration_unit' => 'day', 'code_length' => 24,
                'secret_length' => 0, 'status' => 'active',
            ]);
            $securityBatch = CardBatch::query()->create([
                'public_id' => (string) Str::ulid(), 'card_type_id' => $securityCardType->getKey(),
                'batch_no' => 'RISK-'.Str::upper(Str::random(10)), 'source' => 'generated',
                'planned_quantity' => 1, 'actual_quantity' => 1, 'status' => 'ready',
            ]);
            $securityCardCode = 'RISK-LOGIN-CARD-000001';
            $securityCard = Card::query()->create([
                'public_id' => (string) Str::ulid(), 'batch_id' => $securityBatch->getKey(),
                'card_type_id' => $securityCardType->getKey(), 'application_id' => $application->getKey(),
                'code_lookup_hash' => app(CardCredentialHasher::class)->lookupHash($securityCardCode),
                'code_ciphertext' => $securityCardCode, 'code_last4' => '0001',
                'status' => 'issued', 'card_is_frozen' => true,
                'bound_device_ciphertext' => 'BLOCKED-DEVICE', 'bound_device_mask' => 'BLOCKED-DEVICE',
            ]);
            $securitySession = ApplicationClientSession::query()->create([
                'public_id' => (string) Str::ulid(), 'application_id' => $application->getKey(),
                'token_hash' => hash('sha256', 'risk-device-session'), 'auth_mode' => 'card',
                'card_id' => $securityCard->getKey(),
                'device_id_ciphertext' => 'BLOCKED-DEVICE', 'device_mask' => 'BLOCKED-DEVICE',
                'status' => 'security_blocked', 'last_heartbeat_at' => now(),
            ]);
            ClientSecurityEvent::query()->create([
                'public_id' => (string) Str::ulid(), 'application_id' => $application->getKey(),
                'application_client_session_id' => $securitySession->getKey(),
                'card_id' => $securityCard->getKey(),
                'event_id' => '01RISKDEVICEEVENT000000000001', 'event_code' => 'S0',
                'severity' => 'high', 'ip_hash' => hash('sha256', '127.0.0.1'),
                'device_hash' => hash('sha256', 'BLOCKED-DEVICE'), 'evidence' => [],
                'action' => 'freeze', 'created_at' => now(),
            ]);
            $this->actingAs($administrator, 'admin');

            $this->get('/private-console/risk-control')
                ->assertOk()
                ->assertSee($application->name.' · 封控设置')
                ->assertSee('请求频率封控')
                ->assertSee('客户端蜜罐封控')
                ->assertSee('心跳和安全上报不计入此处')
                ->assertSee('最近 10 秒滚动窗口')
                ->assertSee('请求 4 · 仍计 3 次')
                ->assertDontSee('当前软件策略')
                ->assertSee('上线运行：谨慎响应')
                ->assertDontSee('返回软件配置')
                ->assertDontSee('/private-console/applications/'.$application->public_id.'/edit', false)
                ->assertSee('低级封控')
                ->assertSee('高级封控')
                ->assertSee('IP 封控')
                ->assertSee('蜜罐封控')
                ->assertDontSee('蜜罐事件与异常退出策略')
                ->assertSee('风险 IP 列表');

            $this->get('/private-console/risk-control?application='.$application->public_id.'&tab=honeypot')
                ->assertOk()
                ->assertSee('蜜罐事件与异常退出策略')
                ->assertSee('设备黑名单')
                ->assertSee('当前有 1 台设备处于黑名单')
                ->assertSee('设备序列号')
                ->assertSee('BLOCKED-DEVICE')
                ->assertSee('已关联绑定设备')
                ->assertSee('登录卡密')
                ->assertSee($securityCardCode)
                ->assertSee('登录 IP')
                ->assertSee(substr(hash('sha256', 'BLOCKED-DEVICE'), 0, 12).'…'.substr(hash('sha256', 'BLOCKED-DEVICE'), -8))
                ->assertSee('使用新卡密时也会进入蜜罐返回')
                ->assertDontSee('风险 IP 列表');

            $this->put('/private-console/risk-control/applications/'.$application->public_id.'/policy', [
                'enabled' => '1', 'window_seconds' => 90, 'low_threshold' => 5, 'high_threshold' => 11,
                'block_duration_minutes' => 120, 'low_action' => 'observe', 'decoy_value' => '',
                'security_report_mode' => 'disabled',
                'block_http_status' => 403, 'block_code' => 'RISK_BLOCKED', 'block_message' => '当前访问受限。',
            ])->assertRedirect()->assertSessionHas('success');

            $this->assertDatabaseHas('risk_policies', [
                'application_id' => $application->getKey(), 'window_seconds' => 90,
                'low_threshold' => 5, 'high_threshold' => 11, 'low_action' => 'observe',
                'security_report_mode' => 'disabled',
            ]);
            $this->put('/private-console/risk-control/applications/'.$application->public_id.'/security-policy', [
                'security_report_mode' => 'enforce',
                'abnormal_exit_window_minutes' => 60,
                'abnormal_exit_high_threshold' => 8,
            ])->assertRedirect()->assertSessionHas('success');
            $this->assertDatabaseHas('risk_policies', [
                'application_id' => $application->getKey(), 'security_report_mode' => 'enforce',
                'abnormal_exit_window_minutes' => 60, 'abnormal_exit_high_threshold' => 8,
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
