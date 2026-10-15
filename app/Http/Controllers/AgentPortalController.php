<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Cards\CardCredentialHasher;
use App\Cards\CardGenerationService;
use App\Cards\CardOperationLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\AgentTransaction;
use App\Models\Application;
use App\Models\ApplicationClientSession;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AgentPortalController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly CardGenerationService $generator,
        private readonly CardCredentialHasher $credentialHasher,
        private readonly AuditLogger $audit,
    ) {}

    public function dashboard(): View
    {
        $agent = $this->agent();
        $cards = $this->ownedCardsQuery($agent)->where('cards.status', '!=', 'revoked');
        $today = today();
        $yesterday = today()->subDay();

        return view('agent.dashboard', [
            ...$this->layout($agent),
            'announcement' => Application::query()->where('status', 'active')->whereNotNull('announcement')->value('announcement'),
            'metrics' => [
                'balance' => $agent->agent_balance_cents,
                'total' => (clone $cards)->count(),
                'activated' => (clone $cards)->whereIn('status', ['active', 'expired', 'consumed'])->count(),
                'unused' => (clone $cards)->whereIn('status', ['inventory', 'issued'])->count(),
                'frozen' => (clone $cards)->where('card_is_frozen', true)->count(),
                'today_created' => (clone $cards)->whereDate('cards.created_at', $today)->count(),
                'today_activated' => (clone $cards)->whereDate('activated_at', $today)->count(),
                'today_frozen' => (clone $cards)->whereDate('frozen_at', $today)->count(),
                'yesterday_activated' => (clone $cards)->whereDate('activated_at', $yesterday)->count(),
            ],
        ]);
    }

    public function cards(Request $request): View
    {
        $agent = $this->agent();
        $query = $this->ownedCardsQuery($agent)
            ->with(['application', 'cardType', 'batch'])
            ->where('cards.status', '!=', 'revoked');

        if ($request->filled('application')) {
            $query->where('application_id', $request->integer('application'));
        }
        if ($request->filled('card_type')) {
            $query->where('card_type_id', $request->integer('card_type'));
        }
        if ($request->filled('usage')) {
            $request->input('usage') === 'used'
                ? $query->whereIn('cards.status', ['active', 'expired', 'consumed'])
                : $query->whereIn('cards.status', ['inventory', 'issued']);
        }
        if ($request->filled('freeze')) {
            $query->where('card_is_frozen', $request->input('freeze') === 'frozen');
        }
        if ($request->filled('keyword')) {
            $this->applyCardKeyword($query, (string) $request->input('keyword'));
        }
        $query->orderByDesc('cards.agent_starred');
        $request->input('sort') === 'oldest'
            ? $query->orderBy('cards.id')
            : $query->orderByDesc('cards.id');

        return view('agent.cards.index', [
            ...$this->layout($agent),
            'cards' => $query->paginate(20)->withQueryString(),
            'unbindCard' => session('agent_unbind_card'),
            'deleteCard' => session('agent_delete_card'),
        ]);
    }

    public function cardAction(Request $request, Card $card): RedirectResponse|JsonResponse
    {
        $agent = $this->agent();
        $this->ensureOwnedCard($agent, $card);
        $validated = $request->validate([
            'action' => ['required', Rule::in(['freeze', 'unfreeze', 'delete_card', 'unbind', 'star'])],
            'deduct_time' => ['nullable', 'boolean'],
            'confirmed' => ['nullable', 'boolean'],
        ]);
        $card->loadMissing(['application', 'cardType', 'batch']);

        if ($validated['action'] === 'star') {
            $starred = ! $card->agent_starred;
            $card->forceFill(['agent_starred' => $starred])->save();
            if ($request->expectsJson()) {
                return response()->json(['starred' => $starred]);
            }

            return back();
        }

        if ($validated['action'] === 'freeze' && $this->cardIsActivated($card)) {
            $message = $card->agentDeleteEligible()
                ? '已激活卡密不能冻结，可在激活 1 天内删除并退回制卡金额。'
                : '已激活卡密不能冻结，且已超过可删除退款时限。';

            return back()->withErrors(['action' => $message]);
        }

        if ($validated['action'] === 'delete_card' && ($error = $this->agentCardDeleteError($card))) {
            return back()->withErrors(['action' => $error]);
        }

        if ($validated['action'] === 'delete_card' && ! $request->boolean('confirmed')) {
            $refundCents = $this->agentCardRefundCents($agent, $card);

            return back()->with('agent_delete_card', [
                'id' => $card->getKey(),
                'code' => $card->code_ciphertext,
                'application' => $card->application?->name ?? '',
                'type' => $card->cardType?->name ?? '',
                'refund' => number_format($refundCents / 100, 2),
            ]);
        }

        if ($validated['action'] === 'unbind' && ! $request->has('deduct_time')
            && (int) ($card->application?->rebindDeductionSeconds() ?? 0) > 0) {
            return back()->with('agent_unbind_card', [
                'id' => $card->getKey(),
                'code' => $card->code_ciphertext,
                'application' => $card->application?->name ?? '',
                'type' => $card->cardType?->name ?? '',
                'deduction' => $this->rebindDeductionLabel($card->application),
            ]);
        }

        $message = match ($validated['action']) {
            'freeze' => $this->sealUnusedCard($card, $request),
            'unfreeze' => $this->unfreezeOwnedCard($card, $request),
            'delete_card' => $this->deleteOwnedCard($card, $request),
            'unbind' => $this->unbindOwnedCard($card, $request, $request->boolean('deduct_time')),
        };

        return back()->with('success', $message);
    }

    public function purchase(): View
    {
        $agent = $this->agent();
        $types = CardType::query()
            ->with('application')
            ->where('status', 'active')
            ->where('default_agent_price_cents', '>=', 0)
            ->whereHas('application', fn (Builder $query) => $query->where('status', 'active'));
        $this->limitToAssignedApplications($types, $agent);
        $this->limitToAssignedCardTypes($types, $agent);
        $types = $types
            ->orderBy(
                Application::query()->select('name')->whereColumn('applications.id', 'card_types.application_id')
            )
            ->orderBy('application_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('agent.cards.purchase', [
            ...$this->layout($agent),
            'cardTypes' => $types,
            'agentPrices' => $types->mapWithKeys(fn (CardType $type): array => [
                $type->getKey() => $this->agentUnitPrice($agent, $type),
            ]),
            'generatedCards' => session('agent_generated_cards', []),
            'generatedMeta' => session('agent_generated_meta', []),
        ]);
    }

    public function storePurchase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'card_type_id' => ['required', 'integer', 'exists:card_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $actor = $this->agent();
        $cardType = CardType::query()->with('application')
            ->where('status', 'active')
            ->where('default_agent_price_cents', '>=', 0)
            ->findOrFail($validated['card_type_id']);
        abort_unless($cardType->application?->status === 'active', 404);
        abort_unless(
            $this->canAccessApplication($actor, (int) $cardType->application_id)
            && $this->canAccessCardType($actor, (int) $cardType->getKey()),
            404,
        );
        $quantity = (int) $validated['quantity'];
        $unitPrice = $this->agentUnitPrice($actor, $cardType);
        $total = $unitPrice * $quantity;

        $result = DB::transaction(function () use ($actor, $cardType, $quantity, $unitPrice, $total, $validated): array {
            $agent = Administrator::query()->lockForUpdate()->findOrFail($actor->getKey());
            if ((int) $agent->agent_balance_cents < $total) {
                throw ValidationException::withMessages(['quantity' => '当前代理余额不足，请联系平台管理员充值。']);
            }

            $generated = $this->generator->generate(
                $cardType,
                $quantity,
                $agent,
                $validated['note'] ?? '代理余额购卡',
                'sale',
                $agent->username,
            );
            CardOperationLogger::updateCards($generated['batch']->cards()->getQuery(), ['status' => 'issued', 'issued_at' => now(), 'updated_at' => now()]);

            $agent->decrement('agent_balance_cents', $total);
            $agent->refresh();
            AgentTransaction::query()->create([
                'public_id' => (string) Str::ulid(),
                'agent_id' => $agent->getKey(),
                'type' => 'card_purchase',
                'amount_cents' => -$total,
                'balance_after_cents' => $agent->agent_balance_cents,
                'reference_type' => 'card_batch',
                'reference_id' => (string) $generated['batch']->public_id,
                'note' => $validated['note'] ?? null,
                'metadata' => [
                    'card_type_id' => $cardType->getKey(),
                    'card_codes' => collect($generated['credentials'])->pluck('code')->values()->all(),
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPrice,
                    'issue_mode' => 'immediate',
                ],
            ]);

            return $generated;
        }, 3);

        $this->audit->record(new AuditEvent(
            action: 'agent.cards_purchased',
            targetType: 'card_batch',
            targetId: (string) $result['batch']->public_id,
            applicationId: (int) $cardType->application_id,
            after: [
                'quantity' => $quantity,
                'total_cents' => $total,
                'issue_mode' => 'immediate',
                'application_name' => $cardType->application?->name,
                'card_type_name' => $cardType->name,
            ],
            actorRole: 'agent',
        ), $request);

        return redirect()->route('agent.cards.purchase')
            ->with('success', '购卡成功，卡号仅在本次页面展示。')
            ->with('agent_generated_cards', $result['credentials'])
            ->with('agent_generated_meta', [
                'application' => $cardType->application->name,
                'card_type' => $cardType->name,
                'quantity' => $quantity,
                'total' => $total,
            ]);
    }

    public function queryCards(Request $request): View
    {
        $agent = $this->agent();
        $keyword = trim((string) $request->query('keyword', ''));
        $query = Card::query()
            ->with(['application', 'cardType', 'batch'])
            ->latest('id');

        if ($keyword === '') {
            $query->whereRaw('1 = 0');
        } else {
            $this->applyCardKeyword($query, $keyword);
        }

        return view('agent.tools.query', [
            ...$this->layout($agent),
            'keyword' => $keyword,
            'cards' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function unbind(): RedirectResponse
    {
        return redirect()->route('agent.cards.index');
    }

    public function freeze(): RedirectResponse
    {
        return redirect()->route('agent.cards.index');
    }

    public function unfreeze(): RedirectResponse
    {
        return redirect()->route('agent.cards.index');
    }

    public function finance(): View
    {
        $agent = $this->agent();
        $transactions = $agent->agentTransactions()->latest()->paginate(20);
        $cardPublicIds = collect($transactions->items())
            ->filter(fn (AgentTransaction $transaction): bool => $transaction->reference_type === 'card')
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();
        $cardCodes = Card::query()
            ->whereIn('public_id', $cardPublicIds)
            ->get(['public_id', 'code_ciphertext'])
            ->mapWithKeys(fn (Card $card): array => [(string) $card->public_id => (string) $card->code_ciphertext]);
        $batchPublicIds = collect($transactions->items())
            ->filter(fn (AgentTransaction $transaction): bool => $transaction->reference_type === 'card_batch')
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();
        $batchCardCodes = CardBatch::query()
            ->with('cards:id,batch_id,code_ciphertext')
            ->whereIn('public_id', $batchPublicIds)
            ->get(['id', 'public_id'])
            ->mapWithKeys(fn (CardBatch $batch): array => [
                (string) $batch->public_id => $batch->cards->pluck('code_ciphertext')->map(strval(...))->values()->all(),
            ]);
        $transactions->getCollection()->each(function (AgentTransaction $transaction) use ($cardCodes, $batchCardCodes): void {
            $metadataCode = (string) ($transaction->metadata['card_code'] ?? '');
            $metadataCodes = collect($transaction->metadata['card_codes'] ?? [])
                ->filter(fn ($code): bool => is_string($code) && $code !== '')
                ->values()
                ->all();
            $codes = match ($transaction->reference_type) {
                'card' => $metadataCode !== ''
                    ? [$metadataCode]
                    : array_filter([$cardCodes[(string) $transaction->reference_id] ?? null]),
                'card_batch' => $metadataCodes !== []
                    ? $metadataCodes
                    : ($batchCardCodes[(string) $transaction->reference_id] ?? []),
                default => [],
            };
            $transaction->setAttribute(
                'card_reference_codes',
                array_values($codes),
            );
        });

        return view('agent.finance', [
            ...$this->layout($agent),
            'transactions' => $transactions,
        ]);
    }

    private function deleteOwnedCard(Card $card, Request $request): string
    {
        $agentId = $this->agent()->getKey();

        return DB::transaction(function () use ($card, $request, $agentId): string {
            $lockedCard = Card::query()->lockForUpdate()->findOrFail($card->getKey());
            $lockedCard->loadMissing(['batch', 'cardType']);
            $agent = Administrator::query()->lockForUpdate()->findOrFail($agentId);
            if ($lockedCard->status === 'revoked') {
                return $lockedCard->code_ciphertext.'：卡密原本已删除';
            }
            if ($error = $this->agentCardDeleteError($lockedCard)) {
                throw ValidationException::withMessages(['action' => $error]);
            }

            $refundCents = $this->agentCardRefundCents($agent, $lockedCard);
            $alreadyRefunded = AgentTransaction::query()
                ->where('agent_id', $agent->getKey())
                ->where('type', 'card_refund')
                ->where('reference_type', 'card')
                ->where('reference_id', (string) $lockedCard->public_id)
                ->exists();

            $lockedCard->forceFill([
                'status' => 'revoked',
                'user_status' => 'revoked',
                'revoked_at' => now(),
            ])->save();
            $this->closeCardSessions($lockedCard);

            $refunded = false;
            if ($refundCents > 0 && ! $alreadyRefunded) {
                $agent->increment('agent_balance_cents', $refundCents);
                $agent->refresh();
                AgentTransaction::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'agent_id' => $agent->getKey(),
                    'type' => 'card_refund',
                    'amount_cents' => $refundCents,
                    'balance_after_cents' => $agent->agent_balance_cents,
                    'reference_type' => 'card',
                    'reference_id' => (string) $lockedCard->public_id,
                    'note' => '删除卡密退回制卡金额',
                    'metadata' => [
                        'batch_public_id' => $lockedCard->batch?->public_id,
                        'card_code' => (string) $lockedCard->code_ciphertext,
                        'unit_price_cents' => $refundCents,
                    ],
                ]);
                $refunded = true;
            }

            $this->audit->record(new AuditEvent(
                action: 'agent.card_delete',
                targetType: 'card',
                targetId: (string) $lockedCard->public_id,
                applicationId: (int) $lockedCard->application_id,
                after: [
                    'refunded' => $refunded,
                    'refund_cents' => $refunded ? $refundCents : 0,
                ],
                actorRole: 'agent',
            ), $request);

            return $refunded
                ? $lockedCard->code_ciphertext.'：卡密已删除，已退回制卡金额 '.number_format($refundCents / 100, 2).' 元'
                : $lockedCard->code_ciphertext.'：卡密已删除，用户无法继续登录';
        }, 3);
    }

    private function agentCardRefundCents(Administrator $agent, Card $card): int
    {
        $card->loadMissing(['batch', 'cardType']);
        $purchase = AgentTransaction::query()
            ->where('agent_id', $agent->getKey())
            ->where('type', 'card_purchase')
            ->where('reference_type', 'card_batch')
            ->where('reference_id', (string) ($card->batch?->public_id ?? ''))
            ->latest('id')
            ->first();
        if ($purchase instanceof AgentTransaction) {
            return max(0, (int) ($purchase->metadata['unit_price_cents'] ?? 0));
        }
        if ($card->cardType) {
            return $this->agentUnitPrice($agent, $card->cardType);
        }

        return 0;
    }

    private function agentCardDeleteError(Card $card): ?string
    {
        if ($card->agentDeleteEligible()) {
            return null;
        }

        if (in_array($card->status, ['expired', 'consumed'], true) || $card->expires_at?->lte(now())) {
            return '已使用完或已到期的卡密不能删除退款。';
        }

        return '已激活超过 1 天的卡密不能删除退款。';
    }

    private function sealUnusedCard(Card $card, Request $request): string
    {
        if ($card->card_is_frozen) {
            app(CardOperationLogger::class)->record($card, $card, 'unchanged');

            return $card->code_ciphertext.'：未激活卡密原本已封卡';
        }
        $card->forceFill(['card_is_frozen' => true, 'frozen_at' => now()])->save();
        $this->audit->record(new AuditEvent(
            action: 'agent.card_freeze',
            targetType: 'card',
            targetId: (string) $card->public_id,
            applicationId: (int) $card->application_id,
            actorRole: 'agent',
        ), $request);

        return $card->code_ciphertext.'：未激活卡密已封卡';
    }

    private function unfreezeOwnedCard(Card $card, Request $request): string
    {
        $changed = false;
        if ($card->card_is_frozen) {
            $card->forceFill(['card_is_frozen' => false, 'frozen_at' => null]);
            $changed = true;
        }
        if ($this->cardIsActivated($card) && $card->user_status === 'frozen') {
            $card->forceFill(['user_status' => 'active', 'user_frozen_at' => null]);
            $changed = true;
        }
        if (! $changed) {
            app(CardOperationLogger::class)->record($card, $card, 'unchanged');

            return $card->code_ciphertext.'：当前未冻结，到期时间未改动';
        }
        $card->save();
        $this->audit->record(new AuditEvent(
            action: $this->cardIsActivated($card) ? 'agent.user_unfreeze' : 'agent.card_unfreeze',
            targetType: 'card',
            targetId: (string) $card->public_id,
            applicationId: (int) $card->application_id,
            after: ['expires_at' => $card->expires_at?->toIso8601String()],
            actorRole: 'agent',
        ), $request);

        return $this->cardIsActivated($card)
            ? $card->code_ciphertext.'：用户已解冻，到期时间保持不变'
            : $card->code_ciphertext.'：未激活卡密已解封';
    }

    private function unbindOwnedCard(Card $card, Request $request, bool $deductTime): string
    {
        $values = [
            'bound_device_ciphertext' => null,
            'bound_device_mask' => null,
        ];
        $seconds = (int) ($card->application?->rebindDeductionSeconds() ?? 0);
        $deducted = false;
        if ($deductTime && $seconds > 0 && $card->expires_at) {
            $values['expires_at'] = $card->expires_at->copy()->subSeconds($seconds);
            $deducted = true;
        }
        $card->forceFill($values)->save();
        $this->closeCardSessions($card);
        $this->audit->record(new AuditEvent(
            action: 'agent.card_binding_updated',
            targetType: 'card',
            targetId: (string) $card->public_id,
            applicationId: (int) $card->application_id,
            after: [
                'binding_cleared' => true,
                'time_deducted' => $deducted,
                'expires_at' => $card->expires_at?->toIso8601String(),
            ],
            actorRole: 'agent',
        ), $request);

        if ($deductTime && ! $deducted) {
            return '设备绑定已解除，当前软件未配置解绑扣时。';
        }

        return $deducted ? '设备绑定已解除，并已扣除解绑时间。' : '设备绑定已解除，未扣除时间。';
    }

    private function rebindDeductionLabel(?Application $application): string
    {
        $seconds = (int) ($application?->rebindDeductionSeconds() ?? 0);
        if ($seconds <= 0) {
            return '当前软件未配置解绑扣时';
        }
        $value = (int) ($application->rebind_deduction_value ?? 0);
        $unit = ['minute' => '分钟', 'hour' => '小时', 'day' => '天', 'month' => '月', 'year' => '年'][$application->rebind_deduction_unit ?? 'hour'] ?? '小时';

        return '解绑扣时 '.$value.$unit;
    }

    private function cardIsActivated(Card $card): bool
    {
        return $card->activated_at !== null || $card->status === 'active';
    }

    private function closeCardSessions(Card $card): void
    {
        ApplicationClientSession::query()
            ->where('card_id', $card->getKey())
            ->where('status', 'active')
            ->update([
                'status' => 'logged_out',
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function applyCardKeyword(Builder $query, string $keyword): void
    {
        $tokens = preg_split('/[\s,;]+/', trim($keyword), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($tokens === []) {
            return;
        }

        $query->where(function (Builder $inner) use ($tokens): void {
            foreach ($tokens as $token) {
                if (strlen($token) <= 4) {
                    $inner->orWhere('code_last4', $token);
                    if ($token !== strtoupper($token)) {
                        $inner->orWhere('code_last4', strtoupper($token));
                    }

                    continue;
                }
                $inner->orWhere('code_lookup_hash', $this->credentialHasher->lookupHash($token));
            }
        });
    }

    private function ownedCardsQuery(Administrator $agent): Builder
    {
        return Card::query()->whereHas(
            'batch',
            fn (Builder $query) => $query->where('created_by_administrator_id', $agent->getKey()),
        );
    }

    private function ensureOwnedCard(Administrator $agent, Card $card): void
    {
        abort_unless($this->ownedCardsQuery($agent)->whereKey($card->getKey())->exists(), 404);
    }

    private function limitToAssignedApplications(Builder $query, Administrator $agent, string $column = 'application_id'): void
    {
        if ($agent->agent_all_applications === false) {
            $query->whereIn($column, $agent->agentApplications()->pluck('applications.id'));
        }
    }

    private function canAccessApplication(Administrator $agent, int $applicationId): bool
    {
        return $agent->agent_all_applications !== false
            || $agent->agentApplications()->whereKey($applicationId)->exists();
    }

    private function limitToAssignedCardTypes(Builder $query, Administrator $agent): void
    {
        if ($agent->agent_all_applications === false) {
            $query->whereIn('id', $agent->agentCardTypes()->pluck('card_types.id'));
        }
    }

    private function canAccessCardType(Administrator $agent, int $cardTypeId): bool
    {
        return $agent->agent_all_applications !== false
            || $agent->agentCardTypes()->whereKey($cardTypeId)->exists();
    }

    private function agentUnitPrice(Administrator $agent, CardType $type): int
    {
        $basisPoints = min(10000, max(1, (int) ($agent->agent_discount_basis_points ?: 10000)));

        return (int) round(max(0, (int) $type->default_agent_price_cents) * $basisPoints / 10000);
    }

    /** @return array<string, mixed> */
    private function layout(Administrator $agent): array
    {
        return ['appName' => $this->installation->applicationName(), 'agent' => $agent];
    }

    private function agent(): Administrator
    {
        $agent = Auth::guard('agent')->user();
        abort_unless($agent instanceof Administrator, 401);

        return $agent;
    }
}
