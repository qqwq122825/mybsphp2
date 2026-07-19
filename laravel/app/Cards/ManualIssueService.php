<?php

namespace App\Cards;

use App\Models\Administrator;
use App\Models\Card;
use App\Models\CardType;
use App\Models\ManualIssue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class ManualIssueService
{
    public function issueOffline(
        CardType $cardType,
        int $quantity,
        string $recipient,
        string $idempotencyKey,
        ?string $note,
        Administrator $operator,
    ): ManualIssue {
        if ($quantity < 1 || $quantity > 100) {
            throw new RuntimeException('单次发卡数量必须在 1 到 100 之间。');
        }

        return DB::transaction(function () use (
            $cardType,
            $quantity,
            $recipient,
            $idempotencyKey,
            $note,
            $operator,
        ): ManualIssue {
            $existing = ManualIssue::query()
                ->where('operator_administrator_id', $operator->getKey())
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing instanceof ManualIssue) {
                return $existing->load('items.card');
            }

            $cards = Card::query()
                ->where('card_type_id', $cardType->getKey())
                ->where('status', 'inventory')
                ->orderBy('id')
                ->limit($quantity)
                ->lockForUpdate()
                ->get();

            if ($cards->count() !== $quantity) {
                throw new RuntimeException('当前卡种库存不足。');
            }

            $issue = ManualIssue::query()->create([
                'public_id' => (string) Str::ulid(),
                'issue_no' => 'I'.now()->format('YmdHis').strtoupper(Str::random(5)),
                'application_id' => $cardType->application_id,
                'operator_administrator_id' => $operator->getKey(),
                'target_type' => 'offline',
                'offline_recipient_ciphertext' => $recipient,
                'recipient_display_mask' => $this->maskRecipient($recipient),
                'status' => 'committed',
                'quantity' => $quantity,
                'total_cost_cents' => 0,
                'idempotency_key' => $idempotencyKey,
                'note' => $note,
                'committed_at' => now(),
            ]);

            $now = now();
            foreach ($cards as $card) {
                DB::table('manual_issue_items')->insert([
                    'manual_issue_id' => $issue->getKey(),
                    'card_id' => $card->getKey(),
                    'unit_cost_cents' => 0,
                    'delivered_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $card->forceFill([
                    'status' => 'issued',
                    'issued_at' => $now,
                    'lock_version' => $card->lock_version + 1,
                ])->save();
            }

            return $issue->load('items.card');
        }, 3);
    }

    private function maskRecipient(string $recipient): string
    {
        $length = mb_strlen($recipient);

        if ($length <= 2) {
            return mb_substr($recipient, 0, 1).'＊';
        }

        return mb_substr($recipient, 0, 1)
            .str_repeat('＊', min(6, $length - 2))
            .mb_substr($recipient, -1);
    }
}
