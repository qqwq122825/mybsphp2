<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->renamePermission(
            'applications.protocol.manage',
            'cards.bulk_manage',
            'cards',
            '充值卡批量维护',
        );
    }

    public function down(): void
    {
        $this->renamePermission(
            'cards.bulk_manage',
            'applications.protocol.manage',
            'applications',
            '管理授权协议',
        );
    }

    private function renamePermission(string $from, string $to, string $module, string $name): void
    {
        $source = DB::table('permissions')->where('code', $from)->first();
        $target = DB::table('permissions')->where('code', $to)->first();

        if ($source && $target) {
            $roleIds = DB::table('role_permissions')->where('permission_id', $source->id)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $target->id],
                    ['updated_at' => now(), 'created_at' => now()],
                );
            }
            DB::table('role_permissions')->where('permission_id', $source->id)->delete();
            DB::table('permissions')->where('id', $source->id)->delete();
            return;
        }

        if ($source) {
            DB::table('permissions')->where('id', $source->id)->update([
                'code' => $to,
                'module' => $module,
                'name' => $name,
                'risk_level' => 'sensitive',
                'updated_at' => now(),
            ]);
        }
    }
};
