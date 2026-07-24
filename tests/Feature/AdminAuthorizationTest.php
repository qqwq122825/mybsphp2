<?php

namespace Tests\Feature;

use App\Auth\AdminPermission;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lockPath = storage_path('framework/testing/admin-authorization.lock');
        @unlink($this->lockPath);
        config([
            'mybsphp.installed' => false,
            'mybsphp.install_lock' => $this->lockPath,
        ]);

        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => 'MyBsPHP2 Authorization',
            'secondary_password_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);

        parent::tearDown();
    }

    public function test_gate_uses_role_permissions_and_defaults_to_deny(): void
    {
        $administrator = $this->administrator('permission-admin');
        $role = Role::query()->where('code', 'admin')->sole();
        $dashboard = $this->permission(AdminPermission::DashboardView);

        $role->permissions()->attach($dashboard);
        $administrator->roles()->attach($role);

        $this->assertTrue(
            Gate::forUser($administrator)->allows(AdminPermission::DashboardView->value),
        );
        $this->assertFalse(
            Gate::forUser($administrator)->allows(AdminPermission::ApplicationsView->value),
        );

        $administrator->forceFill(['status' => 'frozen'])->save();

        $this->assertFalse(
            Gate::forUser($administrator->fresh())->allows(AdminPermission::DashboardView->value),
        );
    }

    public function test_super_administrator_is_allowed_every_registered_permission(): void
    {
        $administrator = $this->administrator('root-admin', 'super_admin');
        $administrator->roles()->attach(
            Role::query()->where('code', 'super_admin')->sole(),
        );

        foreach (AdminPermission::cases() as $permission) {
            $this->assertTrue(
                Gate::forUser($administrator)->allows($permission->value),
                "超级管理员缺少权限：{$permission->value}",
            );
        }
    }

    public function test_administrator_policy_maps_resource_actions_and_protects_self_delete(): void
    {
        $actor = $this->administrator('policy-admin');
        $target = $this->administrator('target-admin');
        $role = Role::query()->where('code', 'admin')->sole();

        foreach ([
            AdminPermission::AdministratorsView,
            AdminPermission::AdministratorsUpdate,
            AdminPermission::AdministratorsDelete,
        ] as $permission) {
            $role->permissions()->attach($this->permission($permission));
        }
        $actor->roles()->attach($role);

        $gate = Gate::forUser($actor);

        $this->assertTrue($gate->allows('viewAny', Administrator::class));
        $this->assertTrue($gate->allows('view', $target));
        $this->assertTrue($gate->allows('update', $target));
        $this->assertTrue($gate->allows('delete', $target));
        $this->assertFalse($gate->allows('delete', $actor));
        $this->assertFalse($gate->allows('create', Administrator::class));
    }

    public function test_dashboard_route_and_navigation_are_filtered_by_permission(): void
    {
        $administrator = $this->administrator('limited-admin');
        $role = Role::query()->where('code', 'admin')->sole();

        foreach ([
            AdminPermission::DashboardView,
            AdminPermission::ApplicationsView,
        ] as $permission) {
            $role->permissions()->attach($this->permission($permission));
        }
        $administrator->roles()->attach($role);

        $this->actingAs($administrator, 'admin')
            ->get('/private-console/dashboard')
            ->assertOk()
            ->assertSee('控制台')
            ->assertSee('应用管理')
            ->assertSee('软件管理')
            ->assertDontSee('添加新软件')
            ->assertDontSee('卡密管理')
            ->assertDontSee('账号管理')
            ->assertDontSee('系统配置')
            ->assertDontSee('最近动态');
    }

    public function test_dashboard_route_rejects_an_administrator_without_dashboard_permission(): void
    {
        $administrator = $this->administrator('no-dashboard-admin');
        $administrator->roles()->attach(
            Role::query()->where('code', 'admin')->sole(),
        );

        $this->actingAs($administrator, 'admin')
            ->get('/private-console/dashboard')
            ->assertForbidden();
    }

    private function administrator(string $username, string $legacyRole = 'admin'): Administrator
    {
        return Administrator::query()->create([
            'username' => $username,
            'password' => 'primary-secret',
            'role' => $legacyRole,
            'status' => 'active',
        ]);
    }

    private function permission(AdminPermission $permission): Permission
    {
        return Permission::query()->firstOrCreate(
            ['code' => $permission->value],
            [
                'module' => str((string) $permission->value)->before('.')->toString(),
                'name' => $permission->name,
                'risk_level' => 'normal',
                'agent_assignable' => false,
            ],
        );
    }
}
