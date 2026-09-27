<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Cards\CardCredentialHasher;
use App\Cards\CardGenerationService;
use App\Cards\CardOperationLogger;
use App\Cards\ManualIssueService;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use App\Models\ManualIssue;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

final class AdminCardController extends Controller
{
    /** @var array<string, string> */
    private const CARD_ALPHABETS = [
        'mixed' => '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz',
        'digits' => '0123456789',
        'uppercase' => 'ABCDEFGHJKLMNPQRSTUVWXYZ',
        'lowercase' => 'abcdefghijkmnopqrstuvwxyz',
        'digits_uppercase' => '23456789ABCDEFGHJKLMNPQRSTUVWXYZ',
        'digits_lowercase' => '23456789abcdefghijkmnopqrstuvwxyz',
    ];

    public function __construct(
        private readonly InstallationManager $installation,
        private readonly CardGenerationService $generator,
        private readonly CardCredentialHasher $credentialHasher,
        private readonly ManualIssueService $issuer,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request, string $adminPath): View
    {
        $applicationFilter = $request->filled('application')
            ? Application::query()->find($request->integer('application'))
            : null;
        $query = Card::query()->with(['application', 'cardType', 'batch'])->latest();
        if ($applicationFilter instanceof Application) {
            $query->where('application_id', $applicationFilter->getKey());
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('card_type')) {
            $query->where('card_type_id', $request->integer('card_type'));
        }
        if ($request->filled('usage')) {
            $request->input('usage') === 'used'
                ? $query->whereIn('status', ['active', 'expired', 'consumed'])
                : $query->whereIn('status', ['inventory', 'issued']);
        }
        if ($request->filled('freeze')) {
            $request->input('freeze') === 'frozen'
                ? $query->where('card_is_frozen', true)
                : $query->where('card_is_frozen', false);
        }
        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            $field = (string) $request->input('search_field', 'code');
            $query->where(function ($builder) use ($field, $keyword): void {
                match ($field) {
                    'id' => $builder->where('id', ctype_digit($keyword) ? (int) $keyword : 0),
                    'batch' => $builder->whereHas('batch', fn ($batch) => $batch->where('batch_no', 'like', '%'.$keyword.'%')),
                    default => strlen($keyword) <= 4
                        ? $builder->where('code_last4', $keyword)
                        : $builder->where('code_lookup_hash', $this->credentialHasher->lookupHash($keyword)),
                };
            });
        }
        $timeField = $request->input('time_field') === 'activated_at' ? 'activated_at' : 'created_at';
        if ($request->filled('start_date')) {
            $query->whereDate($timeField, '>=', (string) $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate($timeField, '<=', (string) $request->input('end_date'));
        }
        $request->input('sort') === 'oldest'
            ? $query->reorder('id')
            : $query->reorder('id', 'desc');

        $metricQuery = Card::query();
        if ($applicationFilter instanceof Application) {
            $metricQuery->where('application_id', $applicationFilter->getKey());
        }

        $cardTypes = CardType::query()
            ->with('application')
            ->when(
                $applicationFilter instanceof Application,
                fn ($builder) => $builder->where('application_id', $applicationFilter->getKey()),
            )
            ->orderBy('name')
            ->get();

        return view('admin.cards.index', [
            ...$this->layout($adminPath),
            'cards' => $query->paginate(30)->withQueryString(),
            'applications' => Application::query()->orderBy('name')->get(),
            'applicationFilter' => $applicationFilter,
            'cardTypes' => $cardTypes,
            'batches' => CardBatch::query()
                ->with('cardType.application')
                ->when(
                    $applicationFilter instanceof Application,
                    fn ($builder) => $builder->whereHas(
                        'cardType',
                        fn ($cardTypeQuery) => $cardTypeQuery->where('application_id', $applicationFilter->getKey()),
                    ),
                )
                ->latest()
                ->limit(20)
                ->get(),
            'metrics' => [
                'total' => (clone $metricQuery)->count(),
                'inventory' => (clone $metricQuery)->where('status', 'inventory')->count(),
                'issued' => (clone $metricQuery)->where('status', 'issued')->count(),
                'active' => (clone $metricQuery)->where('status', 'active')->count(),
            ],
            'generatedCards' => session('generated_cards', []),
        ]);
    }

