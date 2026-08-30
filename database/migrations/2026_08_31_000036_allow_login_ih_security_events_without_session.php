<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_security_events', function (Blueprint $table): void {
            $table->dropForeign(['application_client_session_id']);
        });
        Schema::table('client_security_events', function (Blueprint $table): void {
            $table->unsignedBigInteger('application_client_session_id')->nullable()->change();
        });
        Schema::table('client_security_events', function (Blueprint $table): void {
            $table->foreign('application_client_session_id')
                ->references('id')
                ->on('application_client_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_security_events', function (Blueprint $table): void {
            $table->dropForeign(['application_client_session_id']);
        });
        Schema::table('client_security_events', function (Blueprint $table): void {
            $table->unsignedBigInteger('application_client_session_id')->nullable(false)->change();
        });
        Schema::table('client_security_events', function (Blueprint $table): void {
            $table->foreign('application_client_session_id')
                ->references('id')
                ->on('application_client_sessions')
                ->cascadeOnDelete();
        });
    }
};
