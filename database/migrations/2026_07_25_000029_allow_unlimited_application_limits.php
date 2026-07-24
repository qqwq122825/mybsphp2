<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->smallInteger('max_bound_devices')->default(1)->change();
            $table->smallInteger('max_concurrent_sessions')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_bound_devices')->default(1)->change();
            $table->unsignedSmallInteger('max_concurrent_sessions')->default(1)->change();
        });
    }
};
