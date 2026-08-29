<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_policies', function (Blueprint $table): void {
            $table->boolean('probe_count_missing_device')->default(true);
            $table->boolean('probe_count_unknown_api')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('risk_policies', function (Blueprint $table): void {
            $table->dropColumn(['probe_count_missing_device', 'probe_count_unknown_api']);
        });
    }
};
