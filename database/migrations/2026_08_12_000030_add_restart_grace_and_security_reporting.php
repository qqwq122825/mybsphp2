<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_policies', function (Blueprint $table): void {
            $table->unsignedSmallInteger('restart_grace_seconds')->default(4);
        });
        Schema::table('ip_risk_states', function (Blueprint $table): void {
            $table->char('last_subject_hash', 64)->nullable();
            $table->boolean('last_attempt_succeeded')->default(false);
        });
        Schema::table('application_client_sessions', function (Blueprint $table): void {
            $table->char('security_report_token_hash', 64)->nullable();
            $table->timestamp('security_report_token_expires_at')->nullable();
        });
        Schema::create('client_security_events', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('application_client_session_id')->constrained('application_client_sessions')->cascadeOnDelete();
            $table->foreignId('card_id')->nullable()->constrained('cards')->nullOnDelete();
            $table->foreignId('application_account_id')->nullable()->constrained('application_accounts')->nullOnDelete();
            $table->string('event_id', 64);
            $table->string('event_code', 16);
            $table->string('severity', 16);
            $table->char('ip_hash', 64);
            $table->char('device_hash', 64);
            $table->json('evidence')->nullable();
            $table->string('action', 16);
            $table->timestamp('created_at')->useCurrent()->index();
            $table->unique(['application_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_security_events');
        Schema::table('application_client_sessions', fn (Blueprint $t) => $t->dropColumn(['security_report_token_hash', 'security_report_token_expires_at']));
        Schema::table('ip_risk_states', fn (Blueprint $t) => $t->dropColumn(['last_subject_hash', 'last_attempt_succeeded']));
        Schema::table('risk_policies', fn (Blueprint $t) => $t->dropColumn('restart_grace_seconds'));
    }
};
