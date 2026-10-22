<?php

namespace App\Cards;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Models\Card;
use App\Models\CardType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class CardOperationLogger
{
    // Deliberately exclude heartbeat/online timestamps, failed attempts and lock counters.
    public const FIELDS = [
        'status', 'card_is_frozen', 'user_status', 'activated_at', 'issued_at',
        'consumed_at', 'expires_at', 'frozen_at', 'user_frozen_at', 'revoked_at',
        'bound_device_ciphertext', 'bound_device_mask', 'activation_count',
        'redeemed_by_account_id', 'account_concurrency_limit', 'machine_concurrency_limit',
        'user_note', 'agent_starred', 'card_type_id', 'application_id', 'batch_id',
        'code_ciphertext', 'created_at', 'is_timer', 'timer_paused_at', 'timer_pause_count',
        'timer_pause_limit', 'timer_pause_max_seconds',
    ];

    public const LEGACY_ACTIONS = [
        'agent.card_freeze' => '冻结卡密', 'agent.card_unfreeze' => '解冻卡密',
        'agent.user_unfreeze' => '解冻软件用户', 'agent.card_delete' => '删除卡密',
        'agent.card_binding_updated' => '解除设备绑定',
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /** @param array<string, mixed> $beforeExtra
     * @param  array<string, mixed>  $afterExtra
     */
    public function record(?Card $before, ?Card $after, string $operation = 'updated', array $beforeExtra = [], array $afterExtra = []): void
    {
        $card = $after ?? $before;
        if (! $card) {
            return;
        }
        $old = $this->snapshot($before);
        $new = $this->snapshot($after);
        if ($old !== null) {
            $old = array_merge($old, $beforeExtra);
        }
        if ($new !== null) {
            $new = array_merge($new, $afterExtra);
        }
        if ($operation === 'updated' && $old === $new) {
            if (! self::isManagementOperation()) {
                return;
            }
            $operation = 'unchanged';
        }
        $request = request();
        $route = (string) $request->route()?->getName();
        $actor = str_starts_with($route, 'agent.') ? $request->user('agent')
            : (str_starts_with($route, 'admin.') ? $request->user('admin') : null);
        $role = str_starts_with($route, 'agent.') ? 'agent'
            : (str_starts_with($route, 'admin.') ? ($actor->role ?? 'admin') : ($route !== '' ? 'client' : 'system'));
        $summary = $this->summary($old, $new, $operation);
        $this->audit->record(new AuditEvent(
            action: 'card.operation',
            targetType: 'card',
            targetId: (string) $card->public_id,
            applicationId: (int) $card->application_id,
            before: ['state' => $old],
            after: [
                'state' => $new,
                'operation' => $summary,
                'actor_username' => $actor?->username,
                'source' => $route !== '' ? $route : 'system',
                'requested_action' => self::requestedAction(),
                // The code stays encrypted at rest, even after a physical card deletion.
                'identity' => [
                    'id' => $card->getKey(), 'public_id' => $card->public_id,
                    'credential_ciphertext' => $card->getRawOriginal('code_ciphertext'),
                    'lookup_hash' => $card->code_lookup_hash,
                    'application_name' => $card->application?->name,
                    'card_type_name' => $card->cardType?->name,
                ],
            ],
            reason: $summary,
            actorRole: $role,
        ), $request);
    }

    /** @return array<string, mixed>|null */
    public function snapshot(?Card $card): ?array
    {
        if (! $card) {
            return null;
        }
        $state = [];
        foreach (self::FIELDS as $field) {
            if (in_array($field, ['code_ciphertext', 'bound_device_ciphertext'], true)) {
                // Store only a fingerprint of binding changes, never the raw device identifier.
                $state[$field === 'code_ciphertext' ? 'credential_fingerprint' : 'binding_fingerprint'] =
                    filled($card->{$field}) ? hash('sha256', (string) $card->{$field}) : null;

                continue;
            }
            $value = $card->{$field};
            $state[$field] = $value instanceof CarbonInterface ? $value->toIso8601String() : $value;
        }

        return $state;
    }

    /** @param array<string, mixed>|null $old
     * @param  array<string, mixed>|null  $new
     */
    private function summary(?array $old, ?array $new, string $operation): string
    {
        if ($operation === 'created') {
            return '制作卡密';
        }
        if ($operation === 'deleted') {
            return '永久删除卡密';
        }
        if ($operation === 'unchanged') {
            $requested = match (self::requestedAction()) {
                'freeze', 'batch-freeze' => '冻结', 'unfreeze', 'batch-unfreeze' => '解冻', 'unbind' => '解绑',
                'used' => '设已使用', 'unused' => '设未使用', 'delete', 'delete_card' => '删除',
                default => '重复操作',
            };

            return $requested.'（状态未变化）';
        }
        if ($operation === 'type_updated') {
            return '修改卡种配置（影响同卡种卡密）';
        }
        $labels = [];
        if (($old['timer_paused_at'] ?? null) !== ($new['timer_paused_at'] ?? null)) {
            $labels[] = ($new['timer_paused_at'] ?? null) !== null ? '冻结计时' : '恢复计时';
        }
        if (($old['card_is_frozen'] ?? false) !== ($new['card_is_frozen'] ?? false)) {
            $labels[] = $new['card_is_frozen'] ? '冻结卡密' : '解冻卡密';
        }
        if (($old['user_status'] ?? null) !== ($new['user_status'] ?? null)) {
            $labels[] = match ($new['user_status']) {
                'frozen' => '冻结软件用户', 'revoked' => '撤销软件用户',
                'active' => ($old['user_status'] ?? '') === 'frozen' ? '解冻软件用户' : '启用软件用户',
                default => '重置软件用户状态',
            };
        }
        if (($old['status'] ?? null) !== ($new['status'] ?? null)) {
            $labels[] = match ($new['status']) {
                'issued' => '发出卡密', 'inventory' => '设为未使用', 'active' => '激活 / 设为已使用',
                'consumed' => '充值使用', 'expired' => '卡密到期', 'revoked' => '删除 / 撤销卡密',
                'frozen' => '冻结卡密', default => '修改卡状态',
            };
        }
        $timerPauseChanged = ($old['timer_paused_at'] ?? null) !== ($new['timer_paused_at'] ?? null);
        foreach (['expires_at' => '调整到期时间', 'binding_fingerprint' => '修改设备绑定',
            'user_note' => '修改备注', 'agent_starred' => '修改标记', 'created_at' => '修改制作时间',
            'account_concurrency_limit' => '修改账号多开', 'machine_concurrency_limit' => '修改机器多开',
            'card_type_id' => '修改卡种', 'credential_fingerprint' => '修改卡号'] as $field => $label) {
            if ($timerPauseChanged && $field === 'expires_at') {
                continue; // 恢复计时顺延到期时间，已由"恢复计时"说明。
            }
            if (($old[$field] ?? null) !== ($new[$field] ?? null)) {
                $labels[] = $label;
            }
        }

        return $labels === [] ? '更新卡资料' : implode('；', array_unique($labels));
    }

    public static function isManagementOperation(): bool
    {
        return request()->routeIs('agent.cards.action', 'admin.cards.bulk-action', 'admin.cards.maintenance.run',
            'admin.operation-plugins.show', 'admin.applications.card-users.update', 'admin.applications.users.bulk-action',
            'admin.application-users.maintenance.run', 'admin.quick-operations.action');
    }

    private static function requestedAction(): ?string
    {
        if (! self::isManagementOperation()) {
            return null;
        }
        $action = request()->routeIs('admin.operation-plugins.show') ? request()->route('tool') : request()->input('action');

        return is_string($action) ? substr($action, 0, 100) : null;
    }

    /** Card-type maintenance changes effective duration/price for all cards of that type.
     * @param  Builder<CardType>  $query
     * @param  array<string, mixed>  $values
     */
    public static function updateTypes(Builder $query, array $values): int
    {
        return $query->getModel()->getConnection()->transaction(function () use ($query, $values): int {
            $count = 0;
            foreach ((clone $query)->lockForUpdate()->get() as $type) {
                $before = [];
                $after = [];
                $type->fill($values);
                foreach ($type->getDirty() as $key => $value) {
                    if ($key === 'updated_at') {
                        continue;
                    }
                    $before['type_'.$key] = $type->getRawOriginal($key);
                    $after['type_'.$key] = $value;
                }
                $type->save();
                if ($before !== $after) {
                    Card::query()->where('card_type_id', $type->getKey())->lockForUpdate()->chunkById(200, function ($cards) use ($before, $after): void {
                        foreach ($cards as $card) {
                            app(self::class)->record($card, $card, 'type_updated', $before, $after);
                        }
                    });
                }
                $count++;
            }

            return $count;
        });
    }

    /** Bulk Eloquent/SQL updates skip model events. All application card bulk writes use this path.
     * @param  Builder<Card>  $query
     * @param  array<string, mixed>  $values
     */
    public static function updateCards(Builder $query, array $values): int
    {
        return $query->getModel()->getConnection()->transaction(function () use ($query, $values): int {
            $count = 0;
            (clone $query)->reorder()->lockForUpdate()->chunkById(200, function ($cards) use ($values, &$count): void {
                foreach ($cards as $card) {
                    $card->forceFill($values)->save();
                    $count++;
                }
            });

            return $count;
        });
    }

    /** @param Builder<Card> $query */
    public static function deleteCards(Builder $query): int
    {
        return $query->getModel()->getConnection()->transaction(function () use ($query): int {
            $count = 0;
            (clone $query)->reorder()->lockForUpdate()->chunkById(200, function ($cards) use (&$count): void {
                foreach ($cards as $card) {
                    $count += (int) $card->delete();
                }
            });

            return $count;
        });
    }
}
