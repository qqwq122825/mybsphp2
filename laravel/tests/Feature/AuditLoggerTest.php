<?php

namespace Tests\Feature;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use LogicException;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_writes_a_redacted_immutable_audit_record(): void
    {
        $request = Request::create('/admin/apps/9', 'PUT', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'MyBsPHP2 Test Browser',
        ]);
        $request->attributes->set('request_id', '01J00000000000000000000000');

        $audit = app(AuditLogger::class)->record(new AuditEvent(
            action: 'app.settings.update',
            targetType: 'application',
            targetId: '9',
            applicationId: 9,
            before: ['name' => 'Old', 'app_key' => 'old-secret'],
            after: [
                'name' => 'New',
                'nested' => ['secondary_password' => 'second-secret'],
            ],
            reason: 'configuration change token=reason-secret',
            actorRole: 'super_admin',
        ), $request);

        $before = $audit->getAttribute('before_data');
        $after = $audit->getAttribute('after_data');
        $this->assertIsArray($before);
        $this->assertIsArray($after);
        $nested = $after['nested'] ?? null;
        $this->assertIsArray($nested);

        $this->assertSame('01J00000000000000000000000', $audit->request_id);
        $this->assertSame('[REDACTED]', $before['app_key']);
        $this->assertSame('[REDACTED]', $nested['secondary_password']);
        $this->assertStringNotContainsString('reason-secret', $audit->reason);
        $this->assertSame(hash('sha256', 'MyBsPHP2 Test Browser'), $audit->user_agent_hash);
        $this->assertDatabaseHas('audit_logs', [
            'public_id' => $audit->public_id,
            'action' => 'app.settings.update',
            'target_type' => 'application',
            'target_id' => '9',
        ]);

        $this->expectException(LogicException::class);
        $audit->update(['result' => 'failed']);
    }

    public function test_it_rejects_deleting_an_audit_record(): void
    {
        $audit = AuditLog::query()->create([
            'public_id' => '01J00000000000000000000001',
            'action' => 'test.created',
            'target_type' => 'test',
            'request_id' => '01J00000000000000000000002',
            'result' => 'success',
        ]);

        $this->expectException(LogicException::class);
        $audit->delete();
    }
}