    public function make(string $adminPath, Application $application): View
    {
        return view('admin.cards.make', [
            ...$this->layout($adminPath),
            'application' => $application,
            'cardTypes' => $application->cardTypes()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'generatedCards' => session('generated_cards', []),
            'generatedBatch' => session('generated_batch', []),
        ]);
    }

    public function bulkAction(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'card_ids' => ['required', 'array', 'min:1'],
            'card_ids.*' => ['integer', 'exists:cards,id'],
            'action' => ['required', Rule::in(['freeze', 'unfreeze', 'used', 'unused', 'delete'])],
        ]);
        $cards = Card::query()->with('cardType')->whereKey($validated['card_ids'])->get();

        foreach ($cards as $card) {
            match ($validated['action']) {
                'freeze' => $card->forceFill(['card_is_frozen' => true, 'frozen_at' => now()])->save(),
                'unfreeze' => $card->forceFill(['card_is_frozen' => false, 'frozen_at' => null])->save(),
                'used' => $this->markCardUsed($card),
                'unused' => $card->forceFill([
                    'status' => 'inventory', 'user_status' => null, 'activated_at' => null,
                    'expires_at' => null, 'bound_device_ciphertext' => null, 'bound_device_mask' => null,
                ])->save(),
                'delete' => $card->forceFill(['status' => 'revoked', 'revoked_at' => now()])->save(),
            };
        }

        $messages = [
            'freeze' => '所选充值卡已冻结。已激活的软件用户不受影响。',
            'unfreeze' => '所选充值卡已解冻。',
            'used' => '所选充值卡已设为使用。',
            'unused' => '所选充值卡已设为未使用。',
            'delete' => '所选充值卡已删除。',
        ];

        return back()->with('success', $messages[$validated['action']]);
    }

    public function maintenance(string $adminPath): View
    {
        return view('admin.cards.maintenance', [
            ...$this->layout($adminPath),
            'applications' => Application::query()->orderBy('name')->get(),
            'cardTypes' => CardType::query()->with('application')->orderBy('application_id')->orderBy('name')->get(),
            'applicationFilter' => null,
        ]);
    }

    public function runMaintenance(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'selection_mode' => ['required', Rule::in(['ids', 'filter'])],
            'id_list' => ['nullable', 'string', 'max:20000'],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'scope' => ['required', Rule::in(['all', 'frozen', 'normal', 'unused', 'used', 'card_type'])],
            'card_type_id' => ['nullable', 'integer', 'exists:card_types,id'],
            'action' => ['required', Rule::in([
                'unused', 'used', 'freeze', 'unfreeze', 'set_duration',
                'set_sale_price', 'set_agent_price', 'set_created_at', 'delete',
            ])],
            'action_value' => ['nullable', 'string', 'max:64'],
            'confirmed' => ['accepted'],
        ]);

        if ($validated['selection_mode'] === 'ids' && ! filled($validated['id_list'] ?? null)) {
            throw ValidationException::withMessages(['id_list' => '请填写卡串索引 ID。']);
        }
        if ($validated['scope'] === 'card_type' && ! filled($validated['card_type_id'] ?? null)) {
            throw ValidationException::withMessages(['card_type_id' => '维护范围为卡类型时需要选择卡类型。']);
        }

        $query = Card::query();
        if ($validated['selection_mode'] === 'ids') {
            $query->whereKey($this->parseCardIds((string) $validated['id_list']));
        } else {
            if (filled($validated['application_id'] ?? null)) {
                $query->where('application_id', (int) $validated['application_id']);
            }
            match ($validated['scope']) {
                'frozen' => $query->where('card_is_frozen', true),
                'normal' => $query->where('card_is_frozen', false),
                'unused' => $query->whereIn('status', ['inventory', 'issued']),
                'used' => $query->whereIn('status', ['active', 'expired', 'consumed']),
                'card_type' => $query->where('card_type_id', (int) $validated['card_type_id']),
                default => $query,
            };
        }

        $affected = (clone $query)->count();
        if ($affected === 0) {
            return back()->withErrors(['selection_mode' => '当前条件没有匹配到充值卡。'])->withInput();
        }

        $value = trim((string) ($validated['action_value'] ?? ''));
        $typeCount = 0;
        DB::transaction(function () use ($validated, $query, $value, &$typeCount): void {
            $now = now();
            match ($validated['action']) {
                'freeze' => CardOperationLogger::updateCards($query, ['card_is_frozen' => true, 'frozen_at' => $now, 'updated_at' => $now]),
                'unfreeze' => CardOperationLogger::updateCards($query, ['card_is_frozen' => false, 'frozen_at' => null, 'updated_at' => $now]),
                'unused' => CardOperationLogger::updateCards($query, [
                    'status' => 'inventory', 'user_status' => null, 'activated_at' => null, 'expires_at' => null,
                    'bound_device_ciphertext' => null, 'bound_device_mask' => null, 'updated_at' => $now,
                ]),
                'used' => $this->markQueryUsed($query),
                'set_duration' => $typeCount = $this->setQueryDuration($query, $this->positiveInteger($value, '充值天数')),
                'set_sale_price' => $typeCount = $this->setQueryPrice($query, $this->priceCents($value, '销售价格'), false),
                'set_agent_price' => $typeCount = $this->setQueryPrice($query, $this->priceCents($value, '代理价格'), true),
                'set_created_at' => CardOperationLogger::updateCards($query, [
                    'created_at' => $this->maintenanceDate($value), 'updated_at' => $now,
                ]),
                'delete' => CardOperationLogger::updateCards($query, ['status' => 'revoked', 'revoked_at' => $now, 'updated_at' => $now]),
            };
        }, 3);

        $this->audit->record(new AuditEvent(
            action: 'cards.bulk_maintained',
            targetType: 'card',
            targetId: $validated['selection_mode'] === 'ids' ? 'id_selection' : 'filtered_selection',
            applicationId: filled($validated['application_id'] ?? null) ? (int) $validated['application_id'] : null,
            after: [
                'action' => $validated['action'], 'affected_cards' => $affected, 'affected_card_types' => $typeCount,
                'scope' => $validated['scope'], 'value' => $value,
            ],
            actorRole: $this->administrator()->role,
        ), $request);

        $suffix = $typeCount > 0 ? "，同步更新 {$typeCount} 个卡类型" : '';

        return back()->with('success', "批量维护完成：共处理 {$affected} 张充值卡{$suffix}。");
    }

    /** @return list<int> */
    private function parseCardIds(string $value): array
    {
        $ids = [];
        foreach (preg_split('/[\s,;，；]+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            if (preg_match('/^(\d+)-(\d+)$/', $part, $matches)) {
                $start = (int) $matches[1];
                $end = (int) $matches[2];
                if ($start < 1 || $end < $start || $end - $start > 10000) {
                    throw ValidationException::withMessages(['id_list' => "ID 范围 {$part} 格式错误或跨度过大。"]);
                }
                foreach (range($start, $end) as $id) {
                    $ids[$id] = $id;
                }

                continue;
            }
            if (! ctype_digit($part) || (int) $part < 1) {
                throw ValidationException::withMessages(['id_list' => "ID {$part} 格式错误。"]);
            }
            $ids[(int) $part] = (int) $part;
        }
        if ($ids === [] || count($ids) > 100000) {
            throw ValidationException::withMessages(['id_list' => '请填写 1 至 100000 个有效卡串 ID。']);
        }

        return array_values($ids);
    }

    private function markQueryUsed($query): void
    {
        $query->with('cardType')->orderBy('id')->chunkById(500, function ($cards): void {
            foreach ($cards as $card) {
                $this->markCardUsed($card);
            }
        });
    }

    private function setQueryDuration($query, int $days): int
    {
        $typeIds = (clone $query)->distinct()->pluck('card_type_id');
        $count = CardOperationLogger::updateTypes(CardType::query()->whereKey($typeIds), [
            'benefit_kind' => 'duration', 'duration_value' => $days, 'duration_unit' => 'day',
            'duration_seconds' => $days * 86400, 'updated_at' => now(),
        ]);
        CardOperationLogger::updateCards((clone $query)->whereIn('status', ['active', 'expired']), [
            'status' => 'active', 'user_status' => 'active', 'expires_at' => now()->addDays($days), 'updated_at' => now(),
        ]);

        return $count;
    }

    private function setQueryPrice($query, int $cents, bool $agent): int
    {
        $typeIds = (clone $query)->distinct()->pluck('card_type_id');

        return CardOperationLogger::updateTypes(CardType::query()->whereKey($typeIds), [
            $agent ? 'default_agent_price_cents' : 'face_price_cents' => $cents,
            'updated_at' => now(),
        ]);
    }

    private function positiveInteger(string $value, string $label): int
    {
        if (! ctype_digit($value) || (int) $value < 1 || (int) $value > 36500) {
            throw ValidationException::withMessages(['action_value' => "{$label}需要填写 1 至 36500 的整数。"]);
        }

        return (int) $value;
    }

    private function priceCents(string $value, string $label): int
    {
        if (! is_numeric($value) || (float) $value < -1 || (float) $value > 999999) {
            throw ValidationException::withMessages(['action_value' => "{$label}需要填写 -1 至 999999 的数值。"]);
        }

        return (int) round((float) $value * 100);
    }

    private function maintenanceDate(string $value): CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $value);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['action_value' => '制卡时间格式需为 YYYY-MM-DD HH:MM:SS。']);
        }
    }

    private function markCardUsed(Card $card): void
    {
        $expiresAt = match ($card->cardType->benefit_kind) {
            'duration' => now()->addSeconds((int) $card->cardType->duration_seconds),
            'fixed_expiry' => $card->cardType->fixed_expires_at,
            default => null,
        };
        $card->forceFill([
            'status' => 'active',
            'user_status' => 'active',
            'activated_at' => $card->activated_at ?? now(),
            'expires_at' => $card->expires_at ?? $expiresAt,
        ])->save();
    }

    public function types(Request $request, string $adminPath, Application $application): View
    {
        $editingType = null;
        if ($request->filled('edit')) {
            $editingType = $application->cardTypes()->where('public_id', (string) $request->query('edit'))->firstOrFail();
        }

        return view('admin.cards.types', [
            ...$this->layout($adminPath),
            'application' => $application,
            'cardTypes' => $application->cardTypes()
                ->withCount('cards')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'editingType' => $editingType,
            'showTypeForm' => $editingType instanceof CardType || $request->boolean('create'),
        ]);
    }

    public function storeType(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $this->validateCardType($request);
        $application = Application::query()->findOrFail($validated['application_id']);

        $codePrefix = (string) ($validated['code_prefix'] ?? '');
        if ((int) $validated['code_length'] - strlen($codePrefix) < 8) {
            return back()->withErrors(['code_length' => '卡号长度至少需要比开头字符多 8 位。'])->withInput();
        }

        $durationSeconds = $this->durationSeconds(
            (int) $validated['duration_value'],
            $validated['duration_unit'],
        );
        $publicId = (string) Str::ulid();

        $cardType = CardType::query()->create([
            'public_id' => $publicId,
            'application_id' => $application->getKey(),
            'name' => $validated['name'],
            'code' => 'ct_'.strtolower(substr($publicId, -12)),
            'note' => $validated['note'] ?? null,
            'sort_order' => (int) $validated['sort_order'],
            'purpose' => $application->mode === 'card_login' ? 'login_credential' : 'account_recharge',
            'benefit_kind' => 'duration',
            'duration_seconds' => $durationSeconds,
            'duration_value' => (int) $validated['duration_value'],
            'duration_unit' => $validated['duration_unit'],
            'points_amount' => null,
            'fixed_expires_at' => null,
            'face_price_cents' => (int) round(((float) $validated['face_price']) * 100),
            'default_agent_price_cents' => (int) round(((float) $validated['agent_price']) * 100),
            'balance_recharge_cents' => (int) round(((float) $validated['balance_recharge']) * 100),
            'code_prefix' => $codePrefix,
            'code_length' => (int) $validated['code_length'],
            'code_charset' => $validated['code_charset'],
            'alphabet' => self::CARD_ALPHABETS[$validated['code_charset']],
            'secret_length' => (int) ($validated['secret_length'] ?? 0),
            'secret_charset' => $validated['secret_charset'] ?? 'mixed',
            'secret_alphabet' => self::CARD_ALPHABETS[$validated['secret_charset'] ?? 'mixed'],
            'account_concurrency_limit' => (int) $validated['account_concurrency_limit'],
            'machine_concurrency_limit' => (int) $validated['machine_concurrency_limit'],
            'status' => 'active',
            'max_activations' => 1,
        ]);

        $this->audit->record(new AuditEvent(
            action: 'card_type.created',
            targetType: 'card_type',
            targetId: (string) $cardType->public_id,
            applicationId: (int) $application->getKey(),
            after: $cardType->only([
                'name',
                'code',
                'purpose',
                'duration_seconds',
                'code_length',
                'secret_length',
            ]),
            actorRole: $this->administrator()->role,
        ), $request);

        return redirect()
            ->route('admin.software-card-types.index', [
                'adminPath' => $adminPath,
                'application' => $application,
            ])
            ->with('success', '充值卡类型创建成功。');
    }

    public function updateType(Request $request, string $adminPath, CardType $cardType): RedirectResponse
    {
        $validated = $this->validateCardType($request);
        abort_unless((int) $validated['application_id'] === $cardType->application_id, 404);
        $application = $cardType->application()->firstOrFail();
        $codePrefix = (string) ($validated['code_prefix'] ?? '');
        if ((int) $validated['code_length'] - strlen($codePrefix) < 8) {
            return back()->withErrors(['code_length' => '卡号长度至少需要比开头字符多 8 位。'])->withInput();
        }

        $cardType->fill([
            'name' => $validated['name'], 'note' => $validated['note'] ?? null,
            'sort_order' => (int) $validated['sort_order'],
            'duration_seconds' => $this->durationSeconds((int) $validated['duration_value'], $validated['duration_unit']),
            'duration_value' => (int) $validated['duration_value'], 'duration_unit' => $validated['duration_unit'],
            'face_price_cents' => (int) round(((float) $validated['face_price']) * 100),
            'default_agent_price_cents' => (int) round(((float) $validated['agent_price']) * 100),
            'balance_recharge_cents' => (int) round(((float) $validated['balance_recharge']) * 100),
            'code_prefix' => $codePrefix, 'code_length' => (int) $validated['code_length'],
            'code_charset' => $validated['code_charset'], 'alphabet' => self::CARD_ALPHABETS[$validated['code_charset']],
            'secret_length' => (int) ($validated['secret_length'] ?? 0),
            'secret_charset' => $validated['secret_charset'] ?? 'mixed',
            'secret_alphabet' => self::CARD_ALPHABETS[$validated['secret_charset'] ?? 'mixed'],
            'account_concurrency_limit' => (int) $validated['account_concurrency_limit'],
            'machine_concurrency_limit' => (int) $validated['machine_concurrency_limit'],
        ])->save();

        return redirect()->route('admin.software-card-types.index', [
            'adminPath' => $adminPath, 'application' => $application,
        ])->with('success', '充值卡类型已保存。');
    }

    public function quickType(Request $request, string $adminPath): RedirectResponse
    {
        $request->merge([
            'name' => '1天体验卡', 'code_prefix' => 'T', 'note' => '快速添加模拟卡类型', 'sort_order' => 0,
            'duration_value' => 1, 'duration_unit' => 'day', 'face_price' => 1, 'agent_price' => 1,
            'balance_recharge' => 0, 'code_length' => 25, 'code_charset' => 'mixed',
            'secret_length' => 0, 'secret_charset' => 'mixed',
            'account_concurrency_limit' => 0, 'machine_concurrency_limit' => 0,
        ]);

        return $this->storeType($request, $adminPath);
    }

    public function deleteTypes(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'card_type_ids' => ['required', 'array', 'min:1'], 'card_type_ids.*' => ['integer'],
        ]);
        $application = Application::query()->findOrFail($validated['application_id']);
        $types = $application->cardTypes()->whereKey($validated['card_type_ids'])->get();
        $typeIds = $types->modelKeys();
        $deletedCards = 0;
        $deletedBatches = 0;
        $deletedTypes = 0;
        $retainedTypes = 0;
        $retainedDetails = [];

        DB::transaction(function () use ($types, &$deletedBatches, &$deletedCards, &$deletedTypes, &$retainedTypes, &$retainedDetails): void {
            foreach ($types as $type) {
                // Inventory and issued cards are still removable. Active,
                // expired and consumed cards remain available for history and
                // to keep their card type relationship valid.
                $removableCardIds = Card::query()
                    ->where('card_type_id', $type->getKey())
                    ->whereIn('status', ['inventory', 'issued'])
                    ->whereNull('activated_at')
                    ->pluck('id');

                if ($removableCardIds->isNotEmpty()) {
                    DB::table('manual_issue_items')->whereIn('card_id', $removableCardIds)->delete();
                    $deletedCards += CardOperationLogger::deleteCards(Card::query()->whereIn('id', $removableCardIds));
                }

                $remainingCards = Card::query()->where('card_type_id', $type->getKey())->count();
                if ($remainingCards === 0) {
                    $deletedBatches += CardBatch::query()->where('card_type_id', $type->getKey())->delete();
                } else {
                    $retainedTypes++;
                    $retainedDetails[] = "{$type->name}（{$remainingCards} 张已激活或已使用卡）";
                }

                $deletedTypes += $type->delete() ? 1 : 0;
            }
        }, 3);

        $this->audit->record(new AuditEvent(
            action: 'card_types.deleted',
            targetType: 'application',
            targetId: (string) $application->public_id,
            applicationId: (int) $application->getKey(),
            before: ['card_types' => $types->map->only(['id', 'public_id', 'name', 'code'])->all()],
            after: [
                'deleted_card_types' => $deletedTypes,
                'deleted_cards' => $deletedCards,
                'deleted_batches' => $deletedBatches,
                'retained_card_types' => $retainedTypes,
                'retained_details' => $retainedDetails,
            ],
            actorRole: $this->administrator()->role,
        ), $request);

        $retainedMessage = $retainedDetails === []
            ? ''
            : '保留明细：'.implode('、', $retainedDetails).'。';

        return back()->with('success', "已删除 {$deletedTypes} 个卡串类型，清理 {$deletedCards} 张未激活充值卡；保留 {$retainedTypes} 个已删除类型下的已激活或已使用卡供查询。{$retainedMessage}");
    }

    /** @return array<string, mixed> */
    private function validateCardType(Request $request): array
    {
        return $request->validate([
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'code_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'note' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'duration_value' => ['required', 'integer', 'min:1', 'max:315360000'],
            'duration_unit' => ['required', Rule::in(['minute', 'hour', 'day', 'month', 'year'])],
            'face_price' => ['required', 'numeric', 'min:-1', 'max:999999'],
            'agent_price' => ['required', 'numeric', 'min:-1', 'max:999999'],
            'balance_recharge' => ['required', 'numeric', 'min:-999999', 'max:999999'],
            'code_length' => ['required', 'integer', 'min:10', 'max:64'],
            'code_charset' => ['required', Rule::in(array_keys(self::CARD_ALPHABETS))],
            'secret_length' => ['required', 'integer', 'min:0', 'max:64'],
            'secret_charset' => ['sometimes', Rule::in(array_keys(self::CARD_ALPHABETS))],
            'account_concurrency_limit' => ['required', 'integer', 'min:-1', 'max:32767'],
            'machine_concurrency_limit' => ['required', 'integer', 'min:-1', 'max:32767'],
        ]);
    }

    public function generateBatch(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'card_type_id' => ['required', 'integer', 'exists:card_types,id'],
            'generation_mode' => ['required', Rule::in(['automatic'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'sale_flag' => ['sometimes', Rule::in(['normal', 'sale'])],
            'note' => ['nullable', 'string', 'max:500'],
            'maker_reference' => ['nullable', 'string', 'max:120'],
        ]);
        $cardType = CardType::query()->findOrFail($validated['card_type_id']);

        try {
            $result = $this->generator->generate(
                $cardType,
                (int) $validated['quantity'],
                $this->administrator(),
                $validated['note'] ?? null,
                $validated['sale_flag'] ?? 'normal',
                $validated['maker_reference'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()])->withInput();
        }

        $this->audit->record(new AuditEvent(
            action: 'card_batch.generated',
            targetType: 'card_batch',
            targetId: (string) $result['batch']->public_id,
            applicationId: (int) $cardType->application_id,
            after: [
                'batch_no' => $result['batch']->batch_no,
                'quantity' => count($result['credentials']),
                'application_name' => $cardType->application?->name,
                'card_type_name' => $cardType->name,
            ],
            actorRole: $this->administrator()->role,
        ), $request);

        return redirect()
            ->route('admin.software-card-making.show', [
                'adminPath' => $adminPath,
                'application' => $cardType->application,
            ])
            ->with('generated_cards', $result['credentials'])
            ->with('generated_batch', [
                'batch_no' => $result['batch']->batch_no,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'card_type' => $cardType->name,
                'sale_flag' => $validated['sale_flag'] ?? 'normal',
            ]);
    }

    public function issues(string $adminPath): View
    {
        return view('admin.cards.issues', [
            ...$this->layout($adminPath),
            'cardTypes' => CardType::query()
                ->with('application')
                ->withCount(['cards as inventory_count' => fn ($query) => $query->where('status', 'inventory')])
                ->orderBy('name')
                ->get(),
            'issues' => ManualIssue::query()->with(['application', 'items.card'])->latest()->paginate(20),
            'issuedCredentials' => session('issued_credentials', []),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function storeIssue(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'card_type_id' => ['required', 'integer', 'exists:card_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'recipient' => ['required', 'string', 'min:1', 'max:160'],
            'idempotency_key' => ['required', 'uuid'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $cardType = CardType::query()->findOrFail($validated['card_type_id']);

        try {
            $issue = $this->issuer->issueOffline(
                $cardType,
                (int) $validated['quantity'],
                $validated['recipient'],
                $validated['idempotency_key'],
                $validated['note'] ?? null,
                $this->administrator(),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()])->withInput();
        }

        $credentials = $issue->items->map(
            static fn ($item): array => [
                'code' => (string) $item->card->code_ciphertext,
                'secret' => (string) $item->card->secret_ciphertext,
            ],
        )->values()->all();

        $this->audit->record(new AuditEvent(
            action: 'cards.issued',
            targetType: 'manual_issue',
            targetId: (string) $issue->public_id,
            applicationId: (int) $issue->application_id,
            after: [
                'issue_no' => $issue->issue_no,
                'quantity' => $issue->quantity,
                'recipient' => $issue->offline_recipient_ciphertext,
            ],
            actorRole: $this->administrator()->role,
        ), $request);

        return redirect()
            ->route('admin.card-issues.index', ['adminPath' => $adminPath])
            ->with('success', '手动发卡成功，卡密已从库存转为已发出。')
            ->with('issued_credentials', $credentials);
    }

    /**
     * @return array<string, mixed>
     */
    private function layout(string $adminPath): array
    {
        return [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $this->administrator(),
            'databaseHealthy' => true,
        ];
    }

    private function administrator(): Administrator
    {
        $administrator = Auth::guard('admin')->user();

        abort_unless($administrator instanceof Administrator, 401);

        return $administrator;
    }

    private function durationSeconds(int $value, string $unit): int
    {
        $multiplier = match ($unit) {
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400,
            'month' => 2592000,
            'year' => 31536000,
            default => throw new RuntimeException('充值卡期限单位配置错误。'),
        };

        return $value * $multiplier;
    }
}
