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
use App\Models\Card;
use App\Models\CardType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CardIssueAndActivationTest extends TestCase
{
    use RefreshDatabase;

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

        $response = $this->postJson('/api/v2/demo/activate', [
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

        $this->postJson('/api/v2/demo/activate', [
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
        $this->postJson('/api/v2/demo/activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ])->assertOk();

        // Freezing the software user blocks subsequent logins without changing expiry.
        $expiresAt = $card->fresh()->expires_at;
        $card->forceFill(['user_status' => 'frozen', 'user_frozen_at' => now()])->save();
        $this->postJson('/api/v2/demo/activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ])->assertForbidden()->assertJsonPath('error.code', 'USER_FROZEN');
        $this->assertTrue($expiresAt->equalTo($card->fresh()->expires_at));
        $card->forceFill(['user_status' => 'active', 'user_frozen_at' => null])->save();

        $this->postJson('/api/v2/demo/activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'OTHER-DEVICE',
        ])
            ->assertConflict()
            ->assertJsonPath('error.code', 'DEVICE_MISMATCH');

        $this->get('/demo/activate')->assertOk();
        $demoHtml = file_get_contents(public_path('demo-activate.html'));
        $this->assertIsString($demoHtml);
        $this->assertStringContainsString('卡密激活模拟', $demoHtml);
        $this->assertStringContainsString('/api/v2/demo/activate', $demoHtml);
    }

    public function test_generated_inventory_card_can_be_activated_directly(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->postJson('/api/v2/demo/activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.card.status', 'active');
    }

    public function test_frozen_unused_card_cannot_be_activated(): void
    {
        [, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];
        Card::query()->where('code_lookup_hash', app(CardCredentialHasher::class)->lookupHash($credential['code']))
            ->update(['card_is_frozen' => true, 'frozen_at' => now()]);

        $this->postJson('/api/v2/demo/activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ])->assertUnprocessable()
            ->assertJsonPath('error.code', 'CARD_FROZEN')
            ->assertJsonPath('error.message', '你选择卡串已冻结！');
    }

    public function test_card_password_is_ignored_because_all_cards_are_code_only(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->postJson('/api/v2/demo/activate', [
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

        $this->postJson('/api/v2/demo/activate', [
            'code' => $credential['code'],
            'secret' => 'WRONG-SECRET',
            'device_id' => 'IOS-DEVICE-001',
        ])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'INVALID_CARD')
            ->assertJsonPath('error.message', '卡密不存在。');

        $this->postJson('/api/v2/demo/activate', [
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

        $heartbeat = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'timeout.ic', 'BSphpSeSsL' => $handle, 'key' => 'HEARTBEAT-DEVICE', 'appsafecode' => 'HEART-SAFE',
        ], '', '&', PHP_QUERY_RFC3986));
        $heartbeatResponse = $this->postJson($gateway, $heartbeat['envelope'])->assertOk()->json();
        $fields = explode('|', $cipher->openResponse($heartbeatResponse, $heartbeat['session_key'], $heartbeat['request_nonce']));
        $this->assertSame(['5031', $handle, 'HEART-SAFE'], array_slice($fields, 0, 3));

        $card = $application->cards()->sole();
        $card->forceFill(['user_status' => 'frozen'])->save();
        $this->postJson('/api/v2/apps/'.$application->public_id.'/sessions/heartbeat', [
            'BSphpSeSsL' => $handle, 'device_id' => 'HEARTBEAT-DEVICE',
        ])->assertOk()->assertJsonPath('data.code', '5033');

        $card->forceFill(['user_status' => 'active', 'expires_at' => now()->subSecond()])->save();
        $this->postJson('/api/v2/apps/'.$application->public_id.'/sessions/heartbeat', [
            'BSphpSeSsL' => $handle, 'device_id' => 'HEARTBEAT-DEVICE',
        ])->assertOk()->assertJsonPath('data.code', '5030');
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
}
