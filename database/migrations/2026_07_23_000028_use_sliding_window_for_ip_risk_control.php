<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_risk_states', function (Blueprint $table): void {
            $table->json('recent_attempt_timestamps')->nullable()->after('window_attempts');
        });

        // 第一版尚未正式启用时，将默认固定窗口配置升级为更灵敏的滚动窗口基线。
        DB::table('risk_policies')
            ->where('window_seconds', 60)
            ->where('low_threshold', 6)
            ->where('high_threshold', 12)
            ->update([
                'window_seconds' => 10,
                'low_threshold' => 3,
                'high_threshold' => 5,
                'block_duration_minutes' => 30,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('risk_policies')
            ->where('window_seconds', 10)
            ->where('low_threshold', 3)
            ->where('high_threshold', 5)
            ->update([
                'window_seconds' => 60,
                'low_threshold' => 6,
                'high_threshold' => 12,
                'block_duration_minutes' => 60,
                'updated_at' => now(),
            ]);

        Schema::table('ip_risk_states', function (Blueprint $table): void {
            $table->dropColumn('recent_attempt_timestamps');
        });
    }
};
