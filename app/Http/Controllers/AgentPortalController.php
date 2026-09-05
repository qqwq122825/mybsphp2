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
use App\Models\Card;
use App\Models\CardType;
use Illuminate\Database\Eloquent\Builder;
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
            $keyword = trim((string) $request->input('keyword'));
            strlen($keyword) <= 4
                ? $query->where('code_last4', $keyword)
                : $query->where('code_lookup_hash', $this->credentialHasher->lookupHash($keyword));
        }
        $request->input('sort') === 'oldest'
            ? $query->orderBy('cards.id')
            : $query->orderByDesc('cards.id');

        return view('agent.cards.index', [
            ...$this->layout($agent),
            'cards' => $query->paginate(20)->withQueryString(),
            'applications' => Application::query()
                ->whereHas('cards.batch', fn (Builder $batch) => $batch->where('created_by_administrator_id', $agent->getKey()))
                ->orderBy('name')->get(),
            'cardTypes' => CardType::query()
                ->whereHas('cards.batch', fn (Builder $batch) => $batch->where('created_by_administrator_id', $agent->getKey()))
                ->with('application')->orderBy('name')->get(),
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

    public function unbind(): View
    {
        $agent = $this->agent();

        $applications = Application::query()
            ->whereHas('cards.batch', fn (Builder $batch) => $batch->where('created_by_administrator_id', $agent->getKey()));
        $this->limitToAssignedApplications($applications, $agent, 'id');

        return view('agent.tools.unbind', [
            ...$this->layout($agent),
            'applications' => $applications->orderBy('name')->get(),
        ]);
    }

    public function runUnbind(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'credential' => ['required', 'string', 'max:128'],
            'new_binding' => ['nullable', 'string', 'max:255'],
        ]);
        $agent = $this->agent();
        abort_unless($this->canAccessApplication($agent, (int) $validated['application_id']), 404);
        $card = $this->ownedCardsQuery($agent)
            ->where('application_id', $validated['application_id'])
            ->where('code_lookup_hash', $this->credentialHasher->lookupHash(trim($validated['credential'])))
            ->first();
        if (! $card instanceof Card) {
            throw ValidationException::withMessages(['credential' => '未找到属于当前代理的充值卡。']);
        }

        $card->forceFill([
            'bound_device_ciphertext' => filled($validated['new_binding'] ?? null) ? $validated['new_binding'] : null,
            'bound_device_mask' => filled($validated['new_binding'] ?? null)
                ? Str::mask((string) $validated['new_binding'], '*', 3)
                : null,
        ])->save();
        $this->audit->record(new AuditEvent(
            action: 'agent.card_binding_updated',
            targetType: 'card',
            targetId: (string) $card->public_id,
            applicationId: (int) $card->application_id,
            after: ['binding_cleared' => ! filled($validated['new_binding'] ?? null)],
            actorRole: 'agent',
        ), $request);

        return back()->with('success', filled($validated['new_binding'] ?? null) ? '新设备绑定已保存。' : '设备绑定已解除。');
    }

    public function finance(): View
    {
        $agent = $this->agent();

        return view('agent.finance', [
            ...$this->layout($agent),
            'transactions' => $agent->agentTransactions()->latest()->paginate(20),
        ]);
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
