<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table): void {
            $table->foreignId('owner_agent_id')->nullable()->after('batch_id')
                ->constrained('administrators')->nullOnDelete();
        });

        // Existing agent-made cards stay with the agent that bought them.
        DB::table('cards')->update([
            'owner_agent_id' => DB::raw('(select card_batches.created_by_administrator_id from card_batches'
                .' inner join administrators on administrators.id = card_batches.created_by_administrator_id'
                ." where card_batches.id = cards.batch_id and administrators.role = 'agent')"),
        ]);
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('owner_agent_id');
        });
    }
};
