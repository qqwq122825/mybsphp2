<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Cards\CardGenerationService;
use App\Cards\ManualIssueService;
use App\Install\SystemAccountResetter;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\CardType;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SystemAccountResetterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resets_system_accounts_while_preserving_applications_and_cards(): void
    {
        app(SystemAccessSynchronizer::class)->sync();

        $administrator = Administrator::query()->create([
            'username' => 'old-admin',
            'password' => 'primary-secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());
        SystemSetting::query()->create([
            'key' => 'app_name',
            'value' => '旧系统名称',
            'type' => 'string',
            'group' => 'site',
        ]);
        User::factory()->create();
        DB::table('audit_logs')->insert([
            'public_id' => (string) Str::ulid(),
            'action' => 'fixture.created',
            'target_type' => 'fixture',
            'request_id' => (string) Str::ulid(),
            'result' => 'success',
            'created_at' => now(),
        ]);

        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => 'preserved_app',
            'name' => '需要保留的应用',
            'mode' => 'card_login',
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'payload_mode' => 'constant',
            'payload_value_ciphertext' => 'A1B2C3D4E5',
            'client_secret_hash' => 'fixture-secret-hash',
            'bind_device_enabled' => true,
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 1,
            'created_by_administrator_id' => $administrator->getKey(),
        ]);
        $cardType = CardType::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'name' => '保留卡种',
            'code' => 'preserved_type',
            'purpose' => 'login_credential',
            'benefit_kind' => 'duration',
            'duration_seconds' => 86400,
            'face_price_cents' => 100,
            'code_prefix' => 'PS',
            'code_length' => 20,
            'secret_length' => 8,
            'status' => 'active',
            'max_activations' => 1,
        ]);
        app(CardGenerationService::class)->generate($cardType, 2, $administrator, '保留批次');
        app(ManualIssueService::class)->issueOffline(
            $cardType,
            1,
            '保留的线下客户',
            (string) Str::uuid(),
            '保留发卡记录',
            $administrator,
        );

        app(SystemAccountResetter::class)->reset();

        $this->assertDatabaseCount('administrators', 0);
        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('permissions', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseCount('system_settings', 0);
        $this->assertDatabaseCount('users', 0);

        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseCount('card_types', 1);
        $this->assertDatabaseCount('card_batches', 1);
        $this->assertDatabaseCount('cards', 2);
        $this->assertDatabaseCount('manual_issues', 1);
        $this->assertDatabaseCount('manual_issue_items', 1);
        $this->assertNull($application->fresh()->created_by_administrator_id);
        $this->assertNull(DB::table('card_batches')->value('created_by_administrator_id'));
        $this->assertNull(DB::table('manual_issues')->value('operator_administrator_id'));
    }
}
