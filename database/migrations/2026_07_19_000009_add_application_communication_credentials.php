<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('gateway_token', 64)->nullable()->unique()->after('client_secret_hash');
            $table->longText('communication_key_ciphertext')->nullable()->after('gateway_token');
            $table->string('encryption_mode', 40)->default('hybrid_rsa_aes_hmac')->after('communication_key_ciphertext');
            $table->longText('server_public_key')->nullable()->after('encryption_mode');
            $table->longText('server_private_key_ciphertext')->nullable()->after('server_public_key');
            $table->longText('legacy_packet_key_ciphertext')->nullable()->after('server_private_key_ciphertext');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropUnique(['gateway_token']);
            $table->dropColumn([
                'gateway_token',
                'communication_key_ciphertext',
                'encryption_mode',
                'server_public_key',
                'server_private_key_ciphertext',
                'legacy_packet_key_ciphertext',
            ]);
        });
    }
};
