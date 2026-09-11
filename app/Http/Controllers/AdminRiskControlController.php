<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\ClientSecurityEvent;
use App\Models\ClientSecuritySubjectState;
use App\Models\GlobalSecurityBlock;
use App\Models\IpRiskState;
use App\Models\RiskEvent;
use App\Risk\IpRiskControlService;
use App\Support\OperationalMode;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return view('admin.risk-control.index', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $administrator,
            'databaseHealthy' => true,
            'applications' => $applications,
            'selectedApplication' => $selected,
            'policy' => $policy,
            'operationalMode' => $this->operationalMode->current(),
        ]);
    }

    public function center(Request $request, string $adminPath): View
    {
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        $subjectType = (string) $request->query('subject_type', 'all');
        if (! in_array($subjectType, ['all', 'device', 'ip'], true)) {
            $subjectType = 'all';
        }
        $keyword = trim((string) $request->query('keyword', ''));
        $sort = (string) $request->query('sort', 'latest') === 'oldest' ? 'oldest' : 'latest';

        $rows = $this->centerBlockRows();
        if ($subjectType === 'device') {
            $rows = $rows->where('kind', 'device')->values();
        } elseif ($subjectType === 'ip') {
            $rows = $rows->filter(function (array $row): bool {
                return $row['kind'] === 'ip' || filled($row['related'] ?? null);
            })->values();
        }
        if ($keyword !== '') {
            $needle = mb_strtolower($keyword);
            $rows = $rows->filter(function (array $row) use ($needle): bool {
                return str_contains(mb_strtolower($row['value']), $needle)
                    || str_contains(mb_strtolower((string) $row['related']), $needle);
            })->values();
        }
        $rows = $sort === 'oldest'
            ? $rows->sortBy('timestamp')->values()
            : $rows->sortByDesc('timestamp')->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $blocks = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $blockedDeviceHashes = ClientSecuritySubjectState::query()
            ->where('status', 'blocked')
            ->where(fn ($query) => $query->whereNull('blocked_until')->orWhere('blocked_until', '>', now()))
            ->pluck('device_hash');
        $globalDeviceHashes = GlobalSecurityBlock::query()->where('subject_type', 'device')->pluck('subject_hash');

        return view('admin.risk-control.center', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $administrator,
            'databaseHealthy' => true,
            'blocks' => $blocks,
            'metrics' => [
                'events_24h' => RiskEvent::query()->where('created_at', '>=', now()->subDay())->count()
                    + ClientSecurityEvent::query()->where('created_at', '>=', now()->subDay())->count(),
                'blocked_devices' => $blockedDeviceHashes->merge($globalDeviceHashes)->unique()->count(),
            ],
        ]);
    }

    public function releaseSelected(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'block_keys' => ['required', 'array', 'min:1'],
            'block_keys.*' => ['required', 'string'],
        ]);
        foreach ($validated['block_keys'] as $key) {
            $this->releaseCenterRow((string) $key);
        }

        return back()->with('success', '已解除选定的封控。');
    }

    public function updatePolicy(Request $request, string $adminPath, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'window_seconds' => ['nullable', 'integer', 'min:10', 'max:3600'],
            'restart_grace_seconds' => ['nullable', 'integer', 'min:0', 'max:15'],
            'security_report_mode' => ['nullable', Rule::in(['disabled', 'observe', 'enforce'])],
            'low_threshold' => ['nullable', 'integer', 'min:2', 'max:1000'],
            'high_threshold' => ['nullable', 'integer', 'min:3', 'max:2000'],
            'block_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:43200'],
            'low_action' => ['nullable', Rule::in(['decoy', 'observe'])],
            'decoy_value' => ['nullable', 'string', 'max:255'],
            'block_http_status' => ['required', Rule::in([403, 429])],
            'block_code' => ['required', 'string', 'max:64', 'regex:/^[A-Z][A-Z0-9_]+$/'],
            'block_message' => ['required', 'string', 'max:255'],
            'invalid_card_threshold' => ['required', 'integer', 'min:2', 'max:100'],
            'invalid_card_window_seconds' => ['required', 'integer', 'min:10', 'max:86400'],
            'probe_count_missing_device' => ['nullable', 'boolean'],
            'probe_count_unknown_api' => ['nullable', 'boolean'],
        ]);
        $current = $application->riskPolicy;
        $validated['enabled'] = $request->boolean('enabled');
        $validated['probe_count_missing_device'] = $request->exists('probe_count_missing_device')
            ? $request->boolean('probe_count_missing_device')
            : (bool) ($current?->probe_count_missing_device ?? true);
        $validated['probe_count_unknown_api'] = $request->exists('probe_count_unknown_api')
            ? $request->boolean('probe_count_unknown_api')
            : (bool) ($current?->probe_count_unknown_api ?? true);
        $validated['window_seconds'] ??= (int) ($current?->window_seconds ?? 60);
        $validated['restart_grace_seconds'] ??= (int) ($current?->restart_grace_seconds ?? 8);
        $validated['security_report_mode'] ??= (string) ($current?->security_report_mode ?? 'observe');
        $validated['low_threshold'] ??= (int) ($current?->low_threshold ?? 6);
        $validated['high_threshold'] ??= (int) ($current?->high_threshold ?? 12);
        $validated['block_duration_minutes'] ??= (int) ($current?->block_duration_minutes ?? 30);
        $validated['low_action'] ??= (string) ($current?->low_action ?? 'observe');

        $application->riskPolicy()->updateOrCreate([], $validated);
        $this->recordAdminAction($request, 'risk.policy_updated', 'risk_policy', $application->public_id, $application->getKey(), $validated, '更新设备封控策略');

        return back()->with('success', '封控策略已保存并立即生效。')->withFragment('probe');
    }

    public function updateSecurityPolicy(Request $request, string $adminPath, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'security_report_mode' => ['required', Rule::in(['disabled', 'observe', 'enforce'])],
            'abnormal_exit_window_minutes' => ['required', 'integer', 'min:15', 'max:60'],
            'abnormal_exit_high_threshold' => ['required', 'integer', 'min:3', 'max:20'],
        ]);
        $application->riskPolicy()->updateOrCreate([], $validated);
        $this->recordAdminAction($request, 'risk.security_policy_updated', 'risk_policy', $application->public_id, $application->getKey(), $validated, '更新客户端蜜罐封控策略');

        return back()->with('success', '客户端蜜罐封控策略已保存。')->withFragment('honeypot');
    }

    public function stateAction(Request $request, string $adminPath, IpRiskState $state): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['reset', 'set_low'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        match ($validated['action']) {
            'reset' => $state->forceFill(['level' => 'normal', 'window_attempts' => 0, 'recent_attempt_timestamps' => [], 'window_started_at' => now(), 'blocked_until' => null, 'permanently_blocked' => false]),
            'set_low' => $state->forceFill(['level' => 'low', 'blocked_until' => null, 'permanently_blocked' => false]),
        };
        if (array_key_exists('note', $validated)) {
            $state->note = $validated['note'];
        }
        $state->save();
        if (in_array($validated['action'], ['reset', 'set_low'], true)) {
            GlobalSecurityBlock::query()
                ->where('subject_type', 'ip')
                ->where('subject_hash', $state->ip_hash)
                ->delete();
        }

        $this->recordAdminAction($request, 'risk.ip_state_updated', 'ip_risk_state', $state->public_id, $state->application_id, $validated, '人工调整 IP 封控状态');

        return back()->with('success', 'IP 状态已经更新。');
    }

    public function unblockDevice(Request $request, string $adminPath, ClientSecuritySubjectState $deviceState): RedirectResponse
    {
        $releaseScope = (string) $request->input('release_scope', 'all');
        abort_unless(in_array($releaseScope, ['device_only', 'device_credential', 'device_ip', 'all'], true), 422);
        $releaseCredential = in_array($releaseScope, ['device_credential', 'all'], true);
        $releaseIp = in_array($releaseScope, ['device_ip', 'all'], true);

        $result = DB::transaction(function () use ($deviceState, $releaseCredential, $releaseIp): array {
            $lockedState = ClientSecuritySubjectState::query()->lockForUpdate()->findOrFail($deviceState->getKey());
            $events = ClientSecurityEvent::query()
                ->where('application_id', $lockedState->application_id)
                ->where('device_hash', $lockedState->device_hash)
                ->lockForUpdate()
                ->get();

            $baselineEvents = 0;
            foreach ($events->where('event_code', 'T1') as $event) {
                $evidence = is_array($event->evidence) ? $event->evidence : [];
                if ((int) ($evidence['version'] ?? 0) !== 3) {
                    continue;
                }
                $evidence['pardon_revision'] = (int) ($evidence['revision'] ?? 0);
                $evidence['pardon_count'] = (int) ($evidence['count'] ?? 0);
                $evidence['pardon_short_runs'] = (int) ($evidence['short_runs'] ?? 0);
                $evidence['effective_count'] = 0;
                $evidence['effective_short_runs'] = 0;
                $evidence['effective_burst'] = 0;
                $evidence['pardoned_at'] = now()->toIso8601String();
                $event->forceFill(['evidence' => $evidence])->save();
                $baselineEvents++;
            }

            $cardIds = $events->pluck('card_id')->filter()->unique()->values();
            $accountIds = $events->pluck('application_account_id')->filter()->unique()->values();
            $sessionIds = $events->pluck('application_client_session_id')->filter()->unique()->values();
            $cardsReleased = ! $releaseCredential || $cardIds->isEmpty() ? 0 : DB::table('cards')
                ->whereIn('id', $cardIds)
                ->where('user_status', 'frozen')
                ->update(['user_status' => 'active', 'user_frozen_at' => null, 'updated_at' => now()]);
            $accountsReleased = ! $releaseCredential || $accountIds->isEmpty() ? 0 : DB::table('application_accounts')
                ->whereIn('id', $accountIds)
                ->where('status', 'frozen')
                ->update(['status' => 'active', 'updated_at' => now()]);
            $sessionsReleased = ! $releaseCredential || $sessionIds->isEmpty() ? 0 : DB::table('application_client_sessions')
                ->whereIn('id', $sessionIds)
                ->where('status', 'security_blocked')
                ->update(['status' => 'revoked', 'logged_out_at' => now(), 'updated_at' => now()]);
            $ipsReleased = $releaseIp
                ? IpRiskState::query()
                    ->where('application_id', $lockedState->application_id)
                    ->where('last_device_hash', $lockedState->device_hash)
                    ->update([
                        'level' => 'normal',
                        'window_attempts' => 0,
                        'recent_attempt_timestamps' => '[]',
                        'window_started_at' => now(),
                        'blocked_until' => null,
                        'permanently_blocked' => false,
                        'last_attempt_succeeded' => false,
                        'note' => '设备解除封控并建立新基线',
                        'updated_at' => now(),
                    ])
                : 0;

            $lockedState->forceFill([
                'status' => 'observe',
                'blocked_until' => null,
                'risk_score' => 0,
                'reason_code' => 'ADMIN_RESET',
            ])->save();
            GlobalSecurityBlock::query()
                ->where('subject_type', 'device')
                ->where('subject_hash', $lockedState->device_hash)
                ->delete();
            if ($releaseIp) {
                $ipHashes = IpRiskState::query()
                    ->where('last_device_hash', $lockedState->device_hash)
                    ->pluck('ip_hash');
                if ($ipHashes->isNotEmpty()) {
                    GlobalSecurityBlock::query()
                        ->where('subject_type', 'ip')
                        ->whereIn('subject_hash', $ipHashes)
                        ->delete();
                }
            }

            return compact('baselineEvents', 'cardsReleased', 'accountsReleased', 'sessionsReleased', 'ipsReleased');
        }, 3);

        $this->recordAdminAction(
            $request,
            'risk.device_unblocked',
            'client_security_subject_state',
            (string) $deviceState->getKey(),
            $deviceState->application_id,
            ['status' => 'observe', 'release_scope' => $releaseScope, ...$result],
            '解除客户端设备黑名单并建立永久总账新基线',
        );

        $message = match ($releaseScope) {
            'device_only' => '设备黑名单已解除；关联卡密/账号保持原状态。',
            'device_credential' => '设备黑名单及关联卡密/账号已恢复。',
            'device_ip' => '设备黑名单已解除；卡密/账号保持原状态。',
            default => '设备与安全冻结主体已经恢复；历史闪退总账保留，新异常将从当前基线重新计算。',
        };

        return back()->with('success', $message);
    }

    public function updateDeviceStatus(Request $request, string $adminPath, ClientSecuritySubjectState $deviceState): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['observe', 'quarantined', 'blocked'])],
            'release_scope' => ['nullable', Rule::in(['device_only', 'device_credential', 'device_ip', 'all'])],
        ]);

        if ($validated['status'] === 'blocked') {
            $deviceState->forceFill([
                'status' => 'blocked',
                'blocked_until' => null,
                'risk_score' => 100,
                'reason_code' => 'ADMIN_BLOCK',
                'last_event_at' => now(),
            ])->save();
            GlobalSecurityBlock::rememberDeviceHash($deviceState->device_hash, null, 'ADMIN_BLOCK', '设备已拉黑，全软件登录显示卡密不存在');
            $this->recordAdminAction(
                $request,
                'risk.device_blocked',
                'client_security_subject_state',
                (string) $deviceState->getKey(),
                $deviceState->application_id,
                ['status' => 'blocked'],
                '后台将设备处置等级改为拉黑',
            );

            return back()->with('success', '已将该设备拉黑。IP 仅作日志记录。');
        }

        if ($deviceState->status === 'blocked') {
            $request->merge(['release_scope' => $validated['release_scope'] ?? 'all']);
            $redirect = $this->unblockDevice($request, $adminPath, $deviceState);
            if ($validated['status'] === 'quarantined') {
                $deviceState->refresh()->forceFill(['status' => 'quarantined', 'reason_code' => 'ADMIN_WATCH'])->save();
            }
            GlobalSecurityBlock::query()
                ->where('subject_type', 'device')
                ->where('subject_hash', $deviceState->device_hash)
                ->delete();

            return $redirect;
        }

        $deviceState->forceFill([
            'status' => $validated['status'],
            'reason_code' => $validated['status'] === 'quarantined' ? 'ADMIN_WATCH' : 'ADMIN_RESET',
            'last_event_at' => now(),
        ])->save();
        $this->recordAdminAction(
            $request,
            'risk.device_status_updated',
            'client_security_subject_state',
            (string) $deviceState->getKey(),
            $deviceState->application_id,
            ['status' => $validated['status']],
            '后台调整设备处置等级',
        );

        return back()->with('success', $validated['status'] === 'quarantined' ? '已将该设备设为重点观察。' : '已将该设备设为观察。');
    }

    public function storeGlobalBlock(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'device_serial' => ['required', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', Rule::in(['manual', 'quick'])],
        ]);
        $deviceSerial = trim((string) ($validated['device_serial'] ?? ''));
        $source = ($validated['source'] ?? 'manual') === 'quick' ? 'quick' : 'manual';
        $reason = $source === 'quick' ? 'QUICK_BLOCK' : 'ADMIN_BLOCK';
        $note = $source === 'quick'
            ? (filled($validated['note'] ?? null) ? (string) $validated['note'] : '客户端请求日志快速拉黑')
            : ($validated['note'] ?? null);
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        $created = [];
        $deviceExisted = false;
        $deviceHash = $deviceSerial !== '' ? GlobalSecurityBlock::hashValue($deviceSerial) : '';
        if ($deviceSerial !== '') {
            $block = GlobalSecurityBlock::query()->firstOrCreate(
                ['subject_type' => 'device', 'subject_hash' => $deviceHash],
                [
                    'subject_value' => $deviceSerial,
                    'reason' => $reason,
                    'note' => $note,
                    'administrator_id' => $administrator->getKey(),
                ],
            );
            $deviceExisted = ! $block->wasRecentlyCreated;
            $created[] = 'device:'.$block->getKey();
            foreach (Application::query()->pluck('id') as $applicationId) {
                ClientSecuritySubjectState::query()->updateOrCreate(
                    ['application_id' => $applicationId, 'device_hash' => $deviceHash],
                    [
                        'status' => 'blocked',
                        'blocked_until' => null,
                        'risk_score' => 100,
                        'reason_code' => $source === 'quick' ? 'QUICK_BLOCK' : 'GLOBAL_BLOCK',
                        'last_event_at' => now(),
                    ],
                );
            }
        }
        $this->recordAdminAction(
            $request,
            'risk.global_block_created',
            'global_security_block',
            implode(',', $created),
            0,
            ['device_serial' => $deviceSerial, 'note' => $note, 'source' => $source],
            $source === 'quick' ? '客户端请求日志快速拉黑' : '后台主动添加全软件封禁',
        );

        return back()->with('success', $this->globalBlockSuccessMessage($deviceExisted, $source));
    }

    public function destroyGlobalBlock(Request $request, string $adminPath, GlobalSecurityBlock $globalBlock): RedirectResponse
    {
        $snapshot = $globalBlock->only(['subject_type', 'subject_hash', 'subject_value', 'reason']);
        if ($globalBlock->subject_type === 'device') {
            ClientSecuritySubjectState::query()
                ->where('device_hash', $globalBlock->subject_hash)
                ->update([
                    'status' => 'observe',
                    'blocked_until' => null,
                    'risk_score' => 0,
                    'reason_code' => 'ADMIN_RESET',
                    'updated_at' => now(),
                ]);
            $this->releaseLinkedIps((string) $globalBlock->subject_hash);
        }
        if ($globalBlock->subject_type === 'ip') {
            IpRiskState::query()
                ->where('ip_hash', $globalBlock->subject_hash)
                ->update([
                    'level' => 'normal',
                    'permanently_blocked' => false,
                    'blocked_until' => null,
                    'window_attempts' => 0,
                    'recent_card_probes' => '[]',
                    'note' => '解除 IP 封控',
                    'updated_at' => now(),
                ]);
        }
        $globalBlock->delete();
        $this->recordAdminAction(
            $request,
            'risk.global_block_removed',
            'global_security_block',
            $snapshot['subject_hash'],
            0,
            $snapshot,
            $snapshot['subject_type'] === 'device' ? '解除设备封控' : '解除 IP 封控',
        );

        return back()->with('success', $snapshot['subject_type'] === 'device'
            ? '已解除该设备封控。'
            : '已解除该 IP 封控。');
    }

    /**
     * @param  Collection<int, ClientSecuritySubjectState>  $deviceStates
     * @return array<string, array{identifier: string|null, credential_type: string|null, credential: string|null, ip: string|null}>
     */
    private function resolveSecurityDeviceDetails(Collection $deviceStates): array
    {
        $hashes = $deviceStates->pluck('device_hash')->filter()->unique()->values();
        if ($hashes->isEmpty()) {
            return [];
        }

        $details = [];
        $events = ClientSecurityEvent::query()
            ->with([
                'session:id,device_id_ciphertext,device_mask',
                'card:id,code_ciphertext,code_last4,bound_device_ciphertext,bound_device_mask',
                'account:id,username,bound_device_ciphertext,bound_device_mask',
            ])
            ->whereIn('device_hash', $hashes)
            ->latest('created_at')
            ->get();

        foreach ($events as $event) {
            $hash = (string) $event->device_hash;
            $detail = $details[$hash] ?? [
                'identifier' => null,
                'credential_type' => null,
                'credential' => null,
                'ip' => null,
            ];
            $candidates = [
                $event->session?->device_id_ciphertext,
                $event->card?->bound_device_ciphertext,
                $event->account?->bound_device_ciphertext,
            ];
            if ($detail['identifier'] === null) {
                foreach ($candidates as $candidate) {
                    $device = trim((string) $candidate);
                    if ($device !== '' && hash_equals($hash, hash('sha256', $device))) {
                        $detail['identifier'] = $device;
                        break;
                    }
                }
            }
            if ($detail['credential'] === null && $event->card !== null) {
                $detail['credential_type'] = '登录卡密';
                $detail['credential'] = trim((string) $event->card->code_ciphertext);
            } elseif ($detail['credential'] === null && $event->account !== null) {
                $detail['credential_type'] = '登录账号';
                $detail['credential'] = trim((string) $event->account->username);
            }
            $details[$hash] = $detail;
        }

        $ipStates = IpRiskState::query()
            ->whereIn('last_device_hash', $hashes)
            ->latest('last_seen_at')
            ->get(['application_id', 'ip', 'last_device_hash']);
        foreach ($ipStates as $ipState) {
            $hash = (string) $ipState->last_device_hash;
            if ($hash === '') {
                continue;
            }
            if (! isset($details[$hash])) {
                $details[$hash] = [
                    'identifier' => null,
                    'credential_type' => null,
                    'credential' => null,
                    'ip' => null,
                ];
            }
            if ($details[$hash]['ip'] === null) {
                $details[$hash]['ip'] = (string) $ipState->ip;
            }
        }

        return $details;
    }

    /**
     * @return Collection<int, array{key:string,kind:string,value:string,related:?string,reason:string,at:?CarbonInterface,timestamp:int}>
     */
    private function centerBlockRows(): Collection
    {
        $globalDevices = GlobalSecurityBlock::query()->where('subject_type', 'device')->latest()->get();
        $globalDeviceHashes = $globalDevices->pluck('subject_hash');

        $blockedDevices = ClientSecuritySubjectState::query()
            ->where('status', 'blocked')
            ->where(fn ($query) => $query->whereNull('blocked_until')->orWhere('blocked_until', '>', now()))
            ->latest('last_event_at')
            ->get()
            ->unique('device_hash')
            ->values();
        $details = $this->resolveSecurityDeviceDetails($blockedDevices);

        // Associated addresses are diagnostic context, never additional block subjects.
        $deviceHashes = $globalDeviceHashes->merge($blockedDevices->pluck('device_hash'))->unique();
        $ipsByDevice = IpRiskState::query()->whereIn('last_device_hash', $deviceHashes)
            ->latest('last_seen_at')->get(['last_device_hash', 'ip'])->groupBy('last_device_hash');
        $rows = collect();

        foreach ($globalDevices as $block) {
            $linked = $ipsByDevice->get((string) $block->subject_hash, collect());
            $at = $block->updated_at ?? $block->created_at;
            $reason = $this->blockReasonLabel((string) $block->reason, $block->note);
            $rows->push([
                'key' => 'global:'.$block->getKey(),
                'kind' => 'device',
                'value' => (string) $block->subject_value,
                'related' => $linked->pluck('ip')->filter()->unique()->implode('、') ?: null,
                'reason' => $reason,
                'at' => $at,
                'timestamp' => $at?->getTimestamp() ?? 0,
            ]);
        }

        foreach ($blockedDevices as $deviceState) {
            if ($globalDeviceHashes->contains($deviceState->device_hash)) {
                continue;
            }
            $linked = $ipsByDevice->get((string) $deviceState->device_hash, collect());
            $detail = $details[$deviceState->device_hash] ?? [];
            $identifier = $detail['identifier'] ?? null;
            $hashSummary = substr($deviceState->device_hash, 0, 12).'…'.substr($deviceState->device_hash, -8);
            $at = $deviceState->last_event_at ?? $deviceState->updated_at;
            $reason = $this->blockReasonLabel((string) $deviceState->reason_code, null);
            $rows->push([
                'key' => 'device:'.$deviceState->getKey(),
                'kind' => 'device',
                'value' => $identifier ?: $hashSummary,
                'related' => $linked->pluck('ip')->filter()->unique()->implode('、') ?: null,
                'reason' => $reason,
                'at' => $at,
                'timestamp' => $at?->getTimestamp() ?? 0,
            ]);
        }

        return $rows->values();
    }

    private function isGenericBlockNote(?string $note): bool
    {
        $note = trim((string) $note);
        if ($note === '') {
            return true;
        }

        return str_starts_with($note, '设备已拉黑')
            || str_starts_with($note, 'IP 已拉黑')
            || str_starts_with($note, '随设备拉黑');
    }

    private function compactBlockNote(?string $note): ?string
    {
        $note = trim((string) $note);
        if ($this->isGenericBlockNote($note)) {
            return null;
        }
        $note = preg_replace('/[。．]?后续任意软件登录显示卡密不存在。?/u', '', $note) ?? $note;
        $note = preg_replace('/[，,]?已拉黑(?:当前)?设备。?/u', '', $note) ?? $note;
        $note = trim($note, " \t。；;");

        return $note === '' ? null : $note;
    }

    private function blockReasonCategory(string $reason, string $note): ?string
    {
        return match (true) {
            $reason === 'QUICK_BLOCK' || str_contains($note, '快速拉黑') => '快速拉黑',
            $reason === 'C0' || str_contains($note, '套卡探测') => '套卡探测',
            $reason === 'T1' || str_contains($note, '异常退出') => '蜜罐 · 异常退出',
            $reason === 'S0' || str_contains($note, '日签') => '蜜罐 · 日签失败',
            $reason === 'R0' || str_contains($note, '非法能力') => '蜜罐 · 非法能力',
            $reason === 'IH' || str_contains($note, '登录 ih') => '蜜罐 · 登录 ih',
            $reason === 'CRC' || str_contains($note, 'CRC 失配') || str_contains($note, 'CRC不一致') => '蜜罐 · CRC 失配',
            $reason === 'D0' || str_contains($note, '诱饵') => '蜜罐 · 诱饵确认',
            $reason === 'D1' || str_contains($note, '真槽') || str_contains($note, '完整性') => '蜜罐 · 真槽完整性',
            str_contains($note, '客户端安全')
                || str_contains($note, 'security.report')
                || str_contains($note, '蜜罐') => '蜜罐事件',
            in_array($reason, ['ADMIN_BLOCK', 'GLOBAL_BLOCK'], true) => '手动添加',
            default => null,
        };
    }

    private function releaseLinkedIps(string $deviceHash): void
    {
        $deviceHash = trim($deviceHash);
        if ($deviceHash === '') {
            return;
        }
        $ipHashes = IpRiskState::query()
            ->where('last_device_hash', $deviceHash)
            ->pluck('ip_hash')
            ->filter()
            ->unique()
            ->values();
        if ($ipHashes->isEmpty()) {
            return;
        }
        IpRiskState::query()
            ->whereIn('ip_hash', $ipHashes)
            ->where(function ($query): void {
                $query->where('permanently_blocked', true)->orWhere('blocked_until', '>', now());
            })
            ->update([
                'level' => 'normal',
                'permanently_blocked' => false,
                'blocked_until' => null,
                'window_attempts' => 0,
                'recent_card_probes' => '[]',
                'note' => '随设备解除封控',
                'updated_at' => now(),
            ]);
        GlobalSecurityBlock::query()
            ->where('subject_type', 'ip')
            ->whereIn('subject_hash', $ipHashes)
            ->delete();
    }

    private function releaseCenterRow(string $key): void
    {
        [$source, $id] = array_pad(explode(':', $key, 2), 2, '');
        if ($source === 'global') {
            $block = GlobalSecurityBlock::query()->find($id);
            if ($block instanceof GlobalSecurityBlock) {
                $this->destroyGlobalBlock(request(), (string) request()->route('adminPath'), $block);
            }

            return;
        }
        if ($source === 'device') {
            $deviceState = ClientSecuritySubjectState::query()->find($id);
            if ($deviceState instanceof ClientSecuritySubjectState) {
                $request = request();
                $request->merge(['status' => 'observe', 'release_scope' => 'device_ip']);
                $this->updateDeviceStatus($request, (string) $request->route('adminPath'), $deviceState);
                $this->releaseLinkedIps((string) $deviceState->device_hash);
            }

            return;
        }
        if ($source === 'ip') {
            $state = IpRiskState::query()->find($id);
            if ($state instanceof IpRiskState) {
                $request = request();
                $request->merge(['action' => 'reset']);
                $this->stateAction($request, (string) $request->route('adminPath'), $state);
            }
        }
    }

    private function blockReasonLabel(string $reason, ?string $note): string
    {
        $note = trim((string) $note);
        $category = $this->blockReasonCategory($reason, $note);
        $detail = $this->compactBlockNote($note);
        if ($category === null) {
            if ($detail !== null) {
                return $detail;
            }

            return in_array($reason, ['high', 'normal', 'medium', ''], true) ? '封控' : $reason;
        }
        if ($category === '手动添加' && $detail !== null && $detail !== $category) {
            return $detail;
        }
        if ($detail !== null && $detail !== $category && (str_contains($detail, '达到') || str_contains($detail, '槽') || str_contains($detail, 'ih'))) {
            return str_contains($detail, $category) || str_starts_with($detail, '套卡探测') || str_starts_with($detail, '蜜罐')
                ? $detail
                : $category.'：'.$detail;
        }

        return $category;
    }

    private function globalBlockSuccessMessage(bool $deviceExisted, string $source): string
    {
        if ($source === 'quick') {
            return $deviceExisted
                ? '设备已在黑名单中。'
                : '已快速拉黑该设备，任意软件登录将显示卡密不存在。';
        }

        return '已拉黑，任意软件登录将显示卡密不存在。';
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
