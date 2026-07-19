<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrators', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 32)->unique();
            $table->string('password');
            $table->string('secondary_password')->nullable();
            $table->boolean('secondary_password_required')->default(false);
            $table->string('role', 40)->default('admin');
            $table->string('status', 24)->default('active')->index();
            $table->timestamp('last_login_at')->nullable();
            $table->char('last_login_ip_hash', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table): void {
            $table->string('key', 120)->primary();
            $table->text('value')->nullable();
            $table->string('type', 24)->default('string');
            $table->string('group', 60)->default('system')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('administrators');
    }
};
