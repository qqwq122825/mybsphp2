<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\AgentTransaction;
use App\Models\Application;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AdminAgentController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request, string $adminPath): View
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $query = Administrator::query()
            ->with(['roles', 'agentApplications', 'agentCardTypes'])
            ->where(fn ($query) => $query
                ->where('role', 'agent')
                ->orWhereHas('roles', fn ($roles) => $roles->where('code', 'agent')));

        if ($keyword !== '') {
            $query->where(fn ($query) => $query
                ->where('username', 'like', '%'.$keyword.'%')
                ->orWhere('contact_qq', 'like', '%'.$keyword.'%')
                ->orWhere('contact_email', 'like', '%'.$keyword.'%')
                ->orWhere('contact_phone', 'like', '%'.$keyword.'%'));
        }

        return view('admin.agents.index', array_merge($this->viewData($adminPath), [
            'agents' => $query
                ->orderBy('id', $request->query('sort') === 'oldest' ? 'asc' : 'desc')
                ->paginate(20)
                ->withQueryString(),
            'applications' => $this->applicationsForAssignment(),
        ]));
    }

    public function create(string $adminPath): View
    {
        return view('admin.agents.create', array_merge($this->viewData($adminPath), [
            'applications' => $this->applicationsForAssignment(),
        ]));
    }

    public function store(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z][A-Za-z0-9_-]+$/', 'unique:administrators,username'],
            'password' => ['required', 'string', 'min:6', 'max:128', 'confirmed'],
            'initial_balance' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            ...$this->profileRules(),
        ]);
        $initialBalance = (int) round(((float) ($validated['initial_balance'] ?? 0)) * 100);
        $profile = $this->profileValues($validated);

        $agent = DB::transaction(function () use ($validated, $initialBalance, $profile): Administrator {
            $agent = Administrator::query()->create([
                'username' => $validated['username'],
                'password' => $validated['password'],
                'secondary_password' => null,
                'secondary_password_required' => false,
                'role' => 'agent',
                'status' => 'active',
                'agent_balance_cents' => $initialBalance,
                'agent_level' => 1,
                ...$profile,
            ]);
            $agent->roles()->attach(
                Role::query()->where('code', 'agent')->sole(),
                ['assigned_by_administrator_id' => $this->actor()->getKey()],
            );
            $this->syncApplications($agent, $validated);
            if ($initialBalance > 0) {
                AgentTransaction::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'agent_id' => $agent->getKey(),
                    'type' => 'admin_recharge',
                    'amount_cents' => $initialBalance,
                    'balance_after_cents' => $initialBalance,
                    'note' => '创建代理账号时设置初始余额',
                ]);
            }

            return $agent;
        });

        $this->audit->record(new AuditEvent(
            action: 'agent.created',
            targetType: 'administrator',
            targetId: (string) $agent->getKey(),
            after: ['username' => $agent->username, 'role' => 'agent'],
            actorRole: $this->actor()->role,
        ), $request);

        return redirect()->route('admin.agents.index', ['adminPath' => $adminPath])
            ->with('success', '代理账号创建成功。');
    }

    public function update(Request $request, string $adminPath, Administrator $agent): RedirectResponse
    {
        $this->ensureAgent($agent);
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:32',
                'regex:/^[A-Za-z][A-Za-z0-9_-]+$/',
                Rule::unique('administrators', 'username')->ignore($agent->getKey()),
            ],
            'status' => ['required', Rule::in(['active', 'frozen'])],
            'password' => ['nullable', 'string', 'min:6', 'max:128', 'confirmed'],
            'balance' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            ...$this->profileRules(),
        ]);

        $before = ['username' => $agent->username, 'status' => $agent->status, 'balance_cents' => $agent->agent_balance_cents];
        $previousBalance = (int) $agent->agent_balance_cents;
        $agent->username = $validated['username'];
        $agent->status = $validated['status'];
        $agent->fill($this->profileValues($validated));
        if (filled($validated['password'] ?? null)) {
            $agent->password = $validated['password'];
        }
        if (filled($validated['balance'] ?? null)) {
            $agent->agent_balance_cents = (int) round(((float) $validated['balance']) * 100);
        }
        $agent->save();
        $this->syncApplications($agent, $validated);
        if ((int) $agent->agent_balance_cents !== $previousBalance) {
            AgentTransaction::query()->create([
                'public_id' => (string) Str::ulid(),
                'agent_id' => $agent->getKey(),
                'type' => 'admin_adjustment',
                'amount_cents' => (int) $agent->agent_balance_cents - $previousBalance,
                'balance_after_cents' => (int) $agent->agent_balance_cents,
                'note' => '平台管理员调整代理余额',
            ]);
        }

        $this->audit->record(new AuditEvent(
            action: 'agent.updated',
            targetType: 'administrator',
            targetId: (string) $agent->getKey(),
            before: $before,
            after: [
                'username' => $agent->username,
                'status' => $agent->status,
                'balance_cents' => $agent->agent_balance_cents,
                'purchase_discount_basis_points' => $agent->agent_discount_basis_points,
                'all_applications' => $agent->agent_all_applications,
                'password_changed' => filled($validated['password'] ?? null),
            ],
            actorRole: $this->actor()->role,
        ), $request);

        return back()->with('success', '代理账号已更新。');
    }

    public function destroy(Request $request, string $adminPath, Administrator $agent): RedirectResponse
    {
        $this->ensureAgent($agent);
        $targetId = (string) $agent->getKey();
        $before = ['username' => $agent->username, 'role' => 'agent'];
        $agent->delete();

        $this->audit->record(new AuditEvent(
            action: 'agent.deleted',
            targetType: 'administrator',
            targetId: $targetId,
            before: $before,
            actorRole: $this->actor()->role,
        ), $request);

        return redirect()->route('admin.agents.index', ['adminPath' => $adminPath])
            ->with('success', '代理账号已删除。');
    }

    private function viewData(string $adminPath): array
    {
        return [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $this->actor(),
            'databaseHealthy' => true,
        ];
    }

    private function actor(): Administrator
    {
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return $administrator;
    }

    private function ensureAgent(Administrator $agent): void
    {
        abort_unless($agent->role === 'agent' || $agent->hasRole('agent'), 404);
    }

    /** @return array<string, mixed> */
    private function profileRules(): array
    {
        return [
            'purchase_discount' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'agent_note' => ['nullable', 'string', 'max:500'],
            'contact_qq' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'all_applications' => ['nullable', 'boolean'],
            'application_ids' => ['nullable', 'array'],
            'application_ids.*' => ['integer', 'distinct', 'exists:applications,id'],
            'card_type_ids' => ['nullable', 'array'],
            'card_type_ids.*' => ['integer', 'distinct', 'exists:card_types,id'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    private function profileValues(array $validated): array
    {
        $discount = (float) ($validated['purchase_discount'] ?? 0);

        return [
            'agent_discount_basis_points' => $discount > 0 ? (int) round($discount * 1000) : 10000,
            'agent_note' => filled($validated['agent_note'] ?? null) ? trim((string) $validated['agent_note']) : null,
            'contact_qq' => filled($validated['contact_qq'] ?? null) ? trim((string) $validated['contact_qq']) : null,
            'contact_email' => filled($validated['contact_email'] ?? null) ? trim((string) $validated['contact_email']) : null,
            'contact_phone' => filled($validated['contact_phone'] ?? null) ? trim((string) $validated['contact_phone']) : null,
            'agent_all_applications' => (bool) ($validated['all_applications'] ?? true),
        ];
    }

    /** @param array<string, mixed> $validated */
    private function syncApplications(Administrator $agent, array $validated): void
    {
        if ($agent->agent_all_applications) {
            $agent->agentApplications()->sync([]);
            $agent->agentCardTypes()->sync([]);

            return;
        }

        $applicationIds = collect($validated['application_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique();
        $cardTypeIds = \App\Models\CardType::query()
            ->whereIn('application_id', $applicationIds)
            ->whereIn('id', $validated['card_type_ids'] ?? [])
            ->pluck('id');

        $agent->agentApplications()->sync($applicationIds->all());
        $agent->agentCardTypes()->sync($cardTypeIds->all());
    }

    private function applicationsForAssignment(): \Illuminate\Database\Eloquent\Collection
    {
        return Application::query()
            ->with(['cardTypes' => fn ($query) => $query->where('status', 'active')->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get();
    }
}
