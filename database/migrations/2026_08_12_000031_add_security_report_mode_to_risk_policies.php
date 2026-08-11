<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_policies', function (Blueprint $table): void {
            // disabled: 不下发令牌；observe: 只记录；enforce: 按事件策略处置。
            $table->string('security_report_mode', 16)->default('observe');
        });
    }

    public function down(): void
    {
        Schema::table('risk_policies', fn (Blueprint $table) => $table->dropColumn('security_report_mode'));
    }
};
