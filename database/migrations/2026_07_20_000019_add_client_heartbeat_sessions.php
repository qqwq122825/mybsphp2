<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->unsignedSmallInteger('heartbeat_interval_seconds')->default(30)->after('max_concurrent_sessions');
            $table->unsignedSmallInteger('connection_timeout_seconds')->default(120)->after('heartbeat_interval_seconds');
        });

        Schema::create('application_client_sessions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->char('token_hash', 64);
            $table->string('auth_mode', 24);
            $table->foreignId('card_id')->nullable()->constrained('cards')->cascadeOnDelete();
            $table->foreignId('application_account_id')->nullable()->constrained('application_accounts')->cascadeOnDelete();
            $table->text('device_id_ciphertext')->nullable();
            $table->string('device_mask', 80)->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->dateTime('last_heartbeat_at')->index();
            $table->dateTime('logged_out_at')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'token_hash']);
            $table->index(['card_id', 'status', 'last_heartbeat_at'], 'card_session_active_index');
            $table->index(['application_account_id', 'status', 'last_heartbeat_at'], 'account_session_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_client_sessions');
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['heartbeat_interval_seconds', 'connection_timeout_seconds']);
        });
    }
};
