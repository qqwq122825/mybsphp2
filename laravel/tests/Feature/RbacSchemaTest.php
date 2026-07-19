<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RbacSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rbac_tables_and_bootstrap_roles_are_created(): void
    {
        $this->assertTrue(Schema::hasColumns('roles', [
            'code',
            'name',
            'description',
            'is_system',
            'sort_order',
        ]));
        $this->assertTrue(Schema::hasColumns('permissions', [
            'code',
            'module',
            'name',
            'risk_level',
            'agent_assignable',
        ]));
        $this->assertTrue(Schema::hasTable('administrator_roles'));
        $this->assertTrue(Schema::hasTable('role_permissions'));

        $this->assertDatabaseHas('roles', [
            'code' => 'super_admin',
            'is_system' => true,
        ]);
        $this->assertDatabaseHas('roles', [
            'code' => 'admin',
            'is_system' => true,
        ]);
    }

    public function test_administrator_roles_and_role_permissions_are_queryable(): void
    {
        $administrator = $this->administrator();
        $role = Role::query()->create([
            'code' => 'operations_admin',
            'name' => '运营管理员',
            'is_system' => false,
            'sort_order' => 50,
        ]);
        $permission = Permission::query()->create([
            'code' => 'applications.view',
            'module' => 'applications',
            'name' => '查看应用',
            'risk_level' => 'normal',
            'agent_assignable' => false,
        ]);

        $role->permissions()->attach($permission);
        $administrator->roles()->attach($role);

        $this->assertTrue($administrator->hasRole('operations_admin'));
        $this->assertTrue($administrator->hasPermission('applications.view'));
        $this->assertFalse($administrator->hasPermission('cards.delete'));
        $this->assertSame('applications.view', $role->permissions()->sole()->code);
        $this->assertSame('operations_admin', $permission->roles()->sole()->code);
    }

    public function test_super_administrator_receives_the_all_permissions_semantics(): void
    {
        $administrator = $this->administrator();
        $administrator->roles()->attach(
            Role::query()->where('code', 'super_admin')->sole(),
        );

        $this->assertTrue($administrator->hasPermission('future.permission'));
    }

    public function test_pivot_primary_key_rejects_duplicate_role_assignment(): void
    {
        $administrator = $this->administrator();
        $role = Role::query()->where('code', 'admin')->sole();
        $values = [
            'administrator_id' => $administrator->getKey(),
            'role_id' => $role->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('administrator_roles')->insert($values);

        $this->expectException(QueryException::class);
        DB::table('administrator_roles')->insert($values);
    }

    public function test_deleting_a_permission_removes_role_relation(): void
    {
        $role = Role::query()->where('code', 'admin')->sole();
        $permission = Permission::query()->create([
            'code' => 'dashboard.view',
            'module' => 'dashboard',
            'name' => '查看管理首页',
        ]);
        $role->permissions()->attach($permission);

        $permission->delete();

        $this->assertDatabaseMissing('role_permissions', [
            'role_id' => $role->getKey(),
        ]);
    }

    private function administrator(): Administrator
    {
        return Administrator::query()->create([
            'username' => 'rbac-admin',
            'password' => 'primary-secret',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
