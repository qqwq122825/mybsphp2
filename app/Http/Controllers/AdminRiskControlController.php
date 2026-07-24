<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\IpRiskState;
use App\Models\RiskEvent;
use App\Risk\IpRiskControlService;
use App\Support\OperationalMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AdminRiskControlController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly AuditLogger $audit,
        private readonly IpRiskControlService $risk,
        private readonly OperationalMode $operationalMode,
    ) {}

    public function index(Request $request, string $adminPath): View
    {
        $applications = Application::query()->orderBy('name')->get(['id', 'public_id', 'code', 'name', 'mode']);
        $selected = $applications->firstWhere('public_id', (string) $request->query('application')) ?? $applications->first();
        $policy = $selected?->riskPolicy()->firstOrCreate([], $this->risk->defaults());

        $states = IpRiskState::query()->with('application')->whereIn('level', ['low', 'high']);
        $events = RiskEvent::query()->with('application');
        if ($selected instanceof Application) {
            $states->where('application_id', $selected->getKey());
            $events->where('application_id', $selected->getKey());
        } else {
            $states->whereRaw('1 = 0');
            $events->whereRaw('1 = 0');
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $states->where('ip', 'like', '%'.$keyword.'%');
        }
        $level = (string) $request->query('level', 'all');
        if (in_array($level, ['low', 'high'], true)) {
            $states->where('level', $level);
        }

        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        $base = IpRiskState::query()->when($selected, fn ($query) => $query->where('application_id', $selected->getKey()));
        $activeBlocks = (clone $base)->where(function ($query): void {
            $query->where('permanently_blocked', true)->orWhere('blocked_until', '>', now());
        })->count();

        return view('admin.risk-control.index', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $administrator,
            'databaseHealthy' => true,
            'applications' => $applications,
            'selectedApplication' => $selected,
            'policy' => $policy,
            'operationalMode' => $this->operationalMode->current(),
            'states' => $states->latest('last_seen_at')->paginate(20)->withQueryString(),
            'recentEvents' => $events->latest()->limit(12)->get(),
            'metrics' => [
                'events_24h' => $selected ? RiskEvent::query()->where('application_id', $selected->getKey())->where('created_at', '>=', now()->subDay())->count() : 0,
                'low' => (clone $base)->where('level', 'low')->count(),
                'high' => (clone $base)->where('level', 'high')->count(),
                'blocked' => $activeBlocks,
            ],
        ]);
    }

    public function updatePolicy(Request $request, string $adminPath, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'window_seconds' => ['required', 'integer', 'min:10', 'max:3600'],
            'low_threshold' => ['required', 'integer', 'min:2', 'max:1000'],
            'high_threshold' => ['required', 'integer', 'min:3', 'max:2000', 'gt:low_threshold'],
            'block_duration_minutes' => ['required', 'integer', 'min:1', 'max:43200'],
            'low_action' => ['required', Rule::in(['decoy', 'observe'])],
            'decoy_value' => ['nullable', 'string', 'max:255'],
            'block_http_status' => ['required', Rule::in([403, 429])],
            'block_code' => ['required', 'string', 'max:64', 'regex:/^[A-Z][A-Z0-9_]+$/'],
            'block_message' => ['required', 'string', 'max:255'],
        ]);
        $validated['enabled'] = $request->boolean('enabled');

        $application->riskPolicy()->updateOrCreate([], $validated);
        $this->recordAdminAction($request, 'risk.policy_updated', 'risk_policy', $application->public_id, $application->getKey(), $validated, '更新 IP 封控策略');

        return back()->with('success', '封控策略已保存并立即生效。');
    }

    public function stateAction(Request $request, string $adminPath, IpRiskState $state): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['reset', 'block_1h', 'block_24h', 'block_permanent', 'set_low'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        match ($validated['action']) {
            'reset' => $state->forceFill(['level' => 'normal', 'window_attempts' => 0, 'recent_attempt_timestamps' => [], 'window_started_at' => now(), 'blocked_until' => null, 'permanently_blocked' => false]),
            'block_1h' => $state->forceFill(['level' => 'high', 'blocked_until' => now()->addHour(), 'permanently_blocked' => false]),
            'block_24h' => $state->forceFill(['level' => 'high', 'blocked_until' => now()->addDay(), 'permanently_blocked' => false]),
            'block_permanent' => $state->forceFill(['level' => 'high', 'blocked_until' => null, 'permanently_blocked' => true]),
            'set_low' => $state->forceFill(['level' => 'low', 'blocked_until' => null, 'permanently_blocked' => false]),
        };
        if (array_key_exists('note', $validated)) {
            $state->note = $validated['note'];
        }
        $state->save();

        $this->recordAdminAction($request, 'risk.ip_state_updated', 'ip_risk_state', $state->public_id, $state->application_id, $validated, '人工调整 IP 封控状态');

        return back()->with('success', 'IP 状态已经更新。');
    }

    /** @param array<string, mixed> $after */
    private function recordAdminAction(Request $request, string $action, string $targetType, string $targetId, int $applicationId, array $after, string $reason): void
    {
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);
        $this->audit->record(new AuditEvent(
            action: $action,
            targetType: $targetType,
            targetId: $targetId,
            applicationId: $applicationId,
            after: $after,
            reason: $reason,
            actorRole: $administrator->role,
        ), $request);
    }
}
