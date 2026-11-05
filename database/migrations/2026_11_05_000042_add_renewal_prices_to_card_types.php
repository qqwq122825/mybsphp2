<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_types', function (Blueprint $table): void {
            $table->unsignedBigInteger('renewal_price_cents')->nullable();
            $table->boolean('agent_sales_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('card_types', function (Blueprint $table): void {
            $table->dropColumn(['renewal_price_cents', 'agent_sales_enabled']);
        });
    }
};
