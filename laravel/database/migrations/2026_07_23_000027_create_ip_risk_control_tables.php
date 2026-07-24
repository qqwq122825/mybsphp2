<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('window_seconds')->default(60);
            $table->unsignedSmallInteger('low_threshold')->default(6);
            $table->unsignedSmallInteger('high_threshold')->default(12);
            $table->unsignedInteger('block_duration_minutes')->default(60);
            $table->string('low_action', 24)->default('decoy');
            $table->string('decoy_value', 255)->nullable();
            $table->unsignedSmallInteger('block_http_status')->default(429);
            $table->string('block_code', 64)->default('ACCESS_TEMPORARILY_RESTRICTED');
            $table->string('block_message', 255)->default('当前请求过于频繁，请稍后再试。');
            $table->timestamps();
        });

        Schema::create('ip_risk_states', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('ip', 45);
            $table->char('ip_hash', 64);
            $table->string('level', 24)->default('normal')->index();
            $table->unsignedInteger('window_attempts')->default(0);
            $table->unsignedBigInteger('total_attempts')->default(0);
            $table->timestamp('window_started_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('blocked_until')->nullable()->index();
            $table->boolean('permanently_blocked')->default(false);
            $table->char('last_device_hash', 64)->nullable();
            $table->string('last_endpoint', 100)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'ip_hash']);
            $table->index(['application_id', 'level', 'last_seen_at']);
        });

        Schema::create('risk_events', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('ip_risk_state_id')->nullable()->constrained('ip_risk_states')->nullOnDelete();
            $table->string('ip', 45);
            $table->string('level', 24)->index();
            $table->string('action', 32);
            $table->string('endpoint', 100);
            $table->unsignedInteger('window_attempts');
            $table->unsignedInteger('threshold');
            $table->char('device_hash', 64)->nullable();
            $table->string('reason', 255);
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_events');
        Schema::dropIfExists('ip_risk_states');
        Schema::dropIfExists('risk_policies');
    }
};
