<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('card_types')->update([
            'secret_length' => 0,
            'secret_charset' => 'mixed',
        ]);

        DB::table('cards')->update([
            'secret_hash' => Hash::make(''),
            'secret_ciphertext' => Crypt::encryptString(''),
            'secret_last4' => '',
        ]);
    }

    public function down(): void
    {
        // 旧密码明文不会保留；回滚时继续维持无密码卡。
    }
};
