<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('application_legacy_configs')
            ->whereIn('application_id', DB::table('applications')
                ->select('id')
                ->where('mode', 'card_login'))
            ->update([
                'login_api_name' => 'login.ic',
                'credential_param' => 'icid',
                'credential_secret_param' => 'icpwd',
            ]);

        DB::table('application_legacy_configs')
            ->whereIn('application_id', DB::table('applications')
                ->select('id')
                ->where('mode', 'account_recharge'))
            ->update([
                'login_api_name' => 'login.lg',
                'credential_param' => 'user',
                'credential_secret_param' => 'pwd',
            ]);
    }

    public function down(): void
    {
        DB::table('application_legacy_configs')->update([
            'login_api_name' => 'login.ic',
            'credential_param' => 'icid',
            'credential_secret_param' => 'icpwd',
        ]);
    }
};
