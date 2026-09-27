<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Cards\CardCredentialHasher;
use App\Cards\CardOperationLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class CardOperationLogTest extends TestCase
{
    use RefreshDatabase;

    private Administrator $admin;

    private Administrator $agent;

    private CardType $type;

    private CardBatch $batch;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();
        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/card-operation-log.lock');
        config(['mybsphp.install_lock' => $this->lockPath]);
        app(InstallationManager::class)->writeLock(['admin_path' => 'private-console', 'agent_path' => 'agent', 'app_name' => '操作日志测试', 'secondary_password_enabled' => false]);
        $this->admin = $this->actor('audit-admin', 'super_admin');
        $this->agent = $this->actor('sq0001', 'agent');
        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(), 'code' => '1002', 'name' => '测试软件',
            'mode' => 'card_login', 'entitlement_kind' => 'duration', 'status' => 'active',
            'client_secret_hash' => hash('sha256', 'fixture'),
        ]);
        $this->type = CardType::query()->create([
            'public_id' => (string) Str::ulid(), 'application_id' => $application->id,
            'name' => '周卡', 'code' => 'week', 'purpose' => 'login_credential', 'benefit_kind' => 'duration',
            'duration_seconds' => 604800, 'duration_value' => 7, 'duration_unit' => 'day',
            'status' => 'active', 'secret_length' => 0,
        ]);
        $this->batch = CardBatch::query()->create([
            'public_id' => (string) Str::ulid(), 'card_type_id' => $this->type->id,
            'batch_no' => 'AUDIT-BATCH', 'source' => 'generated', 'status' => 'ready',
            'planned_quantity' => 500, 'actual_quantity' => 500,
            'created_by_administrator_id' => $this->agent->id, 'maker_reference' => $this->agent->username,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    public function test_agent_freeze_and_unfreeze_have_before_after_and_correct_dual_session_actor(): void
    {
        $card = $this->card();
        $this->actingAs($this->admin, 'admin')->actingAs($this->agent, 'agent');
        $this->post('/agent/cards/'.$card->id.'/action', ['action' => 'freeze'])->assertRedirect();
        $freeze = $this->operations($card)->latest('id')->firstOrFail();
        $this->assertSame('sq0001', data_get($freeze->after_data, 'actor_username'));
        $this->assertSame($this->agent->id, $freeze->actor_user_id);
        $this->assertSame('agent', $freeze->actor_role_snapshot);
        $this->assertFalse(data_get($freeze->before_data, 'state.card_is_frozen'));
        $this->assertTrue(data_get($freeze->after_data, 'state.card_is_frozen'));
        $this->post('/agent/cards/'.$card->id.'/action', ['action' => 'unfreeze'])->assertRedirect();
        $unfreeze = $this->operations($card)->latest('id')->firstOrFail();
        $this->assertTrue(data_get($unfreeze->before_data, 'state.card_is_frozen'));
        $this->assertFalse(data_get($unfreeze->after_data, 'state.card_is_frozen'));
        $this->assertSame('解冻卡密', data_get($unfreeze->after_data, 'operation'));
        $response = $this->get('/private-console/system-logs/card-operations?keyword=AuditCard001')->assertOk()
            ->assertSee('sq0001')->assertSee('冻结卡密')->assertSee('解冻卡密')->assertSee('卡冻结：是')->assertSee('卡冻结：否');
        $this->assertSame(3, $response->viewData('logs')->total()); // create + freeze + unfreeze, no legacy duplicates
        $this->get('/private-console/cards')->assertOk()->assertSee('查看日志');
    }

    public function test_admin_bulk_and_filter_maintenance_log_every_selected_card(): void
    {
        $first = $this->card();
        $second = $this->card('AuditCard002');
        $unselected = $this->card('AuditCard003');
        $this->actingAs($this->admin, 'admin');
        $this->post('/private-console/cards/bulk-action', ['card_ids' => [$first->id, $second->id], 'action' => 'freeze'])->assertRedirect();
        foreach ([$first, $second] as $card) {
            $log = $this->operations($card)->latest('id')->firstOrFail();
            $this->assertSame($this->admin->id, $log->actor_user_id);
            $this->assertSame('冻结卡密', data_get($log->after_data, 'operation'));
        }
        $this->assertFalse($unselected->fresh()->card_is_frozen);
        $this->post('/private-console/cards/maintenance', [
            'selection_mode' => 'filter', 'scope' => 'frozen', 'action' => 'unfreeze', 'confirmed' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        foreach ([$first, $second] as $card) {
            $this->assertFalse($card->fresh()->card_is_frozen);
            $this->assertSame('解冻卡密', data_get($this->operations($card)->latest('id')->firstOrFail()->after_data, 'operation'));
        }
    }

    public function test_batch_plugin_and_user_freeze_are_logged_as_distinct_operations(): void
    {
        $card = $this->card();
        $this->actingAs($this->admin, 'admin');
        $this->post('/private-console/operation-plugins/batch-freeze', ['codes' => 'AuditCard001'])->assertOk();
        $this->assertSame('冻结卡密', data_get($this->operations($card)->latest('id')->first()->after_data, 'operation'));
        $this->post('/private-console/operation-plugins/batch-freeze', ['codes' => 'AuditCard001'])->assertOk();
        $this->assertSame('冻结（状态未变化）', data_get($this->operations($card)->latest('id')->first()->after_data, 'operation'));
        $this->post('/private-console/operation-plugins/batch-unfreeze', ['codes' => 'AuditCard001'])->assertOk();
        $card->refresh()->forceFill(['activated_at' => now(), 'status' => 'active', 'user_status' => 'active'])->save();
        $this->post('/private-console/applications/'.$this->type->application->public_id.'/users/bulk-action', ['user_ids' => [$card->id], 'action' => 'freeze'])->assertRedirect();
        $log = $this->operations($card)->latest('id')->first();
        $this->assertSame('冻结软件用户', data_get($log->after_data, 'operation'));
        $this->assertFalse(data_get($log->after_data, 'state.card_is_frozen'));
    }

    public function test_deleted_card_remains_searchable_by_encrypted_identity_and_actor_snapshot(): void
    {
        $card = $this->card();
        $this->actingAs($this->admin, 'admin')->actingAs($this->agent, 'agent');
        $this->post('/agent/cards/'.$card->id.'/action', ['action' => 'freeze'])->assertRedirect();
        $this->agent->update(['username' => 'renamed-agent']);
        CardOperationLogger::deleteCards(Card::query()->whereKey($card->id));
        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
        $this->get('/private-console/system-logs/card-operations?keyword=audit-card-001')->assertOk()
            ->assertSee('AuditCard001')->assertSee('sq0001')->assertSee('永久删除卡密');
        $this->get('/private-console/system-logs/card-operations?search_field=card_id&keyword='.$card->id)->assertOk()->assertSee('AuditCard001');
        $this->get('/private-console/system-logs/card-operations?search_field=actor&keyword=sq0001')->assertOk()->assertSee('冻结卡密');
        foreach ($this->operations($card)->get() as $log) {
            $this->assertStringNotContainsString('AuditCard001', json_encode($log->getRawOriginal()));
        }
    }

    public function test_historical_agent_entries_are_visible_without_inventing_snapshots(): void
    {
        $card = $this->card();
        AuditLog::query()->create([
            'public_id' => (string) Str::ulid(), 'action' => 'agent.card_freeze', 'target_type' => 'card',
            'target_id' => $card->public_id, 'request_id' => (string) Str::ulid(),
            'actor_user_id' => $this->agent->id, 'actor_role_snapshot' => 'agent', 'result' => 'success',
        ]);
        $this->actingAs($this->admin, 'admin')->get('/private-console/system-logs/card-operations?keyword=AuditCard001')
            ->assertOk()->assertSee('sq0001')->assertSee('冻结卡密')->assertSee('历史记录未保存前后快照');
    }

    public function test_unrelated_agent_and_no_audit_permission_have_no_access_or_side_effects(): void
    {
        $card = $this->card();
        $stranger = $this->actor('unrelated-agent', 'agent');
        $count = $this->operations($card)->count();
        $this->actingAs($stranger, 'agent')->post('/agent/cards/'.$card->id.'/action', ['action' => 'freeze'])->assertNotFound();
        $this->assertSame($count, $this->operations($card)->count());
        $this->actingAs($stranger, 'admin')->get('/private-console/system-logs/card-operations')->assertForbidden();
        $this->assertFalse($card->fresh()->card_is_frozen);
    }

    public function test_log_failure_rolls_back_the_card_mutation(): void
    {
        $card = $this->card();
        $count = $this->operations($card)->count();
        $event = 'eloquent.creating: '.AuditLog::class;
        Event::listen($event, function (AuditLog $log): void {
            if ($log->action === 'card.operation') {
                throw new RuntimeException('audit storage failed');
            }
        });
        try {
            $card->forceFill(['card_is_frozen' => true])->save();
            $this->fail('Expected audit failure');
        } catch (RuntimeException $e) {
            $this->assertSame('audit storage failed', $e->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertFalse($card->fresh()->card_is_frozen);
        $this->assertSame($count, $this->operations($card)->count());
    }

    public function test_outer_rollback_removes_audit_and_heartbeat_does_not_produce_noise(): void
    {
        $card = $this->card();
        $count = $this->operations($card)->count();
        $card->forceFill(['last_online_at' => now(), 'lock_version' => 3])->save();
        $card->save();
        $this->assertSame($count, $this->operations($card)->count());
        DB::beginTransaction();
        $card->forceFill(['card_is_frozen' => true])->save();
        $this->assertSame($count + 1, $this->operations($card)->count());
        DB::rollBack();
        $this->assertSame($count, $this->operations($card)->count());
        $this->assertFalse($card->fresh()->card_is_frozen);
    }

    public function test_stale_model_audits_actual_persisted_before_state(): void
    {
        $card = $this->card();
        $stale = $card->fresh();
        $card->forceFill(['card_is_frozen' => true])->save();
        $stale->forceFill(['user_note' => '客服备注'])->save();
        $log = $this->operations($card)->latest('id')->first();
        $this->assertTrue(data_get($log->before_data, 'state.card_is_frozen'));
        $this->assertTrue(data_get($log->after_data, 'state.card_is_frozen'));
        $this->assertSame('修改备注', data_get($log->after_data, 'operation'));
    }

    public function test_client_changes_do_not_inherit_a_concurrently_signed_in_admin(): void
    {
        $card = $this->card();
        Route::post('/__testing/card-audit', function () use ($card) {
            $card->forceFill(['user_status' => 'frozen'])->save();

            return response()->noContent();
        })->name('test.client-card-change');
        $this->actingAs($this->admin, 'admin')->post('/__testing/card-audit')->assertNoContent();
        $log = $this->operations($card)->latest('id')->first();
        $this->assertNull($log->actor_user_id);
        $this->assertSame('client', $log->actor_role_snapshot);
    }

    public function test_repeated_freeze_is_retained_and_card_history_has_no_cleanup_endpoint(): void
    {
        $card = $this->card();
        $this->actingAs($this->agent, 'agent');
        $this->post('/agent/cards/'.$card->id.'/action', ['action' => 'freeze'])->assertRedirect();
        $this->post('/agent/cards/'.$card->id.'/action', ['action' => 'freeze'])->assertRedirect();
        $log = $this->operations($card)->latest('id')->first();
        $this->assertSame('冻结（状态未变化）', data_get($log->after_data, 'operation'));
        $this->assertSame(data_get($log->before_data, 'state'), data_get($log->after_data, 'state'));
        $this->actingAs($this->admin, 'admin')->post('/private-console/system-logs/card-operations/maintenance', ['action' => 'clear'])->assertNotFound();
        $this->get('/private-console/system-logs/card-operations')->assertOk()->assertDontSee('清空记录')->assertDontSee('删除选定');
    }

    public function test_chunked_updates_keep_every_card_when_the_filter_changes(): void
    {
        for ($i = 0; $i < 203; $i++) {
            $this->card('ChunkAudit'.$i);
        }
        $count = CardOperationLogger::updateCards(Card::query()->where('card_is_frozen', false), ['card_is_frozen' => true]);
        $this->assertSame(203, $count);
        $this->assertSame(203, Card::query()->where('card_is_frozen', true)->count());
        $this->assertSame(203, AuditLog::query()->where('action', 'card.operation')->where('after_data->operation', '冻结卡密')->count());
    }

    public function test_card_type_maintenance_logs_effective_price_changes_even_for_unused_cards(): void
    {
        $card = $this->card();
        $sameType = $this->card('AuditSameType002');
        $originalPrice = $this->type->fresh()->face_price_cents;
        $this->actingAs($this->admin, 'admin')->post('/private-console/cards/maintenance', [
            'selection_mode' => 'ids', 'id_list' => (string) $card->id, 'scope' => 'all',
            'action' => 'set_sale_price', 'action_value' => '12.50', 'confirmed' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        foreach ([$card, $sameType] as $affected) {
            $log = $this->operations($affected)->latest('id')->first();
            $this->assertSame(1250, data_get($log->after_data, 'state.type_face_price_cents'));
            $this->assertSame($originalPrice, data_get($log->before_data, 'state.type_face_price_cents'));
        }
    }

    private function actor(string $username, string $role): Administrator
    {
        $actor = Administrator::query()->create(['username' => $username, 'password' => 'fixture-secret', 'role' => $role, 'status' => 'active']);
        $actor->roles()->attach(Role::query()->where('code', $role)->sole());

        return $actor;
    }

    private function card(string $code = 'AuditCard001'): Card
    {
        return Card::query()->create([
            'public_id' => (string) Str::ulid(), 'application_id' => $this->type->application_id,
            'card_type_id' => $this->type->id, 'batch_id' => $this->batch->id,
            'code_lookup_hash' => app(CardCredentialHasher::class)->lookupHash($code),
            'code_ciphertext' => $code, 'code_last4' => substr($code, -4), 'status' => 'issued', 'card_is_frozen' => false,
        ]);
    }

    private function operations(Card $card)
    {
        return AuditLog::query()->where('action', 'card.operation')->where('target_id', $card->public_id);
    }
}
