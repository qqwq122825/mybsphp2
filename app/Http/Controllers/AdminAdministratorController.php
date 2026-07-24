<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AdminAdministratorController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request, string $adminPath): View
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $query = Administrator::query()
            ->with('roles')
            ->where('role', '!=', 'agent')
            ->whereDoesntHave('roles', fn ($roles) => $roles->where('code', 'agent'));
        if ($keyword !== '') {
            $query->where('username', 'like', '%'.$keyword.'%');
        }

        return view('admin.administrators.index', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $this->actor(),
            'databaseHealthy' => true,
            'administrators' => $query->orderBy('id', $request->query('sort') === 'oldest' ? 'asc' : 'desc')->paginate(20)->withQueryString(),
            'roles' => Role::query()
                ->where('code', 'admin')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function create(string $adminPath): View
    {
        return view('admin.administrators.create', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $this->actor(),
            'databaseHealthy' => true,
        ]);
    }

    public function store(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z][A-Za-z0-9_-]+$/', 'unique:administrators,username'],
            'password' => ['required', 'string', 'min:6', 'max:128', 'confirmed'],
            'secondary_password' => ['nullable', 'string', 'min:6', 'max:128', 'confirmed'],
        ]);

        $roleCode = 'admin';

        $administrator = Administrator::query()->create([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'secondary_password' => filled($validated['secondary_password'] ?? null)
                ? $validated['secondary_password']
                : null,
            'secondary_password_required' => filled($validated['secondary_password'] ?? null),
            'role' => $roleCode,
            'status' => 'active',
        ]);
        $administrator->roles()->attach(
            Role::query()->where('code', $roleCode)->sole(),
            ['assigned_by_administrator_id' => $this->actor()->getKey()],
        );

        $this->audit->record(new AuditEvent(
            action: 'administrator.created',
            targetType: 'administrator',
            targetId: (string) $administrator->getKey(),
            after: [
                'username' => $administrator->username,
                'role' => $roleCode,
                'secondary_password_required' => $administrator->secondary_password_required,
            ],
            actorRole: $this->actor()->role,
        ), $request);

        return redirect()->route('admin.administrators.index', ['adminPath' => $adminPath])
            ->with('success', '后台账号创建成功。');
    }

    public function update(
        Request $request,
        string $adminPath,
        Administrator $managedAdministrator,
    ): RedirectResponse {
        abort_if($managedAdministrator->is($this->actor()), 422, '当前登录账号请在个人安全设置中修改。');
        abort_if($this->isSystemAdministrator($managedAdministrator), 422, '系统管理员受保护，不允许修改角色或状态。');
        abort_if($managedAdministrator->role === 'agent' || $managedAdministrator->hasRole('agent'), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'frozen'])],
            'role_code' => ['required', Rule::in(['admin'])],
            'password' => ['nullable', 'string', 'min:6', 'max:128', 'confirmed'],
        ]);
        $before = [
            'status' => $managedAdministrator->status,
            'roles' => $managedAdministrator->roles()->pluck('code')->all(),
        ];

        $managedAdministrator->forceFill([
            'status' => $validated['status'],
            'role' => $validated['role_code'],
        ]);
        if (filled($validated['password'] ?? null)) {
            $managedAdministrator->password = $validated['password'];
        }
        $managedAdministrator->save();

        $role = Role::query()->where('code', $validated['role_code'])->sole();
        $managedAdministrator->roles()->sync([
            $role->getKey() => ['assigned_by_administrator_id' => $this->actor()->getKey()],
        ]);

        $this->audit->record(new AuditEvent(
            action: 'administrator.updated',
            targetType: 'administrator',
            targetId: (string) $managedAdministrator->getKey(),
            before: $before,
            after: [
                'status' => $managedAdministrator->status,
                'roles' => [$validated['role_code']],
                'password_changed' => filled($validated['password'] ?? null),
            ],
            actorRole: $this->actor()->role,
        ), $request);

        return back()->with('success', '后台账号已更新。');
    }

    public function destroy(Request $request, string $adminPath, Administrator $managedAdministrator): RedirectResponse
    {
        abort_if($managedAdministrator->is($this->actor()), 422, '当前登录账号不允许删除。');
        abort_if($this->isSystemAdministrator($managedAdministrator), 422, '系统管理员受保护，不允许删除。');
        abort_if($managedAdministrator->role === 'agent' || $managedAdministrator->hasRole('agent'), 404);

        $snapshot = ['username' => $managedAdministrator->username, 'role' => $managedAdministrator->role];
        $targetId = (string) $managedAdministrator->getKey();
        $managedAdministrator->delete();

        $this->audit->record(new AuditEvent(
            action: 'administrator.deleted',
            targetType: 'administrator',
            targetId: $targetId,
            before: $snapshot,
            actorRole: $this->actor()->role,
        ), $request);

        return redirect()->route('admin.administrators.index', ['adminPath' => $adminPath])
            ->with('success', '后台管理员已删除。');
    }

    public function updateSecurity(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:128'],
            'secondary_action' => ['required', Rule::in(['enable', 'disable'])],
            'secondary_password' => ['nullable', 'string', 'min:6', 'max:128', 'confirmed'],
        ]);
        $administrator = $this->actor();

        if (! Hash::check((string) $validated['current_password'], $administrator->password)) {
            throw ValidationException::withMessages([
                'current_password' => '当前登录密码不正确。',
            ]);
        }

        $enable = $validated['secondary_action'] === 'enable';
        $secondaryPassword = (string) ($validated['secondary_password'] ?? '');

        if ($enable && $secondaryPassword === '') {
            throw ValidationException::withMessages([
                'secondary_password' => '启用二级密码时需要填写并确认新二级密码。',
            ]);
        }

        if ($enable && hash_equals((string) $validated['current_password'], $secondaryPassword)) {
            throw ValidationException::withMessages([
                'secondary_password' => '登录密码与二级密码需要设置为不同内容。',
            ]);
        }

        $before = ['secondary_password_required' => $administrator->secondary_password_required];
        $administrator->forceFill([
            'secondary_password' => $enable ? $secondaryPassword : null,
            'secondary_password_required' => $enable,
        ])->save();

        $this->audit->record(new AuditEvent(
            action: 'administrator.security_updated',
            targetType: 'administrator',
            targetId: (string) $administrator->getKey(),
            before: $before,
            after: ['secondary_password_required' => $enable],
            actorRole: $administrator->role,
        ), $request);

        return back()->with(
            'success',
            $enable ? '当前账号二级密码已启用。' : '当前账号二级密码已关闭。',
        );
    }

    private function actor(): Administrator
    {
        $administrator = Auth::guard('admin')->user();

        abort_unless($administrator instanceof Administrator, 401);

        return $administrator;
    }

    private function isSystemAdministrator(Administrator $administrator): bool
    {
        return $administrator->role === 'super_admin'
            || $administrator->roles()->where('code', 'super_admin')->exists();
    }
}
