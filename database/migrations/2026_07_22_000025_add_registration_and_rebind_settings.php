<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->unsignedInteger('registration_bonus_value')->default(0)->after('connection_timeout_seconds');
            $table->string('registration_bonus_unit', 16)->default('hour')->after('registration_bonus_value');
            $table->unsignedInteger('rebind_deduction_value')->default(0)->after('registration_bonus_unit');
            $table->string('rebind_deduction_unit', 16)->default('hour')->after('rebind_deduction_value');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn([
                'registration_bonus_value', 'registration_bonus_unit',
                'rebind_deduction_value', 'rebind_deduction_unit',
            ]);
        });
    }
};
