<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrators', function (Blueprint $table): void {
            $table->string('admin_session_token_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('administrators', function (Blueprint $table): void {
            $table->dropColumn('admin_session_token_hash');
        });
    }
};
