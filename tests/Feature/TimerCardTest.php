<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Applications\HybridPacketCipher;
use App\Auth\SystemAccessSynchronizer;
use App\Cards\CardGenerationService;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use App\Models\Role;
use App\Proxy\ProxyLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TimerCardTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    private Application $application;

    private CardType $cardType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfSecond());
        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/timer-card.lock');
        @unlink($this->lockPath);
        config(['mybsphp.installed' => false, 'mybsphp.install_lock' => $this->lockPath]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'timer-console',
            'secondary_password_enabled' => false,
        ]);

        $credentials = app(ApplicationCredentialService::class)->generate();
        $this->application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => '701',
            'name' => '计时卡应用',
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
        $this->application->riskPolicy()->create(['enabled' => false]);
        $this->cardType = CardType::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $this->application->getKey(),
            'name' => '10 天卡',
            'code' => 'day10',
            'purpose' => 'login_credential',
            'benefit_kind' => 'duration',
            'duration_seconds' => 10 * 86400,
            'duration_value' => 10,
            'duration_unit' => 'day',
            'code_prefix' => 'TM',
            'code_length' => 20,
            'secret_length' => 0,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    public function test_make_page_offers_timer_checkbox_and_creates_timer_cards(): void
    {
        $this->actingAs($this->administrator(), 'admin');

        $this->get('/timer-console/applications/'.$this->application->public_id.'/cards/make')
            ->assertOk()->assertSee('制作为计时卡')->assertSee('冻结次数上限')->assertSee('单次最长冻结');

        $this->post('/timer-console/card-batches', [
            'card_type_id' => $this->cardType->getKey(), 'generation_mode' => 'automatic', 'quantity' => 2,
            'is_timer' => '1', 'timer_pause_limit' => 5, 'timer_pause_max_days' => 30,
        ])->assertRedirect();

        $batch = CardBatch::query()->latest('id')->firstOrFail();
        $this->assertTrue($batch->is_timer);
        $cards = Card::query()->where('batch_id', $batch->id)->get();
        $this->assertCount(2, $cards);
        foreach ($cards as $card) {
            $this->assertTrue($card->is_timer);
            $this->assertSame(5, $card->timer_pause_limit);
            $this->assertSame(30 * 86400, $card->timer_pause_max_seconds);
        }

        // 不勾选时仍是普通卡。
        $this->post('/timer-console/card-batches', [
            'card_type_id' => $this->cardType->getKey(), 'generation_mode' => 'automatic', 'quantity' => 1,
        ])->assertRedirect();
        $this->assertFalse(Card::query()->latest('id')->firstOrFail()->is_timer);
    }

    public function test_only_duration_types_can_be_made_as_timer_cards(): void
    {
        $this->actingAs($this->administrator(), 'admin');
        $points = $this->cardType->replicate()->fill([
            'public_id' => (string) Str::ulid(), 'code' => 'pts', 'benefit_kind' => 'points', 'points_amount' => 10,
        ]);
        $points->save();

        $this->post('/timer-console/card-batches', [
            'card_type_id' => $points->getKey(), 'generation_mode' => 'automatic', 'quantity' => 1, 'is_timer' => '1',
        ])->assertSessionHasErrors('is_timer');
        $this->assertDatabaseCount('cards', 0);
    }

    public function test_pause_stops_the_clock_and_resume_extends_expiry(): void
    {
        $code = $this->timerCard();
        $this->login($code)->assertOk();
        $card = $this->cardByCode($code);
        $originalExpiry = $card->expires_at;

        $paused = $this->legacy('pause.ic', $code);
        $this->assertSame('01', $paused[0]);
        $this->assertSame('paused', $paused[1]);
        $this->assertSame(10 * 86400, (int) $paused[2]);
        $this->assertSame('1', $paused[4]);
        $this->assertSame('-1', $paused[5]);

        // 冻结 3 天：剩余时长不变，到期时间随之顺延。
        $this->travel(3)->days();
        $status = $this->legacy('timer.ic', $code);
        $this->assertSame('paused', $status[1]);
        $this->assertSame(10 * 86400, (int) $status[2]);
        $this->assertSame('paused', $this->cardByCode($code)->softwareUserStatus());

        $resumed = $this->legacy('resume.ic', $code);
        $this->assertSame('active', $resumed[1]);
        $this->assertSame(10 * 86400, (int) $resumed[2]);
        $card = $this->cardByCode($code);
        $this->assertNull($card->timer_paused_at);
        $this->assertSame($originalExpiry->addDays(3)->getTimestamp(), $card->expires_at->getTimestamp());

        // 恢复后正常计时；重复恢复是幂等的。
        $this->travel(1)->days();
        $again = $this->legacy('resume.ic', $code);
        $this->assertSame('active', $again[1]);
        $this->assertSame(9 * 86400, (int) $again[2]);

        $this->assertTrue(AuditLog::query()->where('action', 'card.operation')->where('reason', 'like', '%冻结计时%')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'card.operation')->where('reason', 'like', '%恢复计时%')->exists());
    }

    public function test_login_automatically_resumes_a_paused_timer_card(): void
    {
        $code = $this->timerCard();
        $this->login($code)->assertOk();
        $expiry = $this->cardByCode($code)->expires_at;
        $this->legacy('pause.ic', $code);
        $this->travel(2)->days();

        $fields = $this->openLogin($code);
        $this->assertSame('01', $fields[0]);
        $card = $this->cardByCode($code);
        $this->assertNull($card->timer_paused_at);
        $this->assertSame($expiry->addDays(2)->getTimestamp(), $card->expires_at->getTimestamp());
        $this->assertSame($card->expires_at->toIso8601String(), $fields[6]);
    }

    public function test_normal_cards_and_unactivated_timer_cards_cannot_pause(): void
    {
        $normal = $this->timerCard(false);
        $this->login($normal)->assertOk();
        $this->legacyError('pause.ic', $normal)->assertJsonPath('error.code', 'TIMER_NOT_SUPPORTED');

        $inventory = $this->timerCard();
        $this->legacyError('pause.ic', $inventory)->assertJsonPath('error.code', 'TIMER_NOT_ACTIVATED');
    }

    public function test_pause_limit_double_pause_and_device_binding(): void
    {
        $code = $this->timerCard(true, ['pause_limit' => 1]);
        $this->login($code)->assertOk();
        $this->legacy('pause.ic', $code);
        $this->legacyError('pause.ic', $code)->assertJsonPath('error.code', 'TIMER_ALREADY_PAUSED');
        $this->legacy('resume.ic', $code);
        $this->legacyError('pause.ic', $code)->assertJsonPath('error.code', 'TIMER_PAUSE_LIMIT');

        // 其他设备不能替绑定设备冻结，失败按卡密错误统一伪装。
        $other = $this->timerCard();
        $this->login($other)->assertOk();
        $this->legacyError('pause.ic', $other, 'ANOTHER-DEVICE')->assertJsonPath('error.code', 'INVALID_CARD');
        $this->assertNull($this->cardByCode($other)->timer_paused_at);
    }

    public function test_pause_auto_resumes_after_the_maximum_duration(): void
    {
        $code = $this->timerCard(true, ['pause_max_seconds' => 2 * 86400]);
        $this->login($code)->assertOk();
        $expiry = $this->cardByCode($code)->expires_at;
        $this->legacy('pause.ic', $code);

        $this->travel(5)->days();
        $status = $this->legacy('timer.ic', $code);
        // 只顺延 2 天，之后 3 天正常扣除。
        $this->assertSame('active', $status[1]);
        $this->assertSame(7 * 86400, (int) $status[2]);
        $this->assertSame($expiry->addDays(2)->getTimestamp(), $this->cardByCode($code)->effectiveExpiresAt()->getTimestamp());
    }

    public function test_proxy_link_reports_paused_cards_and_restores_them_after_resume(): void
    {
        $link = app(ProxyLinkService::class)->ensure($this->application);
        $link->forceFill(['enabled' => true])->save();
        config(['app.url' => 'https://license.example']);
        $code = $this->timerCard();
        $this->login($code)->assertOk();

        $this->postJson('/'.$link->path)->assertOk()->assertJsonPath('data.users.0.status', 'active')
            ->assertJsonMissingPath('data.users.0.remaining_seconds');

        $this->legacy('pause.ic', $code);
        $this->travel(1)->days();
        $row = $this->postJson('/'.$link->path)->assertOk()->json('data.users.0');
        $this->assertSame('paused', $row['status']);
        $this->assertSame(10 * 86400, $row['remaining_seconds']);
        $this->assertSame(now()->addDays(10)->getTimestamp(), $row['expires_at']);

        $this->legacy('resume.ic', $code);
        $this->postJson('/'.$link->path)->assertOk()->assertJsonPath('data.users.0.status', 'active')
            ->assertJsonPath('data.users.0.expires_at', now()->addDays(10)->getTimestamp());
    }

    public function test_admin_add_time_on_a_paused_card_keeps_the_remaining_time(): void
    {
        $code = $this->timerCard();
        $this->login($code)->assertOk();
        $this->legacy('pause.ic', $code);
        $this->travel(20)->days(); // 冻结时长超过原有效期，到期时间已在过去。

        $card = $this->cardByCode($code);
        $card->rebaseTimerPause();
        $card->forceFill(['expires_at' => $card->expires_at->addDays(1)])->save();

        $card = $this->cardByCode($code);
        $this->assertTrue($card->isTimerPaused());
        $this->assertSame(11 * 86400, $card->timerRemainingSeconds());
        $this->assertSame('paused', $card->softwareUserStatus());
    }

    public function test_public_page_lets_users_pause_and_resume_by_card_code(): void
    {
        $code = $this->timerCard();
        $this->login($code)->assertOk();
        $expiry = $this->cardByCode($code)->expires_at;

        // 用户自助页独立，不链接代理使用的激活码查询 / 追踪解锁工具。
        $this->get('/card-timer')->assertOk()->assertSee('计时卡冻结')->assertSee('查询')
            ->assertDontSee('激活码查询')->assertDontSee('追踪解锁密码');
        $this->get('/activation-query')->assertOk()->assertDontSee('/card-timer');

        $this->post('/card-timer', ['credential' => $code, 'action' => 'status'])
            ->assertOk()->assertSee('正在计时')->assertSee('冻结计时')->assertSee('计时卡应用')
            ->assertDontSee('冻结次数') // 未设置次数上限时不显示
            ->assertSee('冻结后端口会拒绝连接，禁止在游玩时冻结卡密');

        $limited = $this->timerCard(true, ['pause_limit' => 3]);
        $this->login($limited)->assertOk();
        $this->post('/card-timer', ['credential' => $limited, 'action' => 'status'])
            ->assertOk()->assertSee('冻结次数')->assertSee('已用 0 次，共 3 次');

        // 网页没有机器码，只凭卡号操作，不受设备绑定限制。
        $this->post('/card-timer', ['credential' => $code, 'action' => 'pause'])
            ->assertOk()->assertSee('已冻结，不扣时长')->assertSee('恢复计时')
            ->assertDontSee('禁止在游玩时冻结卡密');
        $this->assertTrue($this->cardByCode($code)->isTimerPaused());

        $this->travel(2)->days();
        $this->post('/card-timer', ['credential' => $code, 'action' => 'resume'])
            ->assertOk()->assertSee('已恢复计时')->assertSee('10 天');
        $card = $this->cardByCode($code);
        $this->assertFalse($card->isTimerPaused());
        $this->assertSame($expiry->addDays(2)->getTimestamp(), $card->expires_at->getTimestamp());
    }

    public function test_public_page_rejects_unknown_and_normal_cards(): void
    {
        $this->post('/card-timer', ['credential' => 'NOPE-NOT-A-CARD', 'action' => 'pause'])
            ->assertOk()->assertSee('卡号不存在，请核对后重试。');

        $normal = $this->timerCard(false);
        $this->login($normal)->assertOk();
        $this->post('/card-timer', ['credential' => $normal, 'action' => 'pause'])
            ->assertOk()->assertSee('该卡不支持冻结。');
        $this->assertNull($this->cardByCode($normal)->timer_paused_at);

        $this->post('/card-timer', ['credential' => $normal, 'action' => 'delete'])
            ->assertSessionHasErrors('action');
    }

    public function test_public_page_does_not_operate_cards_that_have_a_password(): void
    {
        $this->cardType->forceFill(['secret_length' => 8])->save();
        $credential = app(CardGenerationService::class)->generate($this->cardType, 1, null, timer: [])['credentials'][0];
        $card = $this->cardByCode($credential['code']);
        $card->forceFill(['status' => 'active', 'activated_at' => now(), 'expires_at' => now()->addDays(10)])->save();

        $this->post('/card-timer', ['credential' => $credential['code'], 'action' => 'pause'])
            ->assertOk()->assertSee('卡号不存在，请核对后重试。');
        $this->assertNull($card->fresh()->timer_paused_at);
    }

    public function test_user_list_shows_effective_expiry_quick_buttons_and_keeps_seconds(): void
    {
        $this->actingAs($this->administrator(), 'admin');
        $code = $this->timerCard();
        $this->login($code)->assertOk();
        $this->legacy('pause.ic', $code);
        $this->travel(20)->days(); // 冻结超过原有效期，原始 expires_at 已在过去
        $card = $this->cardByCode($code);
        $effective = $card->effectiveExpiresAt();

        $this->get('/timer-console/applications/'.$this->application->public_id.'/users')
            ->assertOk()
            ->assertSee('data-expires="'.$effective->format('Y-m-d\TH:i:s').'"', false)
            ->assertSee('（冻结中）')
            ->assertDontSee('<strong>已到期</strong>', false)
            ->assertSee('data-quick-days="7"', false)->assertSee('快速加时');

        // 只改备注、到期时间按弹窗原值提交：剩余时长不变，秒数不丢，仍处于冻结。
        $this->put('/timer-console/applications/'.$this->application->public_id.'/card-users/'.$card->id, [
            'expires_at' => $effective->format('Y-m-d\TH:i:s'), 'bound_device' => 'TIMER-DEVICE',
            'account_concurrency_limit' => 0, 'machine_concurrency_limit' => 0, 'note' => '只改备注',
        ])->assertRedirect();
        $card = $this->cardByCode($code);
        $this->assertTrue($card->isTimerPaused());
        $this->assertSame(10 * 86400, $card->timerRemainingSeconds());
        $this->assertSame('只改备注', $card->user_note);

        // 快速加 3 天后保存（等同于到期时间 +3 天）：剩余时长 +3 天。
        $this->put('/timer-console/applications/'.$this->application->public_id.'/card-users/'.$card->id, [
            'expires_at' => $card->effectiveExpiresAt()->addDays(3)->format('Y-m-d\TH:i:s'), 'bound_device' => 'TIMER-DEVICE',
            'account_concurrency_limit' => 0, 'machine_concurrency_limit' => 0, 'note' => '只改备注',
        ])->assertRedirect();
        $this->assertSame(13 * 86400, $this->cardByCode($code)->timerRemainingSeconds());
    }

    /** @param array{pause_limit?: int, pause_max_seconds?: int} $timer */
    private function timerCard(bool $isTimer = true, array $timer = []): string
    {
        return app(CardGenerationService::class)
            ->generate($this->cardType, 1, null, timer: $isTimer ? $timer : null)['credentials'][0]['code'];
    }

    private function cardByCode(string $code): Card
    {
        return Card::query()->where('code_ciphertext', '!=', '')->get()
            ->firstOrFail(fn (Card $card) => $card->code_ciphertext === $code);
    }

    private function gateway(): string
    {
        return '/AppEn.php?appid='.$this->application->code.'&md5='.$this->application->gateway_token;
    }

    /** @return array{envelope: array<string, mixed>, session_key: string, request_nonce: string} */
    private function seal(string $api, string $code, string $device): array
    {
        return app(HybridPacketCipher::class)->sealRequestWithContext($this->application, http_build_query([
            'api' => $api, 'icid' => $code, 'key' => $device, 'appsafecode' => 'TIMER-SAFE',
            'BSphpSeSsL' => hash('sha256', $code),
        ], '', '&', PHP_QUERY_RFC3986));
    }

    private function login(string $code): TestResponse
    {
        $response = $this->postJson($this->gateway(), $this->seal('login.ic', $code, 'TIMER-DEVICE')['envelope']);
        $this->assertNull($response->json('error'), (string) $response->getContent());

        return $response;
    }

    /** @return list<string> */
    private function openLogin(string $code): array
    {
        $sealed = $this->seal('login.ic', $code, 'TIMER-DEVICE');
        $response = $this->postJson($this->gateway(), $sealed['envelope'])->assertOk()->json();

        return explode('|', app(HybridPacketCipher::class)->openResponse($response, $sealed['session_key'], $sealed['request_nonce']));
    }

    /** @return list<string> */
    private function legacy(string $api, string $code, string $device = 'TIMER-DEVICE'): array
    {
        $sealed = $this->seal($api, $code, $device);
        $response = $this->postJson($this->gateway(), $sealed['envelope'])->assertOk()->json();
        $this->assertArrayNotHasKey('error', $response, json_encode($response, JSON_UNESCAPED_UNICODE));

        return explode('|', app(HybridPacketCipher::class)->openResponse($response, $sealed['session_key'], $sealed['request_nonce']));
    }

    private function legacyError(string $api, string $code, string $device = 'TIMER-DEVICE'): TestResponse
    {
        $response = $this->postJson($this->gateway(), $this->seal($api, $code, $device)['envelope']);
        $response->assertJsonPath('ok', false);

        return $response;
    }

    private function administrator(): Administrator
    {
        $administrator = Administrator::query()->create([
            'username' => 'timer-admin', 'password' => 'primary-secret', 'role' => 'super_admin', 'status' => 'active',
        ]);
        $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());

        return $administrator;
    }
}
