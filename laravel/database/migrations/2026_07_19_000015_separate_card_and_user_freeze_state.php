<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table): void {
            $table->boolean('card_is_frozen')->default(false)->after('status')->index();
            $table->string('user_status', 24)->nullable()->after('card_is_frozen')->index();
            $table->dateTime('user_frozen_at')->nullable()->after('frozen_at');
            $table->dateTime('last_online_at')->nullable()->after('user_frozen_at');
            $table->string('user_note', 500)->nullable()->after('last_online_at');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table): void {
            $table->dropIndex(['card_is_frozen']);
            $table->dropIndex(['user_status']);
            $table->dropColumn(['card_is_frozen', 'user_status', 'user_frozen_at', 'last_online_at', 'user_note']);
        });
    }
};
