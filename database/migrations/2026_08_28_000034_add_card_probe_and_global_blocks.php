<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_policies', function (Blueprint $table): void {
            $table->unsignedSmallInteger('invalid_card_threshold')->default(3);
            $table->unsignedInteger('invalid_card_window_seconds')->default(60);
        });

        Schema::table('ip_risk_states', function (Blueprint $table): void {
            $table->json('recent_card_probes')->nullable();
        });

        Schema::table('client_security_subject_states', function (Blueprint $table): void {
            $table->json('recent_card_probes')->nullable();
        });

        Schema::create('global_security_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type', 16);
            $table->char('subject_hash', 64);
            $table->string('subject_value', 255);
            $table->string('reason', 64)->default('ADMIN_BLOCK');
            $table->string('note', 500)->nullable();
            $table->foreignId('administrator_id')->nullable()->constrained('administrators')->nullOnDelete();
            $table->timestamps();
            $table->unique(['subject_type', 'subject_hash']);
            $table->index(['subject_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_security_blocks');
        Schema::table('client_security_subject_states', function (Blueprint $table): void {
            $table->dropColumn('recent_card_probes');
        });
        Schema::table('ip_risk_states', function (Blueprint $table): void {
            $table->dropColumn('recent_card_probes');
        });
        Schema::table('risk_policies', function (Blueprint $table): void {
            $table->dropColumn(['invalid_card_threshold', 'invalid_card_window_seconds']);
        });
    }
};
