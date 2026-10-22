<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 计时卡：制卡时勾选，激活后客户端可通过 pause.ic / resume.ic 冻结、恢复计时。
 * 冻结期间 expires_at 保持冻结那一刻的值，恢复时整体顺延冻结时长；
 * 与管理员 / 风控使用的 card_is_frozen、user_status=frozen 完全独立。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table): void {
            $table->boolean('is_timer')->default(false);
            $table->timestamp('timer_paused_at')->nullable();
            $table->unsignedInteger('timer_pause_count')->default(0);
            // -1 不限次数；0 表示不允许冻结（保留给后台单卡调整）。
            $table->integer('timer_pause_limit')->default(-1);
            // 单次冻结最长秒数，0 不限；超过后按到点自动恢复计时。
            $table->unsignedInteger('timer_pause_max_seconds')->default(0);
        });

        Schema::table('card_batches', function (Blueprint $table): void {
            $table->boolean('is_timer')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table): void {
            $table->dropColumn(['is_timer', 'timer_paused_at', 'timer_pause_count', 'timer_pause_limit', 'timer_pause_max_seconds']);
        });

        Schema::table('card_batches', function (Blueprint $table): void {
            $table->dropColumn('is_timer');
        });
    }
};
