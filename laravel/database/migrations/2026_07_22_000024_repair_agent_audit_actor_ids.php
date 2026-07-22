<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('audit_logs')
            ->where('actor_role_snapshot', 'agent')
            ->orderBy('id')
            ->chunkById(100, function ($logs): void {
                foreach ($logs as $log) {
                    $actorId = match ($log->target_type) {
                        'administrator' => $this->administratorActorId($log->target_id),
                        'card_batch' => $this->cardBatchActorId($log->target_id),
                        'card' => $this->cardActorId($log->target_id),
                        default => null,
                    };

                    if ($actorId !== null && (int) $log->actor_user_id !== $actorId) {
                        DB::table('audit_logs')->where('id', $log->id)->update([
                            'actor_user_id' => $actorId,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // This migration corrects actor attribution and is intentionally irreversible.
    }

    private function administratorActorId(mixed $targetId): ?int
    {
        if (! is_numeric($targetId)) {
            return null;
        }

        $id = (int) $targetId;

        return DB::table('administrators')
            ->where('id', $id)
            ->where('role', 'agent')
            ->value('id');
    }

    private function cardBatchActorId(mixed $targetId): ?int
    {
        if (! is_string($targetId) || $targetId === '') {
            return null;
        }

        $actorId = DB::table('card_batches')
            ->where('public_id', $targetId)
            ->value('created_by_administrator_id');

        return $actorId === null ? null : (int) $actorId;
    }

    private function cardActorId(mixed $targetId): ?int
    {
        if (! is_string($targetId) || $targetId === '') {
            return null;
        }

        $actorId = DB::table('cards')
            ->join('card_batches', 'card_batches.id', '=', 'cards.batch_id')
            ->where('cards.public_id', $targetId)
            ->value('card_batches.created_by_administrator_id');

        return $actorId === null ? null : (int) $actorId;
    }
};
