<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_types', function (Blueprint $table): void {
            $table->string('note', 500)->nullable()->after('code');
            $table->unsignedInteger('sort_order')->default(0)->after('note');
            $table->unsignedInteger('duration_value')->nullable()->after('duration_seconds');
            $table->string('duration_unit', 16)->nullable()->after('duration_value');
            $table->bigInteger('face_price_cents')->default(100)->change();
            $table->bigInteger('default_agent_price_cents')->default(100)->change();
            $table->bigInteger('balance_recharge_cents')->default(0)->after('default_agent_price_cents');
            $table->string('code_charset', 24)->default('mixed')->after('code_length');
            $table->string('secret_charset', 24)->default('mixed')->after('secret_length');
            $table->string('secret_alphabet', 64)
                ->default('23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz')
                ->after('secret_charset');
            $table->smallInteger('account_concurrency_limit')->default(0)->after('secret_alphabet');
            $table->smallInteger('machine_concurrency_limit')->default(0)->after('account_concurrency_limit');
        });
    }

    public function down(): void
    {
        DB::table('card_types')->where('face_price_cents', '<', 0)->update(['face_price_cents' => 0]);
        DB::table('card_types')->where('default_agent_price_cents', '<', 0)->update(['default_agent_price_cents' => 0]);

        Schema::table('card_types', function (Blueprint $table): void {
            $table->unsignedBigInteger('face_price_cents')->default(0)->change();
            $table->unsignedBigInteger('default_agent_price_cents')->default(0)->change();
            $table->dropColumn([
                'note',
                'sort_order',
                'duration_value',
                'duration_unit',
                'balance_recharge_cents',
                'code_charset',
                'secret_charset',
                'secret_alphabet',
                'account_concurrency_limit',
                'machine_concurrency_limit',
            ]);
        });
    }
};
