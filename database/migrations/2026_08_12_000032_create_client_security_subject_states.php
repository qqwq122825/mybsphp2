<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_security_subject_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->char('device_hash', 64);
            $table->unsignedInteger('risk_score')->default(0);
            $table->string('status', 24)->default('observe')->index();
            $table->timestamp('blocked_until')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->string('reason_code', 16)->nullable();
            $table->timestamps();
            $table->unique(['application_id', 'device_hash']);
        });
        DB::table('risk_policies')->where('low_threshold', 3)->where('high_threshold', 5)->update([
            'low_threshold' => 6, 'high_threshold' => 12, 'restart_grace_seconds' => 8, 'low_action' => 'observe', 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('client_security_subject_states');
    }
};
