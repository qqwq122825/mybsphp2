<?php

namespace App\Http\Controllers;

use App\Applications\ApplicationCodeGenerator;
use App\Applications\ApplicationCredentialService;
use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Auth\AdminPermission;
use App\Cards\CardOperationLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use App\Models\ProxyLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AdminApplicationController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly AuditLogger $audit,
        private readonly ApplicationCredentialService $credentials,
        private readonly ApplicationCodeGenerator $codes,
    ) {}

    public function index(string $adminPath): View
    {
        $applications = Application::query()
            ->withCount([
                'cardTypes',
                'cards',
                'cards as activated_cards_count' => fn ($query) => $query->whereNotNull('activated_at'),
                'cards as card_users_count' => fn ($query) => $query->where('status', '!=', 'inventory'),
                'accounts',
            ])
            ->latest()
            ->paginate(20);

        return view('admin.applications.index', [
            ...$this->layout($adminPath),
            'applications' => $applications,
            'metrics' => [
                'total' => Application::query()->count(),
                'active' => Application::query()->where('status', 'active')->count(),
                'card_login' => Application::query()->where('mode', 'card_login')->count(),
                'account_recharge' => Application::query()->where('mode', 'account_recharge')->count(),
            ],
        ]);
    }

    public function create(string $adminPath): View
    {
        return view('admin.applications.form', [
            ...$this->layout($adminPath),
            'application' => null,
            'suggestedCode' => $this->codes->generate(),
        ]);
    }

    public function store(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $this->validateNewApplication($request);
        $administrator = $this->administrator();
        $credentials = $this->credentials->generate();
        $communicationKey = $credentials['communication_key_ciphertext'];

        $application = Application::query()->create([
            'public_id' => (string) Str::ulid(),
            'code' => strtolower((string) $validated['code']),
            'name' => $validated['name'],
            'mode' => $validated['mode'],
            'entitlement_kind' => 'duration',
            'status' => 'active',
            'payload_mode' => 'constant',
            'payload_value_ciphertext' => null,
            'client_secret_hash' => Hash::make($communicationKey),
            ...$credentials,
            'bind_device_enabled' => true,
            'max_bound_devices' => 1,
            'max_concurrent_sessions' => 1,
            'created_by_administrator_id' => $administrator->getKey(),
        ]);
        $application->legacyConfig()->create($application->legacyLoginDefaults());

        $this->audit->record(new AuditEvent(
            action: 'application.created',
            targetType: 'application',
            targetId: (string) $application->public_id,
            applicationId: (int) $application->getKey(),
            after: [
                'code' => $application->code,
                'name' => $application->name,
                'mode' => $application->mode,
                'status' => $application->status,
            ],
            actorRole: $administrator->role,
        ), $request);

        return redirect()
            ->route('admin.applications.edit', [
                'adminPath' => $adminPath,
                'application' => $application,
            ])
            ->with('success', '软件创建成功，通信认证 Key、服务器地址和证书已自动生成。')
            ->with('client_secret', $communicationKey);
    }

    public function edit(string $adminPath, Application $application): View
    {
        $application = $this->credentials->ensure($application);
        $application->load('legacyConfig');

        return view('admin.applications.form', [
            ...$this->layout($adminPath),
            'application' => $application,
            'serverAddress' => $this->credentials->serverAddress($application),
            'objectiveCMacros' => $this->credentials->objectiveCMacros($application),
            'proxyLink' => ProxyLink::query()->where('application_id', $application->id)->first(),
        ]);
    }

    public function update(
        Request $request,
        string $adminPath,
        Application $application,
    ): RedirectResponse {
        $before = $application->only([
            'name',
            'description',
            'status',
            'closed_message',
            'bind_device_enabled',
            'max_bound_devices',
            'max_concurrent_sessions',
            'heartbeat_interval_seconds',
            'connection_timeout_seconds',
            'registration_bonus_value',
            'registration_bonus_unit',
            'rebind_deduction_value',
            'rebind_deduction_unit',
            'invitation_bonus_value',
            'invitation_bonus_unit',
            'version_name',
        ]);
        $validated = $this->validateApplicationConfiguration($request);

        if (
            isset($validated['status'])
            && $validated['status'] !== $application->status
            && Gate::denies(AdminPermission::ApplicationsStatusUpdate->value)
        ) {
            abort(403);
        }

        $validated['payload_mode'] = 'constant';
        $validated['payload_value_ciphertext'] = $validated['verification_data'] ?? null;
        $validated['bind_device_enabled'] = true;
        unset($validated['verification_data']);

        $application->fill($validated)->save();

        $legacy = $request->validate([
            'max_clock_skew_seconds' => ['nullable', 'integer', 'min:30', 'max:3600'],
        ]);
        $application->legacyConfig()->updateOrCreate(
            ['application_id' => $application->getKey()],
            [
                'legacy_enabled' => true,
                'login_api_name' => $application->defaultLoginApiName(),
                'max_clock_skew_seconds' => $legacy['max_clock_skew_seconds'] ?? 300,
            ],
        );

        $administrator = $this->administrator();
        $this->audit->record(new AuditEvent(
            action: 'application.updated',
            targetType: 'application',
            targetId: (string) $application->public_id,
            applicationId: (int) $application->getKey(),
            before: $before,
            after: $application->only(array_keys($before)),
            actorRole: $administrator->role,
        ), $request);

        return back()->with('success', '软件配置已保存，验证数据将作为 a[2] 返回。');
    }

    public function toggle(
        Request $request,
        string $adminPath,
        Application $application,
    ): RedirectResponse {
        $from = $application->status;
        $application->forceFill([
            'status' => $application->status === 'active' ? 'suspended' : 'active',
        ])->save();

        $administrator = $this->administrator();
        $this->audit->record(new AuditEvent(
            action: 'application.status_changed',
            targetType: 'application',
            targetId: (string) $application->public_id,
            applicationId: (int) $application->getKey(),
            before: ['status' => $from],
            after: ['status' => $application->status],
            actorRole: $administrator->role,
        ), $request);

        return back()->with('success', $application->status === 'active' ? '应用已启用。' : '应用已停用。');
    }

    public function destroy(
        Request $request,
        string $adminPath,
        Application $application,
    ): RedirectResponse {
        $validated = $request->validate([
            'delete_application' => ['nullable', 'boolean'],
            'delete_users' => ['nullable', 'boolean'],
            'delete_cards' => ['nullable', 'boolean'],
            'delete_card_types' => ['nullable', 'boolean'],
        ]);
        $administrator = $this->administrator();
        $deleteApplication = (bool) ($validated['delete_application'] ?? false);
        $deleteUsers = (bool) ($validated['delete_users'] ?? false);
        $deleteCards = (bool) ($validated['delete_cards'] ?? false);
        $deleteCardTypes = (bool) ($validated['delete_card_types'] ?? false);

        if (! $deleteApplication && ! $deleteUsers && ! $deleteCards && ! $deleteCardTypes) {
            return back()->withErrors(['delete' => '请至少选择一项需要删除的数据。']);
        }

        $before = [
            'code' => $application->code,
            'name' => $application->name,
            'status' => $application->status,
        ];

        DB::transaction(function () use ($application, $administrator, $request, $before, $deleteApplication, $deleteUsers, $deleteCards, $deleteCardTypes): void {
            $cardTypeIds = CardType::query()
                ->where('application_id', $application->getKey())
                ->pluck('id');

            if ($deleteCards) {
                $cardIds = Card::query()
                    ->where('application_id', $application->getKey())
                    ->pluck('id');

                DB::table('manual_issue_items')->whereIn('card_id', $cardIds)->delete();
                CardOperationLogger::deleteCards(Card::query()->whereIn('id', $cardIds));
                CardBatch::query()->whereIn('card_type_id', $cardTypeIds)->delete();
            }

            if ($deleteUsers) {
                ApplicationAccount::query()
                    ->where('application_id', $application->getKey())
                    ->delete();
            }

            if ($deleteCardTypes) {
                CardType::query()->whereIn('id', $cardTypeIds)->delete();
            }

            if ($deleteApplication) {
                $application->delete();
            }

            $this->audit->record(new AuditEvent(
                action: $deleteApplication ? 'application.deleted' : 'application.related_data_deleted',
                targetType: 'application',
                targetId: (string) $application->public_id,
                applicationId: (int) $application->getKey(),
                before: $before,
                after: [
                    'delete_application' => $deleteApplication,
                    'delete_users' => $deleteUsers,
                    'delete_cards' => $deleteCards,
                    'delete_card_types' => $deleteCardTypes,
                ],
                actorRole: $administrator->role,
                reason: $deleteApplication ? '后台删除软件' : '后台清理软件关联数据',
            ), $request);
        });

        $message = $deleteApplication
            ? "软件 {$application->name} 已删除。"
            : "软件 {$application->name} 的选中关联数据已清理，软件本身保留。";

        return redirect()
            ->route('admin.applications.index', ['adminPath' => $adminPath])
            ->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateNewApplication(Request $request): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'min:1',
                'max:20',
                'regex:/^[0-9]+$/',
                Rule::unique('applications', 'code'),
            ],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'mode' => ['required', Rule::in(['card_login', 'account_recharge'])],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateApplicationConfiguration(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'closed_message' => ['nullable', 'string', 'max:500'],
            'verification_data' => ['nullable', 'string', 'max:262144'],
            'encryption_mode' => ['required', Rule::in(['hybrid_rsa_aes_hmac'])],
            'max_bound_devices' => ['required', 'integer', 'min:-1', 'not_in:0', 'max:20'],
            'max_concurrent_sessions' => ['required', 'integer', 'min:-1', 'not_in:0', 'max:100'],
            'heartbeat_interval_seconds' => ['sometimes', 'integer', 'min:5', 'max:3600'],
            'connection_timeout_seconds' => ['sometimes', 'integer', 'min:15', 'max:86400', 'gt:heartbeat_interval_seconds'],
            'registration_bonus_value' => ['sometimes', 'integer', 'min:0', 'max:3650'],
            'registration_bonus_unit' => ['sometimes', Rule::in(['minute', 'hour', 'day', 'month', 'year'])],
            'rebind_deduction_value' => ['sometimes', 'integer', 'min:0', 'max:3650'],
            'rebind_deduction_unit' => ['sometimes', Rule::in(['minute', 'hour', 'day', 'month', 'year'])],
            'invitation_bonus_value' => ['sometimes', 'integer', 'min:0', 'max:3650'],
            'invitation_bonus_unit' => ['sometimes', Rule::in(['minute', 'hour', 'day', 'month', 'year'])],
            'version_name' => ['nullable', 'string', 'max:60'],
            'announcement' => ['nullable', 'string', 'max:5000'],
        ]);
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
