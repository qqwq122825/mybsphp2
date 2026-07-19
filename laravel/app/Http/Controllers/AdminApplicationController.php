<?php

namespace App\Http\Controllers;

use App\Applications\ApplicationCredentialService;
use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Auth\AdminPermission;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    ) {}

    public function index(string $adminPath): View
    {
        $applications = Application::query()
            ->withCount([
                'cardTypes',
                'cards',
                'cards as activated_cards_count' => fn ($query) => $query->whereNotNull('activated_at'),
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
        $application->legacyConfig()->create();

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
            'bind_device_enabled',
            'max_bound_devices',
            'max_concurrent_sessions',
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
        $validated['bind_device_enabled'] = (bool) ($validated['bind_device_enabled'] ?? false);
        unset($validated['verification_data']);

        $application->fill($validated)->save();

        $legacy = $request->validate([
            'legacy_enabled' => ['nullable', 'boolean'],
            'login_api_name' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z_][A-Za-z0-9_.-]{0,63}$/'],
            'max_clock_skew_seconds' => ['nullable', 'integer', 'min:30', 'max:3600'],
        ]);
        $application->legacyConfig()->updateOrCreate(
            ['application_id' => $application->getKey()],
            [
                'legacy_enabled' => (bool) ($legacy['legacy_enabled'] ?? false),
                'login_api_name' => $legacy['login_api_name'] ?? 'login.ic',
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
            'status' => ['required', Rule::in(['draft', 'active', 'suspended', 'archived'])],
            'verification_data' => ['nullable', 'string', 'max:262144'],
            'encryption_mode' => ['required', Rule::in(['hybrid_rsa_aes_hmac'])],
            'bind_device_enabled' => ['nullable', 'boolean'],
            'max_bound_devices' => ['required', 'integer', 'min:1', 'max:20'],
            'max_concurrent_sessions' => ['required', 'integer', 'min:1', 'max:100'],
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
