<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\ClientSecurityEvent;
use App\Models\ClientSecuritySubjectState;
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
            'securityEvents' => $selected ? ClientSecurityEvent::query()->where('application_id', $selected->getKey())->latest()->limit(20)->get() : collect(),
            'securityDeviceStates' => $selected ? ClientSecuritySubjectState::query()->where('application_id', $selected->getKey())->latest('last_event_at')->limit(20)->get() : collect(),
            'securityMetrics' => [
                'events_24h' => $selected ? ClientSecurityEvent::query()->where('application_id', $selected->getKey())->where('created_at', '>=', now()->subDay())->count() : 0,
                'high_24h' => $selected ? ClientSecurityEvent::query()->where('application_id', $selected->getKey())->where('severity', 'high')->where('created_at', '>=', now()->subDay())->count() : 0,
                'frozen_subjects' => $selected ? ClientSecurityEvent::query()->where('application_id', $selected->getKey())->where('action', 'freeze')->get(['card_id', 'application_account_id'])->unique(fn ($event) => $event->card_id ? 'c'.$event->card_id : 'a'.$event->application_account_id)->count() : 0,
            ],
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
            'restart_grace_seconds' => ['nullable', 'integer', 'min:0', 'max:15'],
            'security_report_mode' => ['nullable', Rule::in(['disabled', 'observe', 'enforce'])],
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
        $validated['restart_grace_seconds'] ??= (int) ($application->riskPolicy?->restart_grace_seconds ?? 4);
        $validated['security_report_mode'] ??= (string) ($application->riskPolicy?->security_report_mode ?? 'observe');

        $application->riskPolicy()->updateOrCreate([], $validated);
        $this->recordAdminAction($request, 'risk.policy_updated', 'risk_policy', $application->public_id, $application->getKey(), $validated, '更新 IP 封控策略');

        return back()->with('success', '封控策略已保存并立即生效。');
    }

    public function updateSecurityPolicy(Request $request, string $adminPath, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'security_report_mode' => ['required', Rule::in(['disabled', 'observe', 'enforce'])],
        ]);
        $application->riskPolicy()->updateOrCreate([], $validated);
        $this->recordAdminAction($request, 'risk.security_policy_updated', 'risk_policy', $application->public_id, $application->getKey(), $validated, '更新客户端蜜罐封控策略');

        return back()->with('success', '客户端蜜罐封控策略已保存。');
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

    public function unblockDevice(Request $request, string $adminPath, ClientSecuritySubjectState $deviceState): RedirectResponse
    {
        $deviceState->forceFill(['status' => 'observe', 'blocked_until' => null, 'risk_score' => 0])->save();
        $this->recordAdminAction($request, 'risk.device_unblocked', 'client_security_subject_state', (string) $deviceState->getKey(), $deviceState->application_id, ['status' => 'observe'], '解除客户端设备黑名单');

        return back()->with('success', '设备黑名单已解除；如需恢复原卡密或账号，请同时解冻授权主体。');
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
