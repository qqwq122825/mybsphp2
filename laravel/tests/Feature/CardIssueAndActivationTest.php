<?php

namespace Tests\Feature;

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
            ->assertJsonPath('data.payload', 'A1B2C3D4E5');

        $legacy = $response->json('data.legacy_response');
        $this->assertIsString($legacy);
        $fields = explode('|', $legacy);
        $this->assertSame('01', $fields[0]);
        $this->assertSame('xx', $fields[1]);
        $this->assertSame('A1B2C3D4E5', $fields[2]);
        $this->assertSame('SAFE-123', $fields[3]);
        $this->assertSame(10, strlen($fields[4]));
        $this->assertSame('IOS-DEVICE-001', $fields[8]);

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

    public function test_inventory_card_is_rejected_before_manual_issue(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->postJson('/api/v2/demo/activate', [
            'code' => $credential['code'],
            'secret' => $credential['secret'],
            'device_id' => 'IOS-DEVICE-001',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'CARD_NOT_ISSUED');
    }

    public function test_wrong_secret_does_not_reveal_card_existence(): void
    {
        [$application, $cardType] = $this->applicationAndType();
        $credential = app(CardGenerationService::class)
            ->generate($cardType, 1, $this->administrator())['credentials'][0];

        $this->postJson('/api/v2/demo/activate', [
            'code' => $credential['code'],
            'secret' => 'WRONG-SECRET',
            'device_id' => 'IOS-DEVICE-001',
        ])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'INVALID_CARD');
    }

    /**
     * @return array{Application, CardType}
     */
    private function applicationAndType(): array
    {
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => 'demo_ios',
            'name' => '激活演示应用',
            'mode' => 'card_login',
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'payload_mode' => 'constant',
            'payload_value_ciphertext' => 'A1B2C3D4E5',
            'client_secret_hash' => 'not-used-in-fixture',
            'bind_device_enabled' => true,
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 1,
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
            'secret_length' => 8,
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
