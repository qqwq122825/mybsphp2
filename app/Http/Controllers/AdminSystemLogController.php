<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AdminSystemLogController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request, string $adminPath, string $type): View
    {
        validator(['type' => $type], ['type' => ['required', Rule::in(['login', 'card-making', 'protection', 'client-requests'])]])->validate();

        $keyword = trim((string) $request->query('keyword', ''));
        $sort = $request->query('sort') === 'oldest' ? 'asc' : 'desc';
        $query = $this->queryForType($type)->with('actorAdministrator');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('action', 'like', '%'.$keyword.'%')
                    ->orWhere('target_id', 'like', '%'.$keyword.'%')
                    ->orWhere('actor_role_snapshot', 'like', '%'.$keyword.'%')
                    ->orWhere('ip', 'like', '%'.$keyword.'%')
                    ->orWhere('reason', 'like', '%'.$keyword.'%')
                    ->orWhere('before_data', 'like', '%'.$keyword.'%')
                    ->orWhere('after_data', 'like', '%'.$keyword.'%');
            });
        }

        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return view('admin.logs.index', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $administrator,
            'databaseHealthy' => true,
            'logType' => $type,
            'title' => match ($type) {
                'login' => '管理登录日志',
                'card-making' => '代理制卡日志',
                'protection' => '系统安全防护日志',
                'client-requests' => '客户端请求日志',
            },
            'logs' => $query->orderBy('id', $sort)->paginate(30)->withQueryString(),
        ]);
    }

    public function diagnostics(Request $request, string $adminPath): View
    {
        $validated = $request->validate([
            'reference' => ['nullable', 'integer', 'min:201', 'max:999'],
        ]);
        $reference = isset($validated['reference']) ? (int) $validated['reference'] : null;
        $logs = collect();
        if ($reference !== null) {
            $logs = AuditLog::query()
                ->where('action', 'client.auth_failure_masked')
                ->where('after_data->diagnostic_reference', $reference)
                ->latest('id')
                ->limit(20)
                ->get();
        }

        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return view('admin.logs.diagnostics', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $administrator,
            'databaseHealthy' => true,
            'reference' => $reference,
            'logs' => $logs,
        ]);
    }

    public function maintenance(Request $request, string $adminPath, string $type): RedirectResponse
    {
        validator(['type' => $type], ['type' => ['required', Rule::in(['login', 'card-making', 'protection', 'client-requests'])]])->validate();
        $validated = $request->validate([
            'action' => ['required', Rule::in(['delete_selected', 'delete_older_7', 'delete_older_50', 'clear'])],
            'log_ids' => ['nullable', 'array'],
            'log_ids.*' => ['integer', 'min:1'],
        ]);

        $query = $this->queryForType($type);
        match ($validated['action']) {
            'delete_selected' => $query->whereIn('id', array_values(array_unique($validated['log_ids'] ?? []))),
            'delete_older_7' => $query->where('created_at', '<', now()->subDays(7)),
            'delete_older_50' => $query->where('created_at', '<', now()->subDays(50)),
            'clear' => null,
        };

        $deleted = $query->toBase()->delete();
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);
        $this->audit->record(new AuditEvent(
            action: 'system_logs.maintained',
            targetType: 'audit_log',
            targetId: $type,
            actorRole: $administrator->role,
            after: ['maintenance_action' => $validated['action'], 'deleted_count' => $deleted],
            reason: '后台系统日志维护',
        ), $request);

        return back()->with('status', "日志维护完成，共处理 {$deleted} 条记录。");
    }

    private function queryForType(string $type): Builder
    {
        $query = AuditLog::query();

        return match ($type) {
            'login' => $query->whereIn('action', ['admin.login', 'admin.login_failed', 'admin.logout']),
            'card-making' => $query->where(function ($builder): void {
                $builder->where('action', 'card_batch.generated')
                    ->orWhere('action', 'like', 'cards.%')
                    ->orWhere('target_type', 'card_batch');
            }),
            'protection' => $query->where(function ($builder): void {
                $builder->where('result', '!=', 'success')
                    ->orWhereIn('action', ['admin.login_failed', 'security.blocked', 'security.rate_limited']);
            }),
            'client-requests' => $query->where('action', 'client.card_login_trace'),
        };
    }
}
