<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Cards\CardCredentialHasher;
use App\Cards\CardGenerationService;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\AgentTransaction;
use App\Models\Application;
use App\Models\ApplicationClientSession;
use App\Models\Card;
use App\Models\CardType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $cards = $this->ownedCardsQuery($agent);
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
        $query = $this->ownedCardsQuery($agent)->with(['application', 'cardType', 'batch']);

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
        $request->input('sort') === 'oldest'
            ? $query->orderBy('cards.id')
            : $query->orderByDesc('cards.id');

        return view('agent.cards.index', [
            ...$this->layout($agent),
            'cards' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function cardAction(Request $request, Card $card): RedirectResponse
    {
        $agent = $this->agent();
        $this->ensureOwnedCard($agent, $card);
        $validated = $request->validate(['action' => ['required', Rule::in(['freeze', 'unfreeze'])]]);

        match ($validated['action']) {
            'freeze' => $card->forceFill(['card_is_frozen' => true, 'frozen_at' => now()])->save(),
            'unfreeze' => $card->forceFill(['card_is_frozen' => false, 'frozen_at' => null])->save(),
        };
        $this->audit->record(new AuditEvent(
            action: 'agent.card_'.$validated['action'],
            targetType: 'card',
            targetId: (string) $card->public_id,
            applicationId: (int) $card->application_id,
            actorRole: 'agent',
        ), $request);

        return back()->with('success', match ($validated['action']) {
            'freeze' => '充值卡已冻结。',
            'unfreeze' => '充值卡已解冻。',
        });
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
            $generated['batch']->cards()->update(['status' => 'issued', 'issued_at' => now(), 'updated_at' => now()]);

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
        $keyword = trim((string) $request->input('keyword', ''));
        $query = $this->ownedCardsQuery($agent)->with(['application', 'cardType', 'batch']);
        if ($keyword === '') {
            $query->whereRaw('0 = 1');
        } else {
            $this->applyCardKeyword($query, $keyword);
        }

        return view('agent.tools.query', [
            ...$this->layout($agent),
            'keyword' => $keyword,
            'cards' => $query->orderByDesc('cards.id')->paginate(50)->withQueryString(),
        ]);
    }

    public function unbind(): View
    {
        return view('agent.tools.unbind', $this->layout($this->agent()));
    }

    public function runUnbind(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', 'max:128'],
        ]);
        $agent = $this->agent();
        $credential = preg_split('/\s+/', trim($validated['credential']))[0] ?? '';
        $card = $this->ownedCardsQuery($agent)
            ->where('code_lookup_hash', $this->credentialHasher->lookupHash($credential))
            ->first();
        if (! $card instanceof Card) {
            throw ValidationException::withMessages(['credential' => '未找到属于当前代理的充值卡。']);
        }

        $card->forceFill([
            'bound_device_ciphertext' => null,
            'bound_device_mask' => null,
        ])->save();
        $this->closeCardSessions($card);
        $this->audit->record(new AuditEvent(
            action: 'agent.card_binding_updated',
            targetType: 'card',
            targetId: (string) $card->public_id,
            applicationId: (int) $card->application_id,
            after: ['binding_cleared' => true],
            actorRole: 'agent',
        ), $request);

        return back()->with('success', '设备绑定已解除。');
    }

    public function freeze(): View
    {
        return $this->freezeView('', collect(), []);
    }

    public function runFreeze(Request $request): View
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', 'max:4000'],
            'activated_action' => ['nullable', Rule::in(['freeze_user', 'delete_card'])],
        ]);
        $agent = $this->agent();
        $cards = $this->ownedCardsForKeyword($agent, $validated['credential']);
        if ($cards->isEmpty()) {
            throw ValidationException::withMessages(['credential' => '未找到属于当前代理的充值卡。']);
        }

        $activatedAction = $validated['activated_action'] ?? null;
        $pending = collect();
        $results = [];
        foreach ($cards as $card) {
            if ($this->cardIsActivated($card)) {
                if ($activatedAction === null) {
                    $pending->push($card);
                    continue;
                }
                $results[] = $activatedAction === 'delete_card'
                    ? $this->deleteActivatedCard($card, $request)
                    : $this->freezeActivatedUser($card, $request);
                continue;
            }

            if ($card->card_is_frozen) {
                $results[] = $card->code_ciphertext.'：未激活卡密原本已封卡';
                continue;
            }
            $card->forceFill(['card_is_frozen' => true, 'frozen_at' => now()])->save();
            $this->audit->record(new AuditEvent(
                action: 'agent.card_freeze',
                targetType: 'card',
                targetId: (string) $card->public_id,
                applicationId: (int) $card->application_id,
                actorRole: 'agent',
            ), $request);
            $results[] = $card->code_ciphertext.'：未激活卡密已封卡';
        }

        return $this->freezeView($validated['credential'], $pending, $results);
    }

    public function unfreeze(): View
    {
        return $this->unfreezeView('', []);
    }

    public function runUnfreeze(Request $request): View
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', 'max:4000'],
        ]);
        $agent = $this->agent();
        $cards = $this->ownedCardsForKeyword($agent, $validated['credential']);
        if ($cards->isEmpty()) {
            throw ValidationException::withMessages(['credential' => '未找到属于当前代理的充值卡。']);
        }

        $results = [];
        foreach ($cards as $card) {
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
                $results[] = $card->code_ciphertext.'：当前未冻结，到期时间未改动';
                continue;
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
            $results[] = $this->cardIsActivated($card)
                ? $card->code_ciphertext.'：用户已解冻，到期时间保持不变'
                : $card->code_ciphertext.'：未激活卡密已解封';
        }

        return $this->unfreezeView($validated['credential'], $results);
    }

    public function finance(): View
    {
        $agent = $this->agent();

        return view('agent.finance', [
            ...$this->layout($agent),
            'transactions' => $agent->agentTransactions()->latest()->paginate(20),
        ]);
    }

    private function freezeActivatedUser(Card $card, Request $request): string
    {
        if ($card->user_status === 'frozen') {
            return $card->code_ciphertext.'：用户原本已冻结，到期时间未改动';
        }
        $card->forceFill(['user_status' => 'frozen', 'user_frozen_at' => now()])->save();
        $this->closeCardSessions($card);
        $this->audit->record(new AuditEvent(
            action: 'agent.user_freeze',
            targetType: 'card',
            targetId: (string) $card->public_id,
            applicationId: (int) $card->application_id,
            after: ['expires_at' => $card->expires_at?->toIso8601String()],
            actorRole: 'agent',
        ), $request);

        return $card->code_ciphertext.'：已激活用户已冻结，到期时间保持不变';
    }

    private function deleteActivatedCard(Card $card, Request $request): string
    {
        if ($card->status === 'revoked') {
            return $card->code_ciphertext.'：卡密原本已删除';
        }
        $card->forceFill([
            'status' => 'revoked',
            'user_status' => 'revoked',
            'revoked_at' => now(),
        ])->save();
        $this->closeCardSessions($card);
        $this->audit->record(new AuditEvent(
            action: 'agent.card_delete',
            targetType: 'card',
            targetId: (string) $card->public_id,
            applicationId: (int) $card->application_id,
            actorRole: 'agent',
        ), $request);

        return $card->code_ciphertext.'：已使用卡密已删除，用户无法继续登录';
    }

    private function freezeView(string $credential, Collection $pending, array $results): View
    {
        return view('agent.tools.freeze', [
            ...$this->layout($this->agent()),
            'credential' => $credential,
            'pending' => $pending,
            'results' => $results,
        ]);
    }

    private function unfreezeView(string $credential, array $results): View
    {
        return view('agent.tools.unfreeze', [
            ...$this->layout($this->agent()),
            'credential' => $credential,
            'results' => $results,
        ]);
    }

    private function ownedCardsForKeyword(Administrator $agent, string $keyword): Collection
    {
        $query = $this->ownedCardsQuery($agent)->with(['application', 'cardType']);
        $this->applyCardKeyword($query, $keyword);

        return $query->orderBy('cards.id')->get();
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
