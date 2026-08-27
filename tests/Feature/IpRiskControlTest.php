<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Applications\HybridPacketCipher;
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
use App\Models\IpRiskState;
use App\Models\Role;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IpRiskControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_logins_do_not_trigger_ip_blocks(): void
    {
        $application = $this->application('REAL-VERIFICATION-VALUE');
        $application->riskPolicy()->create([
            'enabled' => true,
            'invalid_card_threshold' => 3,
            'invalid_card_window_seconds' => 60,
            'block_http_status' => 429,
            'block_code' => 'LOGIN_RATE_BLOCKED',
            'block_message' => '登录过于频繁。',
        ]);

        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
            'username' => 'riskuser', 'password' => 'secret123', 'device_id' => 'RISK-DEVICE-001',
        ])->assertCreated();

        $payload = ['username' => 'riskuser', 'password' => 'secret123', 'device_id' => 'RISK-DEVICE-001'];
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', $payload)
                ->assertOk()
                ->assertJsonMissingPath('data.risk_control');
        }

        $this->assertDatabaseMissing('ip_risk_states', [
            'application_id' => $application->getKey(), 'level' => 'high',
        ]);
        $this->assertDatabaseCount('risk_events', 0);
    }

    public function test_three_invalid_card_probes_in_one_minute_block_device_and_ip(): void
    {
        [$application, $gateway] = $this->cardLoginApplication();
        $application->riskPolicy()->create([
            'enabled' => true,
            'invalid_card_threshold' => 3,
            'invalid_card_window_seconds' => 60,
            'block_http_status' => 429,
            'block_code' => 'CARD_PROBE_BLOCKED',
            'block_message' => '套卡探测已拦截。',
        ]);
        $cipher = app(HybridPacketCipher::class);
        $device = 'PROBE-DEVICE-001';

        for ($i = 1; $i <= 2; $i++) {
            $this->assertMaskedCardFailure($this->postJson($gateway, $cipher->sealRequestWithContext($application, http_build_query([
                'api' => 'login.ic', 'icid' => 'FAKE-CARD-'.$i, 'key' => $device,
                'appsafecode' => 'SAFE', 'BSphpSeSsL' => str_repeat((string) $i, 64),
            ], '', '&', PHP_QUERY_RFC3986))['envelope']));
        }
        $this->assertDatabaseMissing('client_security_subject_states', [
            'device_hash' => hash('sha256', $device), 'status' => 'blocked',
        ]);

        $this->assertMaskedCardFailure($this->postJson($gateway, $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => 'FAKE-CARD-3', 'key' => $device,
            'appsafecode' => 'SAFE', 'BSphpSeSsL' => str_repeat('3', 64),
        ], '', '&', PHP_QUERY_RFC3986))['envelope']));

        $this->assertDatabaseHas('client_security_subject_states', [
            'application_id' => $application->getKey(),
            'device_hash' => hash('sha256', $device),
            'status' => 'blocked',
            'reason_code' => 'C0',
        ]);
        $this->assertDatabaseHas('ip_risk_states', [
            'application_id' => $application->getKey(),
            'level' => 'high',
            'permanently_blocked' => true,
        ]);
        $this->assertDatabaseHas('risk_events', [
            'application_id' => $application->getKey(),
            'level' => 'high',
            'action' => 'block',
        ]);
    }

    public function test_empty_and_malformed_card_errors_count_toward_probe_threshold(): void
    {
        [$application, $gateway] = $this->cardLoginApplication();
        $application->riskPolicy()->create([
            'enabled' => true,
            'invalid_card_threshold' => 3,
            'invalid_card_window_seconds' => 60,
            'block_http_status' => 429,
            'block_code' => 'CARD_PROBE_BLOCKED',
            'block_message' => '套卡探测已拦截。',
        ]);
        $cipher = app(HybridPacketCipher::class);
        $device = 'PROBE-EMPTY-DEVICE';

        $this->postJson($gateway, $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => '', 'key' => $device, 'appsafecode' => 'SAFE',
            'BSphpSeSsL' => str_repeat('a', 64),
        ], '', '&', PHP_QUERY_RFC3986))['envelope'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'EMPTY_CARD');

        $this->postJson($gateway, $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => str_repeat('X', 65), 'key' => $device, 'appsafecode' => 'SAFE',
            'BSphpSeSsL' => str_repeat('b', 64),
        ], '', '&', PHP_QUERY_RFC3986))['envelope'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_PARAMETERS');

        $this->assertMaskedCardFailure($this->postJson($gateway, $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => 'UNKNOWN-CARD', 'key' => $device, 'appsafecode' => 'SAFE',
            'BSphpSeSsL' => str_repeat('c', 64),
        ], '', '&', PHP_QUERY_RFC3986))['envelope']));

        $this->assertDatabaseHas('client_security_subject_states', [
            'device_hash' => hash('sha256', $device), 'status' => 'blocked',
        ]);
        $this->assertDatabaseHas('ip_risk_states', [
            'application_id' => $application->getKey(), 'permanently_blocked' => true,
        ]);
    }

    public function test_probe_window_expiry_does_not_block_normal_retries(): void
    {
        Carbon::setTestNow('2026-08-28 12:00:00');
        try {
            [$application, $gateway] = $this->cardLoginApplication();
            $application->riskPolicy()->create([
                'enabled' => true,
                'invalid_card_threshold' => 3,
                'invalid_card_window_seconds' => 60,
                'block_http_status' => 429,
                'block_code' => 'CARD_PROBE_BLOCKED',
                'block_message' => '套卡探测已拦截。',
            ]);
            $cipher = app(HybridPacketCipher::class);
            $device = 'PROBE-WINDOW-DEVICE';
            foreach (['A', 'B'] as $suffix) {
                $this->assertMaskedCardFailure($this->postJson($gateway, $cipher->sealRequestWithContext($application, http_build_query([
                    'api' => 'login.ic', 'icid' => 'FAKE-'.$suffix, 'key' => $device,
                    'appsafecode' => 'SAFE', 'BSphpSeSsL' => str_repeat($suffix, 64),
                ], '', '&', PHP_QUERY_RFC3986))['envelope']));
            }
            Carbon::setTestNow(now()->addSeconds(61));
            $this->assertMaskedCardFailure($this->postJson($gateway, $cipher->sealRequestWithContext($application, http_build_query([
                'api' => 'login.ic', 'icid' => 'FAKE-C', 'key' => $device,
                'appsafecode' => 'SAFE', 'BSphpSeSsL' => str_repeat('C', 64),
            ], '', '&', PHP_QUERY_RFC3986))['envelope']));
            $this->assertDatabaseMissing('client_security_subject_states', [
                'device_hash' => hash('sha256', $device), 'status' => 'blocked',
            ]);
            $this->assertDatabaseHas('ip_risk_states', [
                'application_id' => $application->getKey(), 'permanently_blocked' => false,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_manual_global_device_block_applies_to_all_applications(): void
    {
        app(SystemAccessSynchronizer::class)->sync();
        $lockPath = storage_path('framework/testing/global-block.lock');
        @unlink($lockPath);
        config(['mybsphp.installed' => false, 'mybsphp.install_lock' => $lockPath]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console', 'app_name' => '风控测试后台', 'secondary_password_enabled' => false,
        ]);
        try {
            $administrator = Administrator::query()->create([
                'username' => 'global-admin', 'password' => 'secret', 'role' => 'super_admin', 'status' => 'active',
            ]);
            $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
            [$first] = $this->cardLoginApplication('100101');
            [$second, $secondGateway] = $this->cardLoginApplication('100102');
            $this->actingAs($administrator, 'admin');

            $this->post('/private-console/risk-control/global-blocks', [
                'device_serial' => 'GLOBAL-SERIAL-001',
                'ip' => '203.0.113.88',
                'note' => '人工全软件封禁',
            ])->assertRedirect()->assertSessionHas('success');

            $this->assertDatabaseHas('global_security_blocks', [
                'subject_type' => 'device',
                'subject_hash' => hash('sha256', 'GLOBAL-SERIAL-001'),
                'subject_value' => 'GLOBAL-SERIAL-001',
            ]);
            $this->assertDatabaseHas('global_security_blocks', [
                'subject_type' => 'ip',
                'subject_value' => '203.0.113.88',
            ]);
            $this->assertDatabaseHas('client_security_subject_states', [
                'application_id' => $first->getKey(),
                'device_hash' => hash('sha256', 'GLOBAL-SERIAL-001'),
                'status' => 'blocked',
            ]);
            $this->assertDatabaseHas('client_security_subject_states', [
                'application_id' => $second->getKey(),
                'device_hash' => hash('sha256', 'GLOBAL-SERIAL-001'),
                'status' => 'blocked',
            ]);

            $cipher = app(HybridPacketCipher::class);
            $this->assertMaskedCardFailure($this->postJson($secondGateway, $cipher->sealRequestWithContext($second, http_build_query([
                'api' => 'login.ic', 'icid' => 'ANY-CARD-CODE', 'key' => 'GLOBAL-SERIAL-001',
                'appsafecode' => 'SAFE', 'BSphpSeSsL' => str_repeat('g', 64),
            ], '', '&', PHP_QUERY_RFC3986))['envelope']));
        } finally {
            @unlink($lockPath);
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
            IpRiskState::query()->create([
                'public_id' => (string) Str::ulid(), 'application_id' => $application->getKey(),
                'ip' => '2409:8a55:d336:d350:c5ac:6a09:45d4:572a',
                'ip_hash' => hash('sha256', '2409:8a55:d336:d350:c5ac:6a09:45d4:572a'),
                'level' => 'high', 'window_attempts' => 3, 'total_attempts' => 3,
                'permanently_blocked' => true,
                'last_device_hash' => hash('sha256', 'BLOCKED-DEVICE'),
                'last_seen_at' => now(), 'last_endpoint' => 'client.security-event',
            ]);
            $deviceState = ClientSecuritySubjectState::query()->create([
                'application_id' => $application->getKey(), 'device_hash' => hash('sha256', 'BLOCKED-DEVICE'),
                'risk_score' => 100, 'status' => 'blocked', 'last_event_at' => now(), 'reason_code' => 'T1',
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
                'status' => 'active', 'card_is_frozen' => false, 'user_status' => 'frozen',
                'activated_at' => now(), 'expires_at' => now()->addDay(), 'user_frozen_at' => now(),
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
                'event_id' => str_repeat('e', 32), 'event_code' => 'T1',
                'severity' => 'high', 'ip_hash' => hash('sha256', '127.0.0.1'),
                'device_hash' => hash('sha256', 'BLOCKED-DEVICE'),
                'evidence' => [
                    'version' => 3, 'revision' => 8, 'count' => 8, 'short_runs' => 8,
                    'recent_count' => 8, 'pending_burst' => 8, 'effective_count' => 8,
                ],
                'action' => 'freeze', 'created_at' => now(),
            ]);
            $this->actingAs($administrator, 'admin');

            $this->get('/private-console/risk-control')
                ->assertOk()
                ->assertSee($application->code.'-封控设置')
                ->assertSee('data-workspace-bootstrap-title="'.$application->code.'-封控设置"', false)
                ->assertSee($application->name.' · 封控设置')
                ->assertSee('套卡探测')
                ->assertSee('蜜罐策略')
                ->assertSee('只统计卡密不存在、空卡密、乱输入')
                ->assertSee('正常登录不计入')
                ->assertDontSee('当前软件策略')
                ->assertSee('上线运行：谨慎响应')
                ->assertDontSee('返回软件配置')
                ->assertDontSee('/private-console/applications/'.$application->public_id.'/edit', false)
                ->assertSee('software-config-form', false)
                ->assertSee('software-basic-row', false)
                ->assertSee('蜜罐事件与异常退出策略')
                ->assertSee('封控中心')
                ->assertSee('/private-console/risk-control/center', false)
                ->assertDontSee('主动添加封禁')
                ->assertDontSee('设备黑名单')
                ->assertDontSee('风险 IP 列表')
                ->assertDontSee('BLOCKED-DEVICE')
                ->assertDontSee('data-risk-action-trigger', false);

            $this->get('/private-console/risk-control/center')
                ->assertOk()
                ->assertSee('封控中心')
                ->assertSee('主动添加封禁')
                ->assertSee('设备黑名单')
                ->assertSee('风险 IP 列表')
                ->assertSee('当前有 1 台设备处于黑名单')
                ->assertSee('设备序列号')
                ->assertSee('BLOCKED-DEVICE')
                ->assertSee('已关联绑定设备')
                ->assertSee('登录卡密')
                ->assertSee($securityCardCode)
                ->assertSee('登录 IP')
                ->assertSee(substr(hash('sha256', 'BLOCKED-DEVICE'), 0, 12).'…'.substr(hash('sha256', 'BLOCKED-DEVICE'), -8))
                ->assertSee('使用新卡密时也会进入隐藏响应')
                ->assertSee('客户端统一显示“卡密不存在”')
                ->assertSee('按完整卡密查询真实原因')
                ->assertSee('全软件拉黑')
                ->assertSee('处置等级')
                ->assertSee('观察')
                ->assertSee('拉黑')
                ->assertSee('全部恢复')
                ->assertSee('仅解除设备')
                ->assertSee('设备 + 卡密/账号')
                ->assertSee('设备 + IP')
                ->assertSee('确认修改')
                ->assertSee('name="status" aria-label="处置等级" data-native-select', false)
                ->assertSee('name="release_scope" aria-label="解除范围" data-native-select', false)
                ->assertSee('data-risk-action-trigger', false)
                ->assertSee('data-risk-action-popover', false)
                ->assertDontSee('<details class="risk-action-menu"', false);

            $this->post('/private-console/risk-control/device-states/'.$deviceState->getKey().'/unblock', [
                'release_scope' => 'device_only',
            ])
                ->assertRedirect()
                ->assertSessionHas('success', '设备黑名单已解除；关联卡密/账号与 IP 保持原状态。');
            $this->assertDatabaseHas('client_security_subject_states', [
                'id' => $deviceState->getKey(), 'status' => 'observe', 'risk_score' => 0,
                'reason_code' => 'ADMIN_RESET',
            ]);
            $this->assertDatabaseHas('ip_risk_states', [
                'application_id' => $application->getKey(), 'level' => 'high',
                'permanently_blocked' => true, 'last_device_hash' => hash('sha256', 'BLOCKED-DEVICE'),
            ]);
            $this->assertDatabaseHas('cards', [
                'id' => $securityCard->getKey(), 'user_status' => 'frozen',
            ]);
            $this->assertDatabaseHas('application_client_sessions', [
                'id' => $securitySession->getKey(), 'status' => 'security_blocked',
            ]);

            $this->post('/private-console/risk-control/device-states/'.$deviceState->getKey().'/status', [
                'status' => 'blocked',
            ])->assertRedirect()->assertSessionHas('success');
            $this->assertDatabaseHas('client_security_subject_states', [
                'id' => $deviceState->getKey(), 'status' => 'blocked', 'reason_code' => 'ADMIN_BLOCK',
            ]);

            // 再次标记为黑名单后选择全部恢复，联动解除卡密和 IP。
            $deviceState->refresh()->forceFill([
                'status' => 'blocked', 'risk_score' => 100, 'reason_code' => 'T1',
            ])->save();
            $this->post('/private-console/risk-control/device-states/'.$deviceState->getKey().'/unblock', [
                'release_scope' => 'all',
            ])
                ->assertRedirect()
                ->assertSessionHas('success', '设备、关联 IP 与安全冻结主体已经恢复；历史闪退总账保留，新异常将从当前基线重新计算。');
            $this->assertDatabaseHas('client_security_subject_states', [
                'id' => $deviceState->getKey(), 'status' => 'observe', 'risk_score' => 0,
                'reason_code' => 'ADMIN_RESET',
            ]);
            $this->assertDatabaseHas('ip_risk_states', [
                'application_id' => $application->getKey(), 'level' => 'normal',
                'permanently_blocked' => false, 'last_device_hash' => hash('sha256', 'BLOCKED-DEVICE'),
            ]);
            $this->assertDatabaseHas('cards', [
                'id' => $securityCard->getKey(), 'user_status' => 'active',
            ]);
            $this->assertDatabaseHas('application_client_sessions', [
                'id' => $securitySession->getKey(), 'status' => 'revoked',
            ]);
            $securityEvent = ClientSecurityEvent::query()->where('event_id', str_repeat('e', 32))->firstOrFail();
            $this->assertSame(8, data_get($securityEvent->evidence, 'pardon_revision'));
            $this->assertSame(8, data_get($securityEvent->evidence, 'pardon_count'));
            $this->assertSame(0, data_get($securityEvent->evidence, 'effective_count'));

            $this->put('/private-console/risk-control/applications/'.$application->public_id.'/policy', [
                'enabled' => '1',
                'invalid_card_threshold' => 3,
                'invalid_card_window_seconds' => 60,
                'block_http_status' => 403, 'block_code' => 'RISK_BLOCKED', 'block_message' => '当前访问受限。',
            ])->assertRedirect()->assertSessionHas('success');

            $this->assertDatabaseHas('risk_policies', [
                'application_id' => $application->getKey(),
                'invalid_card_threshold' => 3,
                'invalid_card_window_seconds' => 60,
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

    /** @return array{0: Application, 1: string} */
    private function cardLoginApplication(string $code = ''): array
    {
        $credentials = app(ApplicationCredentialService::class)->generate();
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => $code !== '' ? $code : (string) random_int(800000, 899999),
            'name' => '套卡探测软件',
            'mode' => 'card_login',
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'payload_mode' => 'constant',
            'payload_value_ciphertext' => '0x100000',
            'client_secret_hash' => hash('sha256', $credentials['communication_key_ciphertext']),
            'bind_device_enabled' => true,
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 1,
            ...$credentials,
        ]);
        $application->legacyConfig()->create($application->legacyLoginDefaults());

        return [$application, '/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token];
    }

    private function assertMaskedCardFailure(TestResponse $response): void
    {
        $response->assertOk()->assertJsonPath('error.code', 'INVALID_CARD');
        $response->assertJsonPath('error.message', '卡密不存在');
    }
}
