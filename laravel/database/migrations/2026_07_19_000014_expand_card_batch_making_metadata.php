<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_batches', function (Blueprint $table): void {
            $table->string('sale_flag', 24)->default('normal')->after('source');
            $table->string('maker_reference', 120)->nullable()->after('created_by_administrator_id');
        });
    }

    public function down(): void
    {
        Schema::table('card_batches', function (Blueprint $table): void {
            $table->dropColumn(['sale_flag', 'maker_reference']);
        });
    }
};
