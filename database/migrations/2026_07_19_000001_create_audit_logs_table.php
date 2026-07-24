<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_role_snapshot', 100)->nullable();
            $table->string('action', 120);
            $table->string('target_type', 120);
            $table->string('target_id', 100)->nullable();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->char('request_id', 26)->index();
            $table->string('ip', 45)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->text('reason')->nullable();
            $table->string('result', 32)->default('success');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_user_id', 'created_at']);
            $table->index(['target_type', 'target_id', 'created_at']);
            $table->index(['application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
