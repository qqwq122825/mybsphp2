<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Applications\HybridPacketCipher;
use App\Cards\CardGenerationService;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\CardType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountRegistrationAndLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_config_exposes_frontend_status_without_secrets(): void
    {
        [$application] = $this->fixture();
        $application->forceFill([
            'status' => 'suspended',
            'closed_message' => '今晚维护，预计一小时。',
            'version_name' => 'v2.1',
            'announcement' => '更新公告',
        ])->save();

        $response = $this->getJson('/api/v2/apps/'.$application->public_id.'/public-config')
            ->assertOk()
            ->assertJsonPath('data.application.status', 'suspended')
            ->assertJsonPath('data.application.is_open', false)
            ->assertJsonPath('data.application.closed_message', '今晚维护，预计一小时。')
            ->assertJsonPath('data.application.version', 'v2.1')
            ->assertJsonPath('data.application.binding_mode', 'verified');

        $response->assertJsonMissingPath('data.application.communication_key');
        $response->assertJsonMissingPath('data.application.server_private_key');

        $this->getJson('/api/v2/software/'.$application->code.'/public-config')
            ->assertOk()
            ->assertJsonPath('data.application.code', $application->code);
    }

    public function test_registration_without_entitlement_is_expired_and_login_returns_verified_expiry_details(): void
    {
        [$application] = $this->fixture();

        $registered = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
            'username' => 'emptytime', 'password' => 'secret123', 'device_id' => 'IOS-EMPTY-TIME-001',
        ])->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.account.credentials_verified', true)
            ->assertJsonPath('data.authorization.status', 'expired')
            ->assertJsonPath('data.authorization.is_expired', true);

        $this->assertNotNull($registered->json('data.account.expires_at'));

        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', [
            'username' => 'emptytime', 'password' => 'secret123', 'device_id' => 'IOS-EMPTY-TIME-001',
        ])->assertForbidden()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'ACCOUNT_EXPIRED')
            ->assertJsonPath('error.details.credentials_verified', true)
            ->assertJsonPath('error.details.authorization.status', 'expired')
            ->assertJsonPath('error.details.authorization.is_expired', true);
    }

    public function test_registration_bonus_grants_configured_time_once(): void
    {
        [$application] = $this->fixture();
        $application->forceFill([
            'registration_bonus_value' => 12,
            'registration_bonus_unit' => 'hour',
        ])->save();

        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
            'username' => 'giftuser', 'password' => 'secret123', 'device_id' => 'IOS-GIFT-001',
        ])->assertCreated()
            ->assertJsonPath('data.authorization.status', 'active');

        $account = $application->accounts()->where('username', 'giftuser')->sole();
        $this->assertTrue($account->expires_at->between(now()->addHours(11), now()->addHours(13)));
        $this->assertDatabaseHas('account_entitlement_transactions', [
            'application_account_id' => $account->getKey(),
            'type' => 'registration_bonus',
            'seconds' => 43200,
        ]);
    }

    public function test_registration_with_card_login_recharge_and_invitation_bonus(): void
    {
        [$application, $cardType] = $this->fixture();
        $credentials = app(CardGenerationService::class)->generate($cardType, 2, $this->administrator())['credentials'];
        $sessionHandle = str_repeat('a', 64);

        $issuedHandle = $this->postJson('/api/v2/apps/'.$application->public_id.'/sessions/handle')
            ->assertOk()->json('data.BSphpSeSsL');
        $this->assertIsString($issuedHandle);
        $this->assertSame(64, strlen($issuedHandle));

        $registered = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
            'username' => 'user001', 'password' => 'secret123', 'device_id' => 'IOS-FULL-DEVICE-001',
            'card_code' => $credentials[0]['code'], 'card_secret' => $credentials[0]['secret'],
            'BSphpSeSsL' => $sessionHandle,
        ])->assertCreated()->assertJsonPath('ok', true)->assertJsonPath('data.account.username', 'user001');

        $invite = $registered->json('data.account.invitation_code');
        $firstExpiry = $registered->json('data.account.expires_at');
        $this->assertNotEmpty($invite);
        $this->assertDatabaseHas('cards', ['status' => 'consumed']);
        $this->assertDatabaseHas('application_client_sessions', ['token_hash' => hash('sha256', $sessionHandle), 'status' => 'active']);
        $this->postJson('/api/v2/apps/'.$application->public_id.'/sessions/heartbeat', [
            'BSphpSeSsL' => $sessionHandle, 'device_id' => 'IOS-FULL-DEVICE-001',
        ])->assertOk()->assertJsonPath('data.code', '5031');

        $second = $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/register', [
            'username' => 'user002', 'password' => 'secret456', 'device_id' => 'IOS-FULL-DEVICE-002',
            'invitation_code' => $invite,
        ])->assertCreated()->assertJsonPath('ok', true);
        $this->assertNotNull($second->json('data.account.expires_at'));
        $this->assertTrue($application->accounts()->where('username', 'user001')->sole()->expires_at->greaterThan($firstExpiry));
        $this->assertDatabaseCount('account_entitlement_transactions', 3);

        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/login', [
            'username' => 'user001', 'password' => 'secret123', 'device_id' => 'IOS-FULL-DEVICE-001',
        ])->assertOk()->assertJsonPath('data.device', 'IOS-FULL-DEVICE-001');

        $this->postJson('/api/v2/apps/'.$application->public_id.'/accounts/recharge', [
            'username' => 'user002', 'card_code' => $credentials[1]['code'], 'card_secret' => $credentials[1]['secret'],
        ])->assertOk();
        $this->assertDatabaseCount('account_entitlement_transactions', 4);
    }

    public function test_appen_supports_bsphp_registration_and_login_api_names(): void
    {
        [$application, $cardType] = $this->fixture();
        $credential = app(CardGenerationService::class)->generate($cardType, 1, $this->administrator())['credentials'][0];
        $cipher = app(HybridPacketCipher::class);

        $registration = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'registrationaska.lg', 'user' => 'legacy01', 'pwd' => 'secret789',
            'key' => 'LEGACY-DEVICE', 'icid' => $credential['code'], 'icpwd' => $credential['secret'],
            'appsafecode' => 'SAFE-REG',
        ], '', '&', PHP_QUERY_RFC3986));
        $registered = $this->postJson('/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token, $registration['envelope'])->assertOk();
        $plain = $cipher->openResponse($registered->json(), $registration['session_key'], $registration['request_nonce']);
        $this->assertSame('01', explode('|', $plain)[0]);
        $this->assertStringContainsString('SAFE-REG', $plain);

        $login = $cipher->sealRequestWithContext($application, http_build_query([
            'api' => 'login.lg', 'user' => 'legacy01', 'pwd' => 'secret789', 'key' => 'LEGACY-DEVICE',
        ], '', '&', PHP_QUERY_RFC3986));
        $response = $this->postJson('/AppEn.php?appid='.$application->code.'&md5='.$application->gateway_token, $login['envelope'])->assertOk();
        $this->assertSame('01', explode('|', $cipher->openResponse($response->json(), $login['session_key'], $login['request_nonce']))[0]);
    }

    /** @return array{Application, CardType} */
    private function fixture(): array
    {
        $crypto = app(ApplicationCredentialService::class)->generate();
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(), 'code' => '301', 'name' => '账号演示',
            'mode' => 'account_recharge', 'entitlement_kind' => 'duration', 'status' => 'active',
            'payload_mode' => 'constant', 'payload_value_ciphertext' => 'ACCOUNT-PAYLOAD',
            'client_secret_hash' => hash('sha256', $crypto['communication_key_ciphertext']),
            'bind_device_enabled' => true, 'max_bound_devices' => 1, 'max_concurrent_sessions' => 1,
            'invitation_bonus_value' => 2, 'invitation_bonus_unit' => 'day', ...$crypto,
        ]);
        $application->legacyConfig()->create($application->legacyLoginDefaults());
        $type = CardType::query()->create([
            'public_id' => (string) Str::ulid(), 'application_id' => $application->getKey(), 'name' => '7天充值卡',
            'code' => 'week7', 'purpose' => 'account_recharge', 'benefit_kind' => 'duration',
            'duration_seconds' => 7 * 86400, 'face_price_cents' => 700, 'code_prefix' => 'LG',
            'code_length' => 20, 'secret_length' => 0, 'status' => 'active', 'max_activations' => 1,
        ]);
        return [$application, $type];
    }

    private function administrator(): Administrator
    {
        return Administrator::query()->create(['username' => 'account-'.Str::lower(Str::random(6)), 'password' => 'secret', 'role' => 'super_admin', 'status' => 'active']);
    }
}
