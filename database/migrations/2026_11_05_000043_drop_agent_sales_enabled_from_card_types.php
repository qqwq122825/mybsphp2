<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agent access to card types is controlled by agent assignments, not a per-type switch.
        if (Schema::hasColumn('card_types', 'agent_sales_enabled')) {
            Schema::table('card_types', function (Blueprint $table): void {
                $table->dropColumn('agent_sales_enabled');
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty: the column is no longer used.
    }
};
