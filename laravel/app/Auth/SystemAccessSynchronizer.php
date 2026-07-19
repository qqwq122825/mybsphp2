<?php

namespace App\Auth;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SystemAccessSynchronizer
{
    public function sync(?string $connection = null): void
    {
        $database = DB::connection($connection);
        $now = now();

        $database->table('roles')->upsert([
            [
                'code' => 'super_admin',
                'name' => '超级管理员',
                'description' => '平台最高管理角色，拥有全部后台能力。',
                'is_system' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'admin',
                'name' => '管理员',
                'description' => '按分配权限执行后台运营管理。',
                'is_system' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'agent',
                'name' => '代理',
                'description' => '仅处理允许的应用、库存与发卡业务。',
                'is_system' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['code'], ['name', 'description', 'is_system', 'sort_order', 'updated_at']);

        $permissions = array_map(
            static fn (AdminPermission $permission): array => [
                'code' => $permission->value,
                'module' => $permission->module(),
                'name' => $permission->label(),
                'description' => null,
                'risk_level' => $permission->riskLevel(),
                'agent_assignable' => $permission->agentAssignable(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            AdminPermission::cases(),
        );

        $database->table('permissions')->upsert(
            $permissions,
            ['code'],
            ['module', 'name', 'risk_level', 'agent_assignable', 'updated_at'],
        );

        $roleIds = $database->table('roles')
            ->whereIn('code', ['super_admin', 'admin', 'agent'])
            ->pluck('id', 'code');
        $permissionIds = $database->table('permissions')->pluck('id', 'code');

        $adminPermissions = array_filter(
            AdminPermission::cases(),
            static fn (AdminPermission $permission): bool => ! in_array($permission, [
                AdminPermission::CardsDelete,
                AdminPermission::AdministratorsDelete,
            ], true),
        );
        $agentPermissions = array_filter(
            AdminPermission::cases(),
            static fn (AdminPermission $permission): bool => $permission->agentAssignable(),
        );

        $this->replaceRolePermissions(
            $database->table('role_permissions'),
            (int) $roleIds['super_admin'],
            AdminPermission::cases(),
            $permissionIds,
            $now,
        );
        $this->replaceRolePermissions(
            $database->table('role_permissions'),
            (int) $roleIds['admin'],
            $adminPermissions,
            $permissionIds,
            $now,
        );
        $this->replaceRolePermissions(
            $database->table('role_permissions'),
            (int) $roleIds['agent'],
            $agentPermissions,
            $permissionIds,
            $now,
        );
    }

    /**
     * @param  iterable<AdminPermission>  $permissions
     * @param  Collection<string, int>  $permissionIds
     */
    private function replaceRolePermissions(
        Builder $query,
        int $roleId,
        iterable $permissions,
        Collection $permissionIds,
        mixed $now,
    ): void {
        $query->where('role_id', $roleId)->delete();

        $rows = [];
        foreach ($permissions as $permission) {
            $rows[] = [
                'role_id' => $roleId,
                'permission_id' => (int) $permissionIds[$permission->value],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            $query->insert($rows);
        }
    }
}
