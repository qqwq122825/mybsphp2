<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_policies', function (Blueprint $table): void {
            $table->unsignedSmallInteger('abnormal_exit_window_minutes')->default(60);
            $table->unsignedSmallInteger('abnormal_exit_high_threshold')->default(8);
        });
    }

    public function down(): void
    {
        Schema::table('risk_policies', fn (Blueprint $table) => $table->dropColumn([
            'abnormal_exit_window_minutes',
            'abnormal_exit_high_threshold',
        ]));
    }
};
