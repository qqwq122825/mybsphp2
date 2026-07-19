<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Cards\CardGenerationService;
use App\Cards\ManualIssueService;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use App\Models\ManualIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
                ->orderBy('name')
                ->get(),
            'generatedCards' => session('generated_cards', []),
        ]);
    }

    public function types(string $adminPath, Application $application): View
    {
        return view('admin.cards.types', [
            ...$this->layout($adminPath),
            'application' => $application,
            'cardTypes' => $application->cardTypes()
                ->withCount('cards')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeType(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
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
            'secret_charset' => ['required', Rule::in(array_keys(self::CARD_ALPHABETS))],
            'account_concurrency_limit' => ['required', 'integer', 'min:-1', 'max:32767'],
            'machine_concurrency_limit' => ['required', 'integer', 'min:-1', 'max:32767'],
        ]);
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
            'secret_length' => (int) $validated['secret_length'],
            'secret_charset' => $validated['secret_charset'],
            'secret_alphabet' => self::CARD_ALPHABETS[$validated['secret_charset']],
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

    public function generateBatch(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'card_type_id' => ['required', 'integer', 'exists:card_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $cardType = CardType::query()->findOrFail($validated['card_type_id']);

        try {
            $result = $this->generator->generate(
                $cardType,
                (int) $validated['quantity'],
                $this->administrator(),
                $validated['note'] ?? null,
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
            ],
            actorRole: $this->administrator()->role,
        ), $request);

        return redirect()
            ->route('admin.software-card-making.show', [
                'adminPath' => $adminPath,
                'application' => $cardType->application,
            ])
            ->with('success', '卡密批次生成成功，明文仅在本次页面展示。')
            ->with('generated_cards', $result['credentials']);
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
                'recipient' => $issue->recipient_display_mask,
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
