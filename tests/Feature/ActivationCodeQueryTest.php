<?php

namespace Tests\Feature;

use App\Cards\CardCredentialHasher;
use App\Models\Application;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivationCodeQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_code_query_reports_not_found_and_card_status(): void
    {
        $this->get('/activation-query')
            ->assertOk()
            ->assertSee('激活码查询')
            ->assertSee('请输入激活码或充值卡编码')
            ->assertSee('/assets/css/activation-query.css', false);

        $this->post('/activation-query', ['credential' => 'UNKNOWN-CARD-001'])
            ->assertOk()
            ->assertSee('您查询的授权码不存在，请核对完整编码');

        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => 'query-demo',
            'name' => '查询测试软件',
            'mode' => 'card_login',
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'client_secret_hash' => hash('sha256', 'query-secret'),
        ]);
        $cardType = CardType::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'name' => '30天充值卡',
            'code' => 'query30',
            'purpose' => 'login_credential',
            'benefit_kind' => 'duration',
            'duration_seconds' => 30 * 86400,
            'duration_value' => 30,
            'duration_unit' => 'day',
            'code_length' => 24,
            'secret_length' => 0,
            'status' => 'active',
        ]);
        $batch = CardBatch::query()->create([
            'public_id' => (string) Str::ulid(),
            'card_type_id' => $cardType->getKey(),
            'batch_no' => 'QUERY-'.Str::upper(Str::random(10)),
            'source' => 'generated',
            'planned_quantity' => 1,
            'actual_quantity' => 1,
            'status' => 'ready',
        ]);
        $credential = 'QUERY-CARD-20260722-001';
        Card::query()->create([
            'public_id' => (string) Str::ulid(),
            'batch_id' => $batch->getKey(),
            'card_type_id' => $cardType->getKey(),
            'application_id' => $application->getKey(),
            'code_lookup_hash' => app(CardCredentialHasher::class)->lookupHash($credential),
            'code_ciphertext' => $credential,
            'code_last4' => '0001',
            'status' => 'issued',
            'card_is_frozen' => false,
        ]);

        $this->post('/activation-query', ['credential' => $credential])
            ->assertOk()
            ->assertSee('您查询的授权码未激活')
            ->assertDontSee('activation-query-result-details', false);

        $card = Card::query()->sole();
        $card->forceFill([
            'status' => 'active',
            'card_is_frozen' => true,
            'activated_at' => '2026-07-22 10:00:00',
            'expires_at' => '2026-08-21 10:00:00',
        ])->save();

        $this->post('/activation-query', ['credential' => $credential])
            ->assertOk()
            ->assertSee('您查询的授权码已冻结')
            ->assertSee('查询测试软件')
            ->assertSee('30天充值卡')
            ->assertSee('已激活')
            ->assertSee('已冻结')
            ->assertSee('2026-08-21 10:00:00');
    }
}
