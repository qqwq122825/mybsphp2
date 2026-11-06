<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_types', function (Blueprint $table): void {
            $table->boolean('is_timer')->default(false);
            $table->integer('timer_pause_limit')->default(-1);
            $table->unsignedInteger('timer_pause_max_seconds')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('card_types', function (Blueprint $table): void {
            $table->dropColumn(['is_timer', 'timer_pause_limit', 'timer_pause_max_seconds']);
        });
    }
};
