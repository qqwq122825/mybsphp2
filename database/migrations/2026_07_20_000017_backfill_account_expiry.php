<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 旧数据中的 NULL 曾被展示为“永久”。账号注册不赠送时长，按注册时间回填为已过期。
        DB::table('application_accounts')
            ->whereNull('expires_at')
            ->update(['expires_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        // 回填属于数据语义修正，不将历史账号恢复成永久授权。
    }
};
