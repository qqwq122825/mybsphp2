<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\CardType;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** 危险操作的配色与二次确认、敏感字段打码、手机端卡片布局标记。 */
class AdminUiSafetyTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();
        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/admin-ui-safety.lock');
        @unlink($this->lockPath);
        config(['mybsphp.installed' => false, 'mybsphp.install_lock' => $this->lockPath]);
        app(InstallationManager::class)->writeLock(['admin_path' => 'ui-console', 'secondary_password_enabled' => false]);
        $administrator = Administrator::query()->create([
            'username' => 'ui-admin', 'password' => 'primary-secret', 'role' => 'super_admin', 'status' => 'active',
        ]);
        $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
        $this->actingAs($administrator, 'admin');
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    public function test_card_list_marks_destructive_actions_and_renders_mobile_card_labels(): void
    {
        $html = $this->get('/ui-console/cards')->assertOk()->getContent();

        foreach (['unused', 'freeze', 'delete'] as $action) {
            $this->assertMatchesRegularExpression('/<button[^>]*admin-table-action--danger[^>]*value="'.$action.'"[^>]*return confirm\(/', $html);
        }
        $this->assertStringContainsString('table class="bsphp-card-table is-mobile-cards"', $html);
    }

    public function test_card_type_list_leads_with_duration_and_price_and_supports_mobile_cards(): void
    {
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(), 'code' => '901', 'name' => '界面测试', 'mode' => 'card_login',
            'entitlement_kind' => 'duration', 'status' => 'active', 'client_secret_hash' => 'x',
        ]);
        CardType::query()->create([
            'public_id' => (string) Str::ulid(), 'application_id' => $application->id, 'name' => '周卡', 'code' => 'week',
            'purpose' => 'login_credential', 'benefit_kind' => 'duration', 'duration_seconds' => 604800,
            'duration_value' => 7, 'duration_unit' => 'day', 'face_price_cents' => 100000, 'status' => 'active',
        ]);

        $this->get('/ui-console/applications/'.$application->public_id.'/card-types')->assertOk()
            ->assertSeeInOrder(['<th>卡串名称</th>', '<th>期限</th>', '<th>价格</th>', '<th>代理价格</th>', '<th>代号</th>'], false)
            ->assertSee('data-label="期限">7天', false)
            ->assertSee('admin-table-action--danger', false);
    }

    public function test_universal_card_code_is_masked_until_revealed(): void
    {
        $this->get('/ui-console/settings/system')->assertOk()
            ->assertSee('type="password" name="universal_card_code" data-secret-input', false)
            ->assertSee('data-secret-toggle', false);
    }
}
