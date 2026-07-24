<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrators', function (Blueprint $table): void {
            $table->bigInteger('agent_balance_cents')->default(0)->after('status');
            $table->unsignedTinyInteger('agent_level')->default(1)->after('agent_balance_cents');
        });

        Schema::create('agent_transactions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('agent_id')->constrained('administrators')->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->bigInteger('amount_cents');
            $table->bigInteger('balance_after_cents');
            $table->string('reference_type', 40)->nullable();
            $table->string('reference_id', 80)->nullable();
            $table->string('note', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_transactions');
        Schema::table('administrators', function (Blueprint $table): void {
            $table->dropColumn(['agent_balance_cents', 'agent_level']);
        });
    }
};
