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

    public function storeType(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z][A-Za-z0-9_-]+$/'],
            'benefit_kind' => ['required', Rule::in(['duration', 'points', 'fixed_expiry'])],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'points_amount' => ['nullable', 'integer', 'min:1', 'max:1000000000'],
            'fixed_expires_at' => ['nullable', 'date', 'after:now'],
            'face_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'agent_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'code_prefix' => ['required', 'string', 'min:1', 'max:8', 'regex:/^[A-Za-z0-9]+$/'],
            'code_length' => ['required', 'integer', 'min:16', 'max:40'],
            'secret_length' => ['required', 'integer', 'min:6', 'max:24'],
        ]);
        $application = Application::query()->findOrFail($validated['application_id']);

        $durationSeconds = $validated['benefit_kind'] === 'duration'
            ? ((int) ($validated['duration_days'] ?? 0)) * 86400
            : null;
        $points = $validated['benefit_kind'] === 'points'
            ? (int) ($validated['points_amount'] ?? 0)
            : null;
        $fixed = $validated['benefit_kind'] === 'fixed_expiry'
            ? $validated['fixed_expires_at']
            : null;

        if (
            ($validated['benefit_kind'] === 'duration' && $durationSeconds < 1)
            || ($validated['benefit_kind'] === 'points' && $points < 1)
            || ($validated['benefit_kind'] === 'fixed_expiry' && $fixed === null)
        ) {
            return back()->withErrors(['benefit_kind' => '请填写当前权益类型对应的有效值。'])->withInput();
        }

        $cardType = CardType::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $application->getKey(),
            'name' => $validated['name'],
            'code' => strtolower($validated['code']),
            'purpose' => $application->mode === 'card_login' ? 'login_credential' : 'account_recharge',
            'benefit_kind' => $validated['benefit_kind'],
            'duration_seconds' => $durationSeconds,
            'points_amount' => $points,
            'fixed_expires_at' => $fixed,
            'face_price_cents' => (int) round(((float) ($validated['face_price'] ?? 0)) * 100),
            'default_agent_price_cents' => (int) round(((float) ($validated['agent_price'] ?? 0)) * 100),
            'code_prefix' => strtoupper($validated['code_prefix']),
            'code_length' => $validated['code_length'],
            'secret_length' => $validated['secret_length'],
            'status' => 'active',
            'max_activations' => 1,
        ]);

        $this->audit->record(new AuditEvent(
            action: 'card_type.created',
            targetType: 'card_type',
            targetId: (string) $cardType->public_id,
            applicationId: (int) $application->getKey(),
            after: $cardType->only(['name', 'code', 'purpose', 'benefit_kind']),
            actorRole: $this->administrator()->role,
        ), $request);

        return redirect()
            ->to(route('admin.cards.index', [
                'adminPath' => $adminPath,
                'application' => $application->getKey(),
            ]).'#card-types')
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
            ->route('admin.applications.cards.make', [
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
}
