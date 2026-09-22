<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('path', 40)->unique();
            $table->string('previous_path', 40)->nullable()->unique();
            $table->timestamp('previous_path_until')->nullable();
            $table->text('token_ciphertext');
            $table->string('permission', 10)->default('replay');
            $table->string('node_id', 64)->nullable();
            $table->string('node_name', 80)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->unsignedInteger('last_user_count')->default(0);
            $table->timestamps();
        });
        Schema::create('proxy_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type', 10);
            $table->string('subject_id', 40);
            $table->string('username', 64)->unique();
            $table->text('password_ciphertext');
            $table->timestamps();
            $table->unique(['application_id', 'subject_type', 'subject_id'], 'proxy_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_credentials');
        Schema::dropIfExists('proxy_links');
    }
};
