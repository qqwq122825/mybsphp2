<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')
            ->where('encryption_mode', 'hybrid_rsa_aes_gcm')
            ->update(['encryption_mode' => 'hybrid_rsa_aes_hmac']);

        Schema::table('applications', function (Blueprint $table): void {
            $table->string('encryption_mode', 40)->default('hybrid_rsa_aes_hmac')->change();
        });
    }

    public function down(): void
    {
        DB::table('applications')
            ->where('encryption_mode', 'hybrid_rsa_aes_hmac')
            ->update(['encryption_mode' => 'hybrid_rsa_aes_gcm']);

        Schema::table('applications', function (Blueprint $table): void {
            $table->string('encryption_mode', 40)->default('hybrid_rsa_aes_gcm')->change();
        });
    }
};
