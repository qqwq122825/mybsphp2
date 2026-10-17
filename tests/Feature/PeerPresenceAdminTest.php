<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\Role;
use App\Presence\PeerPresenceRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class PeerPresenceAdminTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('p', 32)), 'peer_presence.store' => 'array']);
        Cache::store('array')->flush();
        $this->travelTo(CarbonImmutable::parse('2026-10-17 04:00:00', 'Asia/Shanghai'));
        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/peer-admin.lock');
        @unlink($this->lockPath);
        config(['mybsphp.installed' => false, 'mybsphp.install_lock' => $this->lockPath]);
        app(InstallationManager::class)->writeLock(['admin_path' => 'private-console', 'app_name' => 'Peer Admin Test', 'secondary_password_enabled' => false]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    private function software(string $code = '1002', bool $enabled = true): Application
    {
        return Application::query()->create([
            'public_id' => (string) Str::ulid(), 'code' => $code, 'name' => '三角洲国服',
            'mode' => 'card_login', 'entitlement_kind' => 'time', 'client_secret_hash' => 'fixture',
            'communication_key_ciphertext' => 'peer-test-key', 'peer_display_enabled' => $enabled,
        ]);
    }

    private function login(bool $allowed = true): void
    {
        $administrator = Administrator::query()->create([
            'username' => 'peer-admin-'.Str::random(8), 'password' => 'fixture',
            'role' => $allowed ? 'super_admin' : 'agent', 'status' => 'active',
        ]);
        $role = Role::query()->where('code', $allowed ? 'super_admin' : 'agent')->sole();
        if (! $allowed) {
            $role->permissions()->detach();
        }
        $administrator->roles()->attach($role);
        $this->actingAs($administrator, 'admin');
    }

    private function endpoint(Application $app): string
    {
        return '/private-console/applications/'.$app->public_id.'/peer-presence';
    }

    public function test_table_is_in_entertainment_tab_and_lists_unique_names_with_latest_beijing_time(): void
    {
        $app = $this->software();
        $other = $this->software('1003');
        $registry = app(PeerPresenceRegistry::class);
        $registry->update($app, (string) Str::uuid(), '  同行甲  ');
        $registry->update($other, (string) Str::uuid(), '别的软件');
        $this->travel(5)->minutes();
        $registry->update($app, (string) Str::uuid(), '同行乙');
        $this->travel(1)->minutes();
        $registry->update($app, (string) Str::uuid(), '同行甲');
        $this->login();
        $response = $this->getJson($this->endpoint($app))->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('rows.0.game_name', '同行甲')->assertJsonPath('rows.0.last_seen_at', '2026-10-17 04:06:00')
            ->assertJsonPath('rows.1.game_name', '同行乙')->assertJsonPath('rows.1.last_seen_at', '2026-10-17 04:05:00');
        $response->assertDontSee('别的软件')->assertDontSee('installation_id')->assertDontSee('digest');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get('/private-console/applications/'.$app->public_id.'/edit?config_tab=entertainment')->assertOk()
            ->assertSee('data-default-config-tab="entertainment"', false)->assertSee('同行名字')->assertSee('最近心跳时间（北京时间）')
            ->assertSee('同行甲')->assertSee('2026-10-17 04:06:00')->assertSee('data-peer-presence-config', false);
    }

    public function test_table_is_hidden_until_the_setting_is_opened(): void
    {
        $disabled = $this->software(enabled: false);
        $enabled = $this->software('1003');
        $this->login();

        $disabledHtml = $this->get('/private-console/applications/'.$disabled->public_id.'/edit?config_tab=entertainment')->assertOk()->getContent();
        $enabledHtml = $this->get('/private-console/applications/'.$enabled->public_id.'/edit?config_tab=entertainment')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<section class="peer-presence"[^>]*\shidden\s*>/', $disabledHtml);
        $this->assertDoesNotMatchRegularExpression('/<section class="peer-presence"[^>]*\shidden\s*>/', $enabledHtml);
    }

    public function test_paging_is_bounded_sorted_and_read_only(): void
    {
        $app = $this->software();
        $registry = app(PeerPresenceRegistry::class);
        for ($i = 0; $i < 60; $i++) {
            $registry->update($app, (string) Str::uuid(), sprintf('玩家%02d', $i));
            $this->travel(1)->seconds();
        }
        $before = Cache::store('array')->get('peer-presence:v1:'.$app->id);
        $this->login();
        $this->getJson($this->endpoint($app))->assertOk()->assertJsonCount(25, 'rows')->assertJsonPath('total', 60)->assertJsonPath('last_page', 3)->assertJsonPath('rows.0.game_name', '玩家59');
        $this->getJson($this->endpoint($app).'?peer_page=3')->assertOk()->assertJsonCount(10, 'rows')->assertJsonPath('rows.0.game_name', '玩家09');
        $this->getJson($this->endpoint($app).'?peer_page=999')->assertOk()->assertJsonPath('page', 3);
        $this->getJson($this->endpoint($app).'?peer_page=bad')->assertUnprocessable();
        $this->getJson($this->endpoint($app).'?peer_page=0')->assertUnprocessable();
        $this->assertSame($before, Cache::store('array')->get('peer-presence:v1:'.$app->id));
    }

    public function test_expiry_leave_rename_disabled_and_legacy_records_are_handled(): void
    {
        $app = $this->software();
        $registry = app(PeerPresenceRegistry::class);
        $id = (string) Str::uuid();
        Cache::store('array')->put('peer-presence:v1:'.$app->id, [$id => ['digest' => PeerPresenceRegistry::digest('1002', '旧摘要'), 'expires' => now()->timestamp + 1200]], 1200);
        $this->login();
        $this->getJson($this->endpoint($app))->assertOk()->assertJsonPath('total', 0)->assertJsonPath('pending_names', 1);
        $registry->update($app, $id, '补全名字');
        $this->getJson($this->endpoint($app))->assertJsonPath('rows.0.game_name', '补全名字')->assertJsonPath('pending_names', 0);
        $registry->update($app, $id, '修改后名字');
        $this->getJson($this->endpoint($app))->assertJsonPath('rows.0.game_name', '修改后名字')->assertJsonPath('total', 1);
        $registry->update($app, $id, null);
        $this->getJson($this->endpoint($app))->assertJsonPath('total', 0);
        $registry->update($app, $id, '二十分钟过期');
        $this->travel(20)->minutes();
        $this->getJson($this->endpoint($app))->assertJsonPath('total', 0);
        $registry->update($app, $id, '关闭后隐藏');
        $app->update(['peer_display_enabled' => false]);
        $this->getJson($this->endpoint($app))->assertJsonPath('enabled', false)->assertJsonPath('total', 0);
    }

    public function test_client_roster_stays_digest_only_and_md5_ignores_display_time(): void
    {
        $app = $this->software();
        $registry = app(PeerPresenceRegistry::class);
        $a = (string) Str::uuid();
        $b = (string) Str::uuid();
        $registry->update($app, $a, '只给管理员看的名字');
        $first = $registry->update($app, $b, '观察者');
        $this->assertSame(['digest', 'expires_in'], array_keys($first[0]));
        $this->assertStringNotContainsString('只给管理员看的名字', json_encode($first, JSON_UNESCAPED_UNICODE));
        $hash = PeerPresenceRegistry::snapshot('1002', $first);
        $this->travel(10)->minutes();
        $registry->update($app, $a, '只给管理员看的名字');
        $second = $registry->update($app, $b, '观察者');
        $this->assertSame($hash, PeerPresenceRegistry::snapshot('1002', $second));
    }

    public function test_names_are_escaped_and_the_endpoint_uses_the_edit_permission(): void
    {
        $app = $this->software();
        $name = '<img src=x onerror=alert(1)> </script> {{ payload }}';
        app(PeerPresenceRegistry::class)->update($app, (string) Str::uuid(), $name);
        $this->getJson($this->endpoint($app))->assertRedirect('/private-console');
        $this->login(false);
        $this->getJson($this->endpoint($app))->assertForbidden();
        $this->login();
        $this->getJson($this->endpoint($app))->assertOk()->assertJsonPath('rows.0.game_name', $name);
        $this->get('/private-console/applications/'.$app->public_id.'/edit')->assertOk()->assertSee(e($name), false)->assertDontSee($name, false);
    }

    public function test_cache_failure_does_not_break_other_software_settings(): void
    {
        $app = $this->software();
        $this->login();
        Cache::partialMock()->shouldReceive('store')->with('array')->andThrow(new \RuntimeException('fixture unavailable'));
        $this->getJson($this->endpoint($app))->assertStatus(503)->assertJsonPath('error', '同行名单暂时读取失败，请点击刷新重试。');
        $this->get('/private-console/applications/'.$app->public_id.'/edit')->assertOk()->assertSee('同行名单暂时读取失败');
    }
}
