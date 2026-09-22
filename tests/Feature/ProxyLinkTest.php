<?php

namespace Tests\Feature;

use App\Admin\SoftwareUiAssets;
use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use App\Models\ProxyCredential;
use App\Models\ProxyLink;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Proxy\ProxyLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProxyLinkTest extends TestCase
{
    use RefreshDatabase;

    private Application $application;

    private ProxyLink $link;

    private string $node;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfSecond());
        $this->application = $this->makeApplication('1002');
        $this->link = app(ProxyLinkService::class)->ensure($this->application);
        $this->link->forceFill(['enabled' => true])->save();
        $this->node = (string) Str::uuid();
        $this->lockPath = storage_path('framework/testing/proxy-link.lock');
        @unlink($this->lockPath);
        config(['mybsphp.installed' => false, 'mybsphp.install_lock' => $this->lockPath, 'app.url' => 'https://license.example']);
        app(InstallationManager::class)->writeLock(['admin_path' => 'proxy-admin', 'secondary_password_enabled' => false]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    private function makeApplication(string $code, string $mode = 'card_login'): Application
    {
        return Application::query()->create([
            'public_id' => (string) Str::ulid(), 'code' => $code, 'name' => '联动测试应用',
            'mode' => $mode, 'entitlement_kind' => 'duration', 'status' => 'active',
            'client_secret_hash' => 'test-only',
        ]);
    }

    private function card(array $changes = [], ?Application $application = null): Card
    {
        $application ??= $this->application;
        $type = CardType::query()->create([
            'public_id' => (string) Str::ulid(), 'application_id' => $application->id,
            'name' => '一小时卡', 'code' => Str::random(12), 'purpose' => 'login',
            'benefit_kind' => 'duration', 'duration_seconds' => 3600,
            'status' => 'active',
        ]);
        $batch = CardBatch::query()->create([
            'public_id' => (string) Str::ulid(), 'card_type_id' => $type->id,
            'batch_no' => Str::random(20), 'source' => 'generated', 'planned_quantity' => 1,
            'actual_quantity' => 1, 'status' => 'completed',
        ]);

        return Card::query()->create(array_merge([
            'public_id' => (string) Str::ulid(), 'application_id' => $application->id,
            'card_type_id' => $type->id, 'batch_id' => $batch->id,
            'code_lookup_hash' => hash('sha256', Str::random(30)), 'code_ciphertext' => 'TEST-CARD-'.Str::random(16),
            'code_last4' => '1234', 'status' => 'active', 'user_status' => 'active',
            'activated_at' => now(), 'expires_at' => now()->addHour(),
        ], $changes));
    }

    private function sync(array $payload = [], ?string $path = null)
    {
        return $this->postJson('/'.($path ?? $this->link->path), array_merge([
            'action' => 'sync', 'node_id' => $this->node, 'node_name' => '测试节点', 'app_id' => '1002',
        ], $payload), ['Authorization' => 'Bearer '.$this->link->token_ciphertext]);
    }

    public function test_random_path_is_stable_and_endpoint_has_no_browser_middleware(): void
    {
        $again = app(ProxyLinkService::class)->ensure($this->application);
        $this->assertSame($this->link->path, $again->path);
        $this->assertMatchesRegularExpression('/^lk_[a-f0-9]{32}$/', $again->path);
        $this->assertSame('https://license.example/'.$again->path, $again->address());
        $route = app('router')->getRoutes()->getByName('proxy.link');
        $this->assertNotContains('web', $route->gatherMiddleware());
        $this->sync(['action' => 'ping'])->assertOk()->assertJsonMissingPath('data.users')
            ->assertJsonPath('data.app_id', '1002')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertNull($this->link->fresh()->node_id);
        $this->assertDatabaseCount('proxy_credentials', 0);
    }

    public function test_token_and_application_and_node_isolation(): void
    {
        $this->postJson('/'.$this->link->path, ['token' => $this->link->token_ciphertext])->assertUnauthorized();
        $this->sync(['app_id' => '9999'])->assertForbidden()->assertJsonPath('error.code', 'APP_MISMATCH');
        $this->sync()->assertOk();
        $this->sync(['node_id' => (string) Str::uuid()])->assertForbidden()->assertJsonPath('error.code', 'NODE_MISMATCH');
        $this->postJson('/lk_'.str_repeat('0', 32))->assertNotFound();
        $this->assertSame($this->node, $this->link->fresh()->node_id);
    }

    public function test_complete_snapshot_only_contains_activated_subjects_from_this_app(): void
    {
        $active = $this->card();
        $this->card(['activated_at' => null, 'status' => 'inventory']);
        $this->card([], $this->makeApplication('1003'));
        $response = $this->sync()->assertOk()->assertJsonPath('data.complete', true)
            ->assertJsonCount(1, 'data.users')->assertJsonPath('data.users.0.expires_at', $active->expires_at->getTimestamp());
        $row = $response->json('data.users.0');
        $this->assertSame('card:'.$active->public_id, $row['subject_id']);
        $this->assertSame('active', $row['status']);
        $this->assertSame($active->code_ciphertext, $row['username']);
        $this->assertSame($row['username'], $row['password']);
        $this->assertNotSame($row['password'], $active->getRawOriginal('code_ciphertext'));
        $this->assertDatabaseCount('proxy_credentials', 0);
    }

    public function test_user_freeze_card_freeze_revocation_expiry_and_null_have_distinct_semantics(): void
    {
        $this->card(['card_is_frozen' => true]);
        $this->card(['user_status' => 'frozen']);
        $this->card(['user_status' => 'revoked']);
        $this->card(['expires_at' => now()]);
        $this->card(['expires_at' => null]);
        $this->sync()->assertOk()
            ->assertJsonPath('data.users.0.status', 'active')
            ->assertJsonPath('data.users.1.status', 'frozen')
            ->assertJsonPath('data.users.2.status', 'revoked')
            ->assertJsonPath('data.users.3.status', 'expired')
            ->assertJsonPath('data.users.4.status', 'expired');
    }

    public function test_points_card_is_not_permanent_and_renewal_keeps_credentials(): void
    {
        $card = $this->card();
        $first = $this->sync()->json('data.users.0');
        $card->forceFill(['expires_at' => now()->addDays(3)])->save();
        $next = $this->sync()->json('data.users.0');
        $this->assertSame($first['username'], $next['username']);
        $this->assertSame($first['password'], $next['password']);
        $this->assertGreaterThan($first['expires_at'], $next['expires_at']);
        $card->cardType->forceFill(['benefit_kind' => 'points'])->save();
        $this->sync()->assertJsonPath('data.users.0.status', 'unsupported');
        $card->delete();
        $this->sync()->assertJsonCount(0, 'data.users');
    }

    public function test_disabled_app_link_and_global_switch_are_explicit_errors(): void
    {
        $this->link->forceFill(['enabled' => false])->save();
        $this->sync()->assertForbidden()->assertJsonPath('error.code', 'LINK_DISABLED');
        $this->link->forceFill(['enabled' => true])->save();
        $this->application->forceFill(['status' => 'suspended'])->save();
        $this->sync()->assertForbidden()->assertJsonPath('error.code', 'APP_DISABLED');
        $this->application->forceFill(['status' => 'active'])->save();
        SystemSetting::query()->updateOrCreate(['key' => 'client_service_enabled'], ['value' => 'false']);
        $this->sync()->assertForbidden()->assertJsonPath('error.code', 'SERVICE_DISABLED');
    }

    public function test_account_recharge_binds_account_not_each_recharge_card(): void
    {
        $this->application->forceFill(['mode' => 'account_recharge'])->save();
        $account = ApplicationAccount::query()->create([
            'public_id' => (string) Str::ulid(), 'application_id' => $this->application->id,
            'username' => 'customer', 'password_hash' => 'fixture', 'status' => 'active', 'expires_at' => now()->addHour(),
        ]);
        $row = $this->sync()->assertJsonPath('data.users.0.subject_id', 'account:'.$account->public_id)
            ->assertJsonPath('data.users.0.status', 'active')->json('data.users.0');
        $this->assertSame($row['username'], $row['password']);
        $this->assertNotSame($row['password'], $account->username);
        $this->assertNotSame($row['password'], ProxyCredential::query()->sole()->getRawOriginal('password_ciphertext'));
        $this->assertNotSame($row['password'], ProxyCredential::query()->sole()->getRawOriginal('username'));
        $account->forceFill(['status' => 'revoked'])->save();
        $this->sync()->assertJsonPath('data.users.0.status', 'revoked');
    }

    private function administrator(): void
    {
        app(SystemAccessSynchronizer::class)->sync();
        $admin = Administrator::query()->create([
            'username' => 'proxy-admin', 'password' => 'fixture-password', 'role' => 'super_admin', 'status' => 'active',
        ]);
        $admin->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
        $this->actingAs($admin, 'admin');
    }

    public function test_admin_ui_and_rotation_and_node_reset(): void
    {
        $this->administrator();
        $base = '/proxy-admin/applications/'.$this->application->public_id;
        $this->get($base.'/edit?config_tab=proxy')->assertOk()->assertSee('代理联动')->assertSee('data-proxy-link-config', false);
        $this->sync()->assertOk();
        $old = $this->link->path;
        $this->post($base.'/proxy-link', ['enabled' => '1', 'permission' => 'both', 'action' => 'rotate_path'])->assertRedirect();
        $this->link->refresh();
        $this->assertNotSame($old, $this->link->path);
        $this->sync([], $old)->assertOk();
        $this->travel(11)->minutes();
        $this->sync([], $old)->assertNotFound();
        $token = $this->link->token_ciphertext;
        $this->post($base.'/proxy-link', ['enabled' => '1', 'permission' => 'both', 'action' => 'rotate_token'])->assertRedirect();
        $this->link->refresh();
        $this->assertNull($this->link->node_id);
        $this->assertNotSame($token, $this->link->token_ciphertext);
        $this->postJson('/'.$this->link->path, [], ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
    }

    public function test_admin_lists_active_card_credentials_and_expired_card_history_is_preserved(): void
    {
        $this->administrator();
        $active = $this->card();
        $expired = $this->card(['expires_at' => now()->subSecond()]);
        $base = '/proxy-admin/applications/'.$this->application->public_id.'/edit?config_tab=proxy';
        $this->get($base)->assertOk()->assertSee($active->code_ciphertext)->assertDontSee($expired->code_ciphertext);
        $this->get($base.'&proxy_search=card:'.$active->public_id)->assertOk()->assertSee($active->code_ciphertext);
        $this->sync()->assertOk()->assertJsonPath('data.users.1.status', 'expired');
        $this->assertDatabaseHas('cards', ['id' => $expired->id]);
        $expired->forceFill(['expires_at' => now()->addHour()])->save();
        $this->sync()->assertOk()->assertJsonPath('data.users.1.status', 'active')
            ->assertJsonPath('data.users.1.username', $expired->code_ciphertext)
            ->assertJsonPath('data.users.1.password', $expired->code_ciphertext);
    }

    public function test_enabling_link_generates_address_and_reenable_keeps_it(): void
    {
        $this->administrator();
        $this->link->delete();
        $base = '/proxy-admin/applications/'.$this->application->public_id;
        $disabled = $this->get($base.'/edit?config_tab=proxy')->assertOk()
            ->assertSee('启用代理联动')->assertSee('尚未生成联动地址');
        $this->assertDatabaseCount('proxy_links', 0);
        $this->post($base.'/proxy-link', ['enabled' => '1', 'permission' => 'replay', 'action' => 'save'])
            ->assertRedirect($base.'/edit?config_tab=proxy');
        $link = ProxyLink::query()->sole();
        $this->assertTrue($link->enabled);
        $this->assertMatchesRegularExpression('/^lk_[a-f0-9]{32}$/', $link->path);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $link->token_ciphertext);
        $enabled = $this->get($base.'/edit?config_tab=proxy')->assertOk()->assertSee($link->address());
        foreach ([false, true] as $state) {
            $this->post($base.'/proxy-link', ['enabled' => $state ? '1' : '0', 'permission' => 'replay', 'action' => 'save'])->assertRedirect();
            $saved = $link->fresh();
            $this->assertSame($state, $saved->enabled);
            $this->assertSame($link->path, $saved->path);
            $this->assertSame($link->token_ciphertext, $saved->token_ciphertext);
            $this->assertDatabaseCount('proxy_links', 1);
        }
        // Optional synthetic browser fixtures; exercise the actual initial/after-save Blade responses.
        if ($directory = getenv('DFM_LINK_TOGGLE_PREVIEW_DIR')) {
            file_put_contents($directory.'/disabled.html', $disabled->getContent());
            file_put_contents($directory.'/enabled.html', $enabled->getContent());
        }
    }

    public function test_old_or_missing_assets_keep_the_native_link_switch_visible(): void
    {
        $this->mock(SoftwareUiAssets::class, fn ($mock) => $mock->shouldReceive('ready')->andReturn(false));
        $this->administrator();
        $this->link->delete();
        $base = '/proxy-admin/applications/'.$this->application->public_id;
        $disabled = $this->get($base.'/edit?config_tab=proxy')->assertOk()
            ->assertSee('data-software-config-native', false)->assertSee('data-software-ui-compat', false)
            ->assertDontSee('data-element-config', false)->assertSee('role="switch"', false)
            ->assertSee('data-config-submit-bar', false)->assertSee('尚未生成联动地址');
        $this->post($base.'/proxy-link', ['enabled' => '1', 'permission' => 'replay', 'action' => 'save'])->assertRedirect();
        $link = ProxyLink::query()->sole();
        $enabled = $this->get($base.'/edit?config_tab=proxy')->assertOk()
            ->assertSee($link->address())->assertSee('data-software-config-native', false)
            ->assertDontSee('data-element-config', false);
        $this->assertTrue($link->enabled);
        if ($directory = getenv('DFM_LINK_COMPAT_PREVIEW_DIR')) {
            file_put_contents($directory.'/disabled.html', $disabled->getContent());
            file_put_contents($directory.'/enabled.html', $enabled->getContent());
        }
    }

    public function test_wire_contract_and_optional_local_preview(): void
    {
        $this->administrator();
        $card = $this->card();
        $initial = $this->sync()->assertOk()->json();
        $card->forceFill(['user_status' => 'frozen'])->save();
        $frozen = $this->sync()->assertOk()->json();
        $card->forceFill(['user_status' => 'active', 'expires_at' => now()->addDays(3)])->save();
        $renewed = $this->sync()->assertOk()->json();
        $card->delete();
        $removed = $this->sync()->assertOk()->json();
        // Synthetic test data only. Allows the Python client to consume real Laravel responses.
        if ($output = getenv('DFM_LINK_CONTRACT_FILE')) {
            file_put_contents($output, json_encode(compact('initial', 'frozen', 'renewed', 'removed'), JSON_THROW_ON_ERROR));
        }
        if ($preview = getenv('DFM_LINK_PREVIEW_FILE')) {
            $html = $this->get('/proxy-admin/applications/'.$this->application->public_id.'/edit?config_tab=proxy')->assertOk()->getContent();
            file_put_contents($preview, $html);
        }
    }
}
