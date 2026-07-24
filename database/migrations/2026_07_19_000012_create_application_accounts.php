<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_accounts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();
            $table->string('username', 64);
            $table->string('password_hash');
            $table->string('status', 24)->default('active')->index();
            $table->unsignedBigInteger('balance_cents')->default(0);
            $table->unsignedBigInteger('points')->default(0);
            $table->dateTime('expires_at')->nullable()->index();
            $table->text('bound_device_ciphertext')->nullable();
            $table->string('bound_device_mask', 80)->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->unsignedInteger('failed_attempt_count')->default(0);
            $table->timestamps();

            $table->unique(['application_id', 'username']);
            $table->index(['application_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_accounts');
    }
};
