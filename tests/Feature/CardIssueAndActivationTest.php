<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Applications\DailyVerificationCodec;
use App\Applications\HybridPacketCipher;
use App\Cards\CardCredentialHasher;
use App\Cards\CardGenerationService;
use App\Cards\ManualIssueService;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Card;
use App\Models\CardType;
use App\Models\ClientSecuritySubjectState;
use App\Models\SystemSetting;
use App\Risk\IpRiskControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CardIssueAndActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_encrypted_card_login_reports_empty_card_explicitly(): void
    {
        [$application] = $this->applicationAndType();
        $application->riskPolicy()->create(['enabled' => false]);
        $cipher = app(HybridPacketCipher::class);
        $request = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => '', 'key' => 'EMPTY-CARD-DEVICE',
            'appsafecode' => 'EMPTY-CARD-SAFE', 'BSphpSeSsL' => str_repeat('q', 64),
        ], '', '&', PHP_QUERY_RFC3986));

        $this->postJson('/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token, $request['envelope'])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'EMPTY_CARD')
            ->assertJsonPath('error.message', '卡密为空。');
    }

    public function test_card_login_diagnostic_switch_records_sanitized_request_response_and_device_mismatch(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $application->riskPolicy()->create(['enabled' => false]);
        SystemSetting::query()->create([
            'key' => 'card_login_trace_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'diagnostics',
        ]);
        $administrator = $this->administrator();
        $credential = app(CardGenerationService::class)->generate($cardType, 1, $administrator)['credentials'][0];
        $cipher = app(HybridPacketCipher::class);
        $gateway = '/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token;
        $firstSession = str_repeat('t', 64);

        $first = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => $credential['code'], 'icpwd' => $credential['secret'],
            'key' => 'TRACE-DEVICE-A', 'appsafecode' => 'TRACE-SAFE-A', 'BSphpSeSsL' => $firstSession,
        ], '', '&', PHP_QUERY_RFC3986));
        $this->postJson($gateway, $first['envelope'])->assertOk();

        $successLog = AuditLog::query()->where('action', 'client.card_login_trace')->sole();
        $this->assertSame('TRACE-DEVICE-A', data_get($successLog->before_data, 'device'));
        $this->assertSame('v2-object', data_get($successLog->before_data, 'wire_format'));
        $this->assertSame('[REDACTED]', data_get($successLog->before_data, 'parameters.appsafecode'));
        $this->assertSame('[SESSION_HANDLE]', data_get($successLog->before_data, 'parameters.BSphpSeSsL'));
        $this->assertSame('TRACE-DEVICE-A', data_get($successLog->after_data, 'returned_device'));
        $this->assertTrue(data_get($successLog->after_data, 'request_device_matches_binding'));
        $this->assertStringContainsString('[PAYLOAD]', (string) data_get($successLog->after_data, 'legacy_response'));
        $successJson = json_encode([$successLog->before_data, $successLog->after_data], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($credential['code'], $successJson);
        $this->assertStringNotContainsString('TRACE-SAFE-A', $successJson);
        $this->assertStringNotContainsString($firstSession, $successJson);

        $second = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => $credential['code'], 'icpwd' => $credential['secret'],
            'key' => 'TRACE-DEVICE-B', 'appsafecode' => 'TRACE-SAFE-B', 'BSphpSeSsL' => str_repeat('u', 64),
        ], '', '&', PHP_QUERY_RFC3986));
        $this->assertMaskedCardFailure($this->postJson($gateway, $second['envelope']));

        $mismatchLog = AuditLog::query()->where('action', 'client.card_login_trace')->latest('id')->firstOrFail();
        $this->assertSame('failed', $mismatchLog->result);
        $this->assertSame('DEVICE_MISMATCH', data_get($mismatchLog->after_data, 'business_code'));
        $this->assertSame('TRACE-DEVICE-B', data_get($mismatchLog->before_data, 'device'));
        $this->assertSame('TRACE-DEVICE-A', data_get($mismatchLog->after_data, 'bound_device'));
        $this->assertFalse(data_get($mismatchLog->after_data, 'request_device_matches_binding'));

        $blockedCredential = app(CardGenerationService::class)->generate($cardType, 1, $administrator)['credentials'][0];
        ClientSecuritySubjectState::query()->create([
            'application_id' => $application->getKey(),
            'device_hash' => hash('sha256', 'TRACE-BLOCKED-DEVICE'),
            'risk_score' => 100,
            'status' => 'blocked',
            'last_event_at' => now(),
            'reason_code' => 'S0',
        ]);
        $blocked = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => $blockedCredential['code'], 'icpwd' => $blockedCredential['secret'],
            'key' => 'TRACE-BLOCKED-DEVICE', 'appsafecode' => 'TRACE-SAFE-BLOCKED',
            'BSphpSeSsL' => str_repeat('v', 64),
        ], '', '&', PHP_QUERY_RFC3986));
        $this->assertMaskedCardFailure($this->postJson($gateway, $blocked['envelope']));
        $blockedLog = AuditLog::query()->where('action', 'client.card_login_trace')->latest('id')->firstOrFail();
        $this->assertSame('DEVICE_BLOCKED', data_get($blockedLog->after_data, 'business_code'));
        $this->assertSame('DEVICE_BLOCKLIST_HONEYPOT', data_get($blockedLog->after_data, 'diagnostic_code'));
        $this->assertTrue(data_get($blockedLog->after_data, 'device_security_state.actively_blocked'));
        $this->assertSame('S0', data_get($blockedLog->after_data, 'device_security_state.reason_code'));
        $blockedCard = Card::query()->where('code_lookup_hash', app(CardCredentialHasher::class)->lookupHash($blockedCredential['code']))->sole();
        $this->assertSame('inventory', $blockedCard->status);
        $this->assertNull($blockedCard->bound_device_ciphertext);

        SystemSetting::query()->whereKey('card_login_trace_enabled')->update(['value' => '0']);
        $third = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => $credential['code'], 'key' => 'TRACE-DEVICE-A',
            'appsafecode' => 'TRACE-SAFE-C', 'BSphpSeSsL' => $firstSession,
        ], '', '&', PHP_QUERY_RFC3986));
        $this->postJson($gateway, $third['envelope'])->assertOk();
        $this->assertSame(3, AuditLog::query()->where('action', 'client.card_login_trace')->count());
    }

    public function test_generated_card_can_be_manually_issued_and_activated_once(): void
    {
        $administrator = $this->administrator();
        [$application, $cardType] = $this->applicationAndType();
        // 本用例验证制卡、协议与冻结语义；分档封控由 IpRiskControlTest 独立覆盖。
        $application->riskPolicy()->create(['enabled' => false]);

        $generated = app(CardGenerationService::class)->generate($cardType, 2, $administrator, '测试批次');
        $credential = $generated['credentials'][0];

        $this->assertCount(2, $generated['credentials']);
        $this->assertDatabaseCount('cards', 2);
        $this->assertDatabaseHas('card_batches', [
            'id' => $generated['batch']->getKey(),
            'status' => 'ready',
            'actual_quantity' => 2,
        ]);

        $issue = app(ManualIssueService::class)->issueOffline(
            $cardType,
            1,
            '线下客户 A',
            (string) Str::uuid(),
            '测试发卡',
            $administrator,
        );

        $this->assertSame(1, $issue->quantity);
        $this->assertDatabaseHas('cards', [
            'code_lookup_hash' => app(CardCredentialHasher::class)->lookupHash($credential['code']),
            'status' => 'issued',
        ]);
        $this->assertDatabaseCount('manual_issue_items', 1);

        $response = $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
            'appsafecode' => 'SAFE-123',
        ]);

        $this->assertSame(200, $response->status(), (string) json_encode($response->json()));
        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.application.code', $application->code)
            ->assertJsonPath('data.card.status', 'active')
            ->assertJsonPath('data.appsafecode', 'SAFE-123')
            ->assertJsonPath('data.payload', '0x100000');

        $legacy = $response->json('data.legacy_response');
        $this->assertIsString($legacy);
        $fields = explode('|', $legacy);
        $this->assertStringEndsWith('+08:00', $response->json('data.card.expires_at'));
        $this->assertSame($response->json('data.card.expires_at'), $fields[6]);
        $this->assertSame('01', $fields[0]);
        $this->assertSame('xx', $fields[1]);
        $this->assertMatchesRegularExpression('/^[0-9A-F]{10}$/', $fields[2]);
        $this->assertSame(0x100000, app(DailyVerificationCodec::class)->decryptUInt32($fields[2]));
        $this->assertSame('SAFE-123', $fields[3]);
        $this->assertSame(8, strlen($fields[4]));
        $this->assertSame(app(DailyVerificationCodec::class)->dailySignature(), $fields[4]);
        $this->assertSame('IOS-DEVICE-001', $fields[8]);

        $cipher = app(HybridPacketCipher::class);
        $sealed = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic',
            'icid' => $credential['code'],
            'icpwd' => $credential['secret'],
            'key' => 'IOS-DEVICE-001',
            'appsafecode' => 'IOS-SAFE',
        ], '', '&', PHP_QUERY_RFC3986));

        $gatewayUrl = '/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token;
        $gatewayResponse = $this->postJson($gatewayUrl, $sealed['envelope']);
        $gatewayResponse->assertOk();

        $gatewayPlaintext = $cipher->openResponse(
            $gatewayResponse->json(),
            $sealed['session_key'],
            $sealed['request_nonce'],
        );
        $gatewayFields = explode('|', $gatewayPlaintext);
        $this->assertSame('01', $gatewayFields[0]);
        $this->assertSame(0x100000, app(DailyVerificationCodec::class)->decryptUInt32($gatewayFields[2]));
        $this->assertSame('IOS-SAFE', $gatewayFields[3]);
        $this->assertSame('IOS-DEVICE-001', $gatewayFields[8]);

        // iOS Demo 使用 wire v3 紧凑数组，数据包中不暴露 iv/data/mac 等字段名称。
        $compactSealed = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic',
            'icid' => $credential['code'],
            'icpwd' => $credential['secret'],
            'key' => 'IOS-DEVICE-001',
            'appsafecode' => 'IOS-COMPACT-SAFE',
        ], '', '&', PHP_QUERY_RFC3986));
        $requestEnvelope = $compactSealed['envelope'];
        $compactRequest = [
            3, $requestEnvelope['ts'], $requestEnvelope['nonce'], $requestEnvelope['ek'],
            $requestEnvelope['iv'], $requestEnvelope['data'], $requestEnvelope['mac'], $requestEnvelope['auth'],
        ];
        $compactResponse = $this->postJson($gatewayUrl, $compactRequest)->assertOk()->json();
        $this->assertTrue(array_is_list($compactResponse));
        $this->assertCount(7, $compactResponse);
        $this->assertSame(3, $compactResponse[0]);
        $expandedCompactResponse = [
            'v' => 2,
            'alg' => HybridPacketCipher::ALGORITHM,
            'direction' => 'response',
            'ts' => $compactResponse[1],
            'nonce' => $compactResponse[2],
            'request_nonce' => $compactResponse[3],
            'iv' => $compactResponse[4],
            'data' => $compactResponse[5],
            'mac' => $compactResponse[6],
        ];
        $compactPlaintext = $cipher->openResponse(
            $expandedCompactResponse,
            $compactSealed['session_key'],
            $compactSealed['request_nonce'],
        );
        $this->assertSame('IOS-COMPACT-SAFE', explode('|', $compactPlaintext)[3]);

        $this->postJson($gatewayUrl, $sealed['envelope'])
            ->assertConflict()
            ->assertJsonPath('error.code', 'PACKET_REPLAYED');

        $application->forceFill(['status' => 'suspended'])->save();
        $disabledRequest = $cipher->sealRequest($application, 'api=login.ic');
        $this->postJson($gatewayUrl, $disabledRequest)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'APP_DISABLED');
        $application->forceFill(['status' => 'active'])->save();

        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ])->assertOk();

        $card = Card::query()
            ->where('code_lookup_hash', app(CardCredentialHasher::class)->lookupHash($credential['code']))
            ->sole();
        $this->assertSame(1, $card->activation_count);
        $this->assertNotNull($card->expires_at);

        // BSPHP semantics: freezing a used card does not freeze its software user.
        $card->forceFill(['card_is_frozen' => true, 'frozen_at' => now()])->save();
        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ])->assertOk();

        // Freezing the software user blocks subsequent logins without changing expiry.
        $expiresAt = $card->fresh()->expires_at;
        $card->forceFill(['user_status' => 'frozen', 'user_frozen_at' => now()])->save();
        $this->assertMaskedCardFailure($this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ]));
        $this->assertTrue($expiresAt->equalTo($card->fresh()->expires_at));
        $card->forceFill(['user_status' => 'active', 'user_frozen_at' => null])->save();

        $this->assertMaskedCardFailure($this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'OTHER-DEVICE',
        ]));

        $this->postJson('/api/v2/demo/activate', [])->assertNotFound();
        $this->get('/demo/activate')->assertNotFound();
    }

    public function test_generated_inventory_card_can_be_activated_directly(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.card.status', 'active');
    }

    public function test_cleared_card_binding_is_claimed_by_next_device_and_rejects_other_devices(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'device_id' => 'FIRST-DEVICE',
        ])->assertOk();

        $card = $application->cards()->sole();
        $card->forceFill([
            'bound_device_ciphertext' => null,
            'bound_device_mask' => null,
        ])->save();

        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'device_id' => 'REBOUND-DEVICE',
        ])->assertOk();

        $this->assertSame('REBOUND-DEVICE', $card->fresh()->bound_device_ciphertext);

        $this->assertMaskedCardFailure($this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'device_id' => 'OTHER-DEVICE',
        ]));
    }

    public function test_frozen_unused_card_cannot_be_activated(): void
    {
        [, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];
        Card::query()->where('code_lookup_hash', app(CardCredentialHasher::class)->lookupHash($credential['code']))
            ->update(['card_is_frozen' => true, 'frozen_at' => now()]);

        $this->assertMaskedCardFailure($this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ]));
    }

    public function test_card_password_is_ignored_because_all_cards_are_code_only(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => 'WRONG-SECRET',
            'device_id' => 'IOS-DEVICE-001',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_password_card_requires_the_generated_secret(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $cardType->forceFill(['secret_length' => 8])->save();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->assertSame(8, strlen($credential['secret']));

        $this->assertMaskedCardFailure($this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => 'WRONG-SECRET',
            'device_id' => 'IOS-DEVICE-001',
        ]));

        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_zero_password_card_activates_through_encrypted_appen_gateway(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $cardType->forceFill(['secret_length' => 0])->save();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->assertSame('', $credential['secret']);
        $cipher = app(HybridPacketCipher::class);
        $sealed = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic',
            'icid' => $credential['code'],
            'icpwd' => '',
            'key' => 'IOS-SHOUQUAN-DEMO',
            'appsafecode' => 'shouquan-demo',
        ], '', '&', PHP_QUERY_RFC3986));

        $response = $this->postJson(
            '/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token,
            $sealed['envelope'],
        )->assertOk();

        $plaintext = $cipher->openResponse(
            $response->json(),
            $sealed['session_key'],
            $sealed['request_nonce'],
        );
        $fields = explode('|', $plaintext);
        $this->assertSame('01', $fields[0]);
        $this->assertSame('shouquan-demo', $fields[3]);
        $this->assertNotSame('', $fields[6]);
        $this->assertSame('IOS-SHOUQUAN-DEMO', $fields[8]);
    }

    public function test_bsphp_session_handle_and_encrypted_card_heartbeat(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)->generate($cardType, 1, $this->administrator())['credentials'][0];
        $cipher = app(HybridPacketCipher::class);
        $handle = str_repeat('b', 64);
        $gateway = '/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token;

        $login = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => $credential['code'], 'icpwd' => '', 'key' => 'HEARTBEAT-DEVICE',
            'BSphpSeSsL' => $handle, 'appsafecode' => 'LOGIN-SAFE',
        ], '', '&', PHP_QUERY_RFC3986));
        $loginResponse = $this->postJson($gateway, $login['envelope'])->assertOk()->json();
        $loginFields = explode('|', $cipher->openResponse($loginResponse, $login['session_key'], $login['request_nonce']));
        $this->assertSame($handle, $loginFields[9]);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $loginFields[10]);

        $heartbeat = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'timeout.ic', 'BSphpSeSsL' => $handle, 'key' => 'HEARTBEAT-DEVICE', 'appsafecode' => 'HEART-SAFE',
        ], '', '&', PHP_QUERY_RFC3986));
        $heartbeatResponse = $this->postJson($gateway, $heartbeat['envelope'])->assertOk()->json();
        $fields = explode('|', $cipher->openResponse($heartbeatResponse, $heartbeat['session_key'], $heartbeat['request_nonce']));
        $this->assertSame(['5031', $handle, 'HEART-SAFE'], array_slice($fields, 0, 3));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $fields[4] ?? '');
        $this->assertSame(app(DailyVerificationCodec::class)->dailySignature(), $fields[5] ?? '');
        $this->assertCount(10, $fields);
        $this->assertSame(['', '', '', ''], array_slice($fields, 6));
        $this->assertNotSame($loginFields[10], $fields[4]);

        $card = $application->cards()->sole();
        $card->forceFill(['user_status' => 'frozen'])->save();
        $this->postJson('/api/v2/apps/'.$application->public_id.'/sessions/heartbeat', [
            'BSphpSeSsL' => $handle, 'device_id' => 'HEARTBEAT-DEVICE',
        ])->assertOk()
            ->assertJsonPath('data.code', '5026')
            ->assertJsonPath('data.message', '网络连接异常');

        $card->forceFill(['user_status' => 'active', 'expires_at' => now()->subSecond()])->save();
        $this->postJson('/api/v2/apps/'.$application->public_id.'/sessions/heartbeat', [
            'BSphpSeSsL' => $handle, 'device_id' => 'HEARTBEAT-DEVICE',
        ])->assertOk()->assertJsonPath('data.code', '5030');

        // 首次和后续再次登录都保持明确的到期业务码，客户端可稳定展示到期弹窗。
        foreach ([str_repeat('e', 64), str_repeat('f', 64)] as $expiredHandle) {
            $expiredLogin = $cipher->sealRequestWithContext($application, http_build_query([
                'api' => 'login.ic', 'icid' => $credential['code'], 'icpwd' => '', 'key' => 'HEARTBEAT-DEVICE',
                'BSphpSeSsL' => $expiredHandle, 'appsafecode' => 'EXPIRED-SAFE',
            ], '', '&', PHP_QUERY_RFC3986));
            $this->assertMaskedCardFailure($this->postJson($gateway, $expiredLogin['envelope']));
        }
    }

    public function test_encrypted_security_report_is_idempotent_and_freezes_high_confidence_subject(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $application->riskPolicy()->updateOrCreate([], [
            ...app(IpRiskControlService::class)->defaults(),
            'security_report_mode' => 'enforce',
        ]);
        $credential = app(CardGenerationService::class)->generate($cardType, 1, $this->administrator())['credentials'][0];
        $cipher = app(HybridPacketCipher::class);
        $handle = str_repeat('s', 64);
        $gateway = '/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token;

        $login = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => $credential['code'], 'key' => 'SECURITY-DEVICE',
            'BSphpSeSsL' => $handle, 'appsafecode' => 'LOGIN-SAFE',
        ], '', '&', PHP_QUERY_RFC3986));
        $response = $this->postJson($gateway, $login['envelope'])->assertOk()->json();
        $fields = explode('|', $cipher->openResponse($response, $login['session_key'], $login['request_nonce']));
        $reportToken = $fields[10];

        $sendReport = function () use ($cipher, $application, $gateway, $handle, $reportToken): void {
            $report = $cipher->sealRequestWithContext($application, http_build_query([
                'api' => 'security.report', 'BSphpSeSsL' => $handle, 'key' => 'SECURITY-DEVICE',
                'appsafecode' => 'REPORT-SAFE', 'event_id' => '01SECURITYEVENT000000000001',
                'event_code' => 'D0', 'report_token' => $reportToken,
                'evidence' => json_encode(['selector_hash' => str_repeat('a', 16)]),
            ], '', '&', PHP_QUERY_RFC3986));
            $response = $this->postJson($gateway, $report['envelope'])->assertOk()->json();
            $plain = $cipher->openResponse($response, $report['session_key'], $report['request_nonce']);
            $this->assertSame(['01', 'accepted', 'REPORT-SAFE'], array_slice(explode('|', $plain), 0, 3));
        };

        $sendReport();
        $sendReport();
        $this->assertDatabaseCount('client_security_events', 1);
        $this->assertDatabaseHas('application_client_sessions', ['token_hash' => hash('sha256', $handle), 'status' => 'security_blocked']);
        $this->assertDatabaseHas('cards', ['application_id' => $application->getKey(), 'user_status' => 'frozen']);
        $this->assertDatabaseHas('client_security_subject_states', [
            'application_id' => $application->getKey(), 'device_hash' => hash('sha256', 'SECURITY-DEVICE'),
            'status' => 'blocked', 'reason_code' => 'D0',
        ]);
        $this->assertDatabaseHas('ip_risk_states', [
            'application_id' => $application->getKey(),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'level' => 'high', 'permanently_blocked' => true,
            'last_device_hash' => hash('sha256', 'SECURITY-DEVICE'),
            'last_endpoint' => 'security.report',
        ]);
        $this->postJson('/api/v2/apps/'.$application->public_id.'/sessions/heartbeat', [
            'BSphpSeSsL' => $handle, 'device_id' => 'SECURITY-DEVICE',
        ])->assertOk()->assertJsonPath('data.code', '5026');
        $blockedHandle = str_repeat('z', 64);
        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'], 'device_id' => 'SECURITY-DEVICE', 'BSphpSeSsL' => $blockedHandle,
        ])->assertStatus(503)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'SERVER_UNAVAILABLE');
        $this->assertDatabaseMissing('application_client_sessions', ['token_hash' => hash('sha256', $blockedHandle)]);
    }

    public function test_daily_signature_mismatch_signal_freezes_subject_in_enforce_mode(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $application->riskPolicy()->updateOrCreate([], [
            ...app(IpRiskControlService::class)->defaults(),
            'security_report_mode' => 'enforce',
        ]);
        $credential = app(CardGenerationService::class)->generate($cardType, 1, $this->administrator())['credentials'][0];
        $cipher = app(HybridPacketCipher::class);
        $handle = str_repeat('m', 64);
        $gateway = '/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token;

        $login = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic', 'icid' => $credential['code'], 'key' => 'DAILY-MISMATCH-DEVICE',
            'BSphpSeSsL' => $handle, 'appsafecode' => 'LOGIN-SAFE',
        ], '', '&', PHP_QUERY_RFC3986));
        $response = $this->postJson($gateway, $login['envelope'])->assertOk()->json();
        $fields = explode('|', $cipher->openResponse($response, $login['session_key'], $login['request_nonce']));

        $report = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'security.report', 'BSphpSeSsL' => $handle, 'key' => 'DAILY-MISMATCH-DEVICE',
            'appsafecode' => 'DAILY-REPORT-SAFE', 'event_id' => '01DAILYMISMATCH000000000001',
            'event_code' => 'S0', 'report_token' => $fields[10],
            'evidence' => json_encode(['source' => 'daily-v2', 'clock_delta' => 0]),
        ], '', '&', PHP_QUERY_RFC3986));
        $reportResponse = $this->postJson($gateway, $report['envelope'])->assertOk()->json();
        $plain = $cipher->openResponse($reportResponse, $report['session_key'], $report['request_nonce']);

        $this->assertSame(['01', 'accepted', 'DAILY-REPORT-SAFE'], array_slice(explode('|', $plain), 0, 3));
        $this->assertDatabaseHas('client_security_events', [
            'application_id' => $application->getKey(), 'event_code' => 'S0',
            'severity' => 'high', 'action' => 'freeze',
        ]);
        $this->assertDatabaseHas('client_security_subject_states', [
            'application_id' => $application->getKey(),
            'device_hash' => hash('sha256', 'DAILY-MISMATCH-DEVICE'),
            'status' => 'blocked', 'reason_code' => 'S0',
        ]);
        $this->assertDatabaseHas('application_client_sessions', [
            'token_hash' => hash('sha256', $handle), 'status' => 'security_blocked',
        ]);
    }

    public function test_successful_login_records_idempotent_abnormal_exit_telemetry_as_observation(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $application->riskPolicy()->updateOrCreate([], [
            ...app(IpRiskControlService::class)->defaults(),
            'security_report_mode' => 'observe',
        ]);
        $credential = app(CardGenerationService::class)->generate($cardType, 1, $this->administrator())['credentials'][0];
        $cipher = app(HybridPacketCipher::class);
        $handle = str_repeat('l', 64);
        $gateway = '/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token;
        $now = now()->timestamp;
        $times = [$now - 100, $now - 75, $now - 50, $now - 25, $now - 5];
        $telemetry = json_encode([
            'v' => 1,
            'id' => str_repeat('a', 32),
            'count' => 5,
            'times' => $times,
            'intervals' => [25, 25, 25, 20],
            'uptimes' => [4, 6, 3, 8, 5],
            'window' => 3600,
        ], JSON_THROW_ON_ERROR);

        $login = function (string $payload) use ($cipher, $application, $gateway, $credential, $handle): void {
            $request = $cipher->sealRequestWithContext($application, http_build_query([
                'api' => 'login.ic',
                'icid' => $credential['code'],
                'key' => 'LAUNCH-TELEMETRY-DEVICE',
                'BSphpSeSsL' => $handle,
                'appsafecode' => 'LOGIN-SAFE',
                'launch_telemetry' => $payload,
            ], '', '&', PHP_QUERY_RFC3986));
            $this->postJson($gateway, $request['envelope'])->assertOk();
        };

        $login($telemetry);
        $login($telemetry);

        $this->assertDatabaseCount('client_security_events', 1);
        $this->assertDatabaseHas('client_security_events', [
            'application_id' => $application->getKey(),
            'event_id' => str_repeat('a', 32),
            'event_code' => 'T1',
            'severity' => 'medium',
            'action' => 'observe',
        ]);
        $this->assertDatabaseHas('client_security_subject_states', [
            'application_id' => $application->getKey(),
            'device_hash' => hash('sha256', 'LAUNCH-TELEMETRY-DEVICE'),
            'risk_score' => 23,
            'status' => 'observe',
            'reason_code' => 'T1',
        ]);

        // 可选参数损坏时保留正常登录结果，也不产生第二条事件。
        $login('{broken');
        $this->assertDatabaseCount('client_security_events', 1);
    }

    public function test_login_ignores_abnormal_exit_telemetry_below_three_events(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $application->riskPolicy()->updateOrCreate([], [
            ...app(IpRiskControlService::class)->defaults(),
            'security_report_mode' => 'observe',
        ]);
        $credential = app(CardGenerationService::class)->generate($cardType, 1, $this->administrator())['credentials'][0];
        $cipher = app(HybridPacketCipher::class);
        $now = now()->timestamp;
        $request = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic',
            'icid' => $credential['code'],
            'key' => 'LOW-COUNT-LAUNCH-DEVICE',
            'BSphpSeSsL' => str_repeat('r', 64),
            'appsafecode' => 'LOGIN-SAFE',
            'launch_telemetry' => json_encode([
                'v' => 1,
                'id' => str_repeat('c', 32),
                'count' => 2,
                'times' => [$now - 30, $now - 10],
                'intervals' => [20],
                'uptimes' => [5, 6],
                'window' => 3600,
            ], JSON_THROW_ON_ERROR),
        ], '', '&', PHP_QUERY_RFC3986));

        $this->postJson('/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token, $request['envelope'])
            ->assertOk();
        $this->assertDatabaseCount('client_security_events', 0);
        $this->assertDatabaseCount('client_security_subject_states', 0);
    }

    public function test_eight_abnormal_exits_within_one_hour_freeze_subject_in_enforce_mode(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $application->riskPolicy()->updateOrCreate([], [
            ...app(IpRiskControlService::class)->defaults(),
            'security_report_mode' => 'enforce',
            'abnormal_exit_window_minutes' => 60,
            'abnormal_exit_high_threshold' => 8,
        ]);
        $credential = app(CardGenerationService::class)->generate($cardType, 1, $this->administrator())['credentials'][0];
        $cipher = app(HybridPacketCipher::class);
        $handle = str_repeat('q', 64);
        $gateway = '/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token;
        $first = now()->timestamp - 3360;
        $times = array_map(fn (int $index): int => $first + ($index * 480), range(0, 7));
        $request = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.ic',
            'icid' => $credential['code'],
            'key' => 'SEVERE-LAUNCH-DEVICE',
            'BSphpSeSsL' => $handle,
            'appsafecode' => 'LOGIN-SAFE',
            'launch_telemetry' => json_encode([
                'v' => 1,
                'id' => str_repeat('b', 32),
                'count' => 8,
                'times' => $times,
                'intervals' => array_fill(0, 7, 480),
                'uptimes' => array_fill(0, 8, 5),
                'window' => 3600,
            ], JSON_THROW_ON_ERROR),
        ], '', '&', PHP_QUERY_RFC3986));

        $this->assertMaskedCardFailure($this->postJson($gateway, $request['envelope']));

        $this->assertDatabaseHas('client_security_events', [
            'application_id' => $application->getKey(),
            'event_id' => str_repeat('b', 32),
            'event_code' => 'T1',
            'severity' => 'high',
            'action' => 'freeze',
        ]);
        $this->assertDatabaseHas('application_client_sessions', [
            'token_hash' => hash('sha256', $handle),
            'status' => 'security_blocked',
        ]);
        $this->assertDatabaseHas('cards', [
            'application_id' => $application->getKey(),
            'user_status' => 'frozen',
        ]);
        $this->assertDatabaseHas('client_security_subject_states', [
            'application_id' => $application->getKey(),
            'device_hash' => hash('sha256', 'SEVERE-LAUNCH-DEVICE'),
            'risk_score' => 100,
            'status' => 'blocked',
            'reason_code' => 'T1',
        ]);
        $this->assertDatabaseHas('ip_risk_states', [
            'application_id' => $application->getKey(),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'level' => 'high', 'permanently_blocked' => true,
            'last_device_hash' => hash('sha256', 'SEVERE-LAUNCH-DEVICE'),
            'last_endpoint' => 'client.launch.telemetry',
        ]);
    }

    public function test_unlimited_application_and_card_limits_allow_multiple_sessions(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $application->forceFill([
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 1,
        ])->save();
        $cardType->forceFill([
            'account_concurrency_limit' => -1,
            'machine_concurrency_limit' => -1,
        ])->save();

        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'device_id' => 'UNLIMITED-DEVICE-1',
            'BSphpSeSsL' => str_repeat('a', 64),
        ])->assertOk();

        $this->postJson('/__testing/card-activate', [
            'code' => $credential['code'],
            'device_id' => 'UNLIMITED-DEVICE-2',
            'BSphpSeSsL' => str_repeat('b', 64),
        ])->assertOk();

        $this->assertDatabaseCount('application_client_sessions', 2);
    }

    /**
     * @return array{Application, CardType}
     */
    private function applicationAndType(): array
    {
        $credentials = app(ApplicationCredentialService::class)->generate();
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => '201',
            'name' => '激活演示应用',
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

        $cardType = CardType::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'name' => '30 天卡',
            'code' => 'month30',
            'purpose' => 'login_credential',
            'benefit_kind' => 'duration',
            'duration_seconds' => 30 * 86400,
            'face_price_cents' => 3000,
            'code_prefix' => 'DM',
            'code_length' => 20,
            'secret_length' => 0,
            'status' => 'active',
            'max_activations' => 1,
        ]);

        return [$application, $cardType];
    }

    private function administrator(): Administrator
    {
        return Administrator::query()->create([
            'username' => 'card-admin-'.Str::lower(Str::random(6)),
            'password' => 'primary-secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    private function assertMaskedCardFailure(TestResponse $response): void
    {
        $response->assertOk()->assertJsonPath('error.code', 'INVALID_CARD');
        $message = (string) $response->json('error.message');
        $this->assertMatchesRegularExpression('/^卡密不存在\d{3}$/u', $message);
        $reference = (int) mb_substr($message, -3);
        $this->assertGreaterThanOrEqual(201, $reference);
        $this->assertLessThanOrEqual(999, $reference);
    }
}
