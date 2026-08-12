<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\EnvironmentFileWriter;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AdminSystemSettingController extends Controller
{
    private const RESERVED_ENTRY_PATHS = [
        'activation-query', 'api', 'assets', 'demo', 'install', 'storage', 'up', 'vendor',
    ];

    private const TIMEZONES = [
        'Asia/Shanghai' => '中国标准时间（Asia/Shanghai）',
        'Asia/Hong_Kong' => '中国香港（Asia/Hong_Kong）',
        'Asia/Taipei' => '中国台北（Asia/Taipei）',
        'Asia/Singapore' => '新加坡（Asia/Singapore）',
        'Asia/Tokyo' => '日本东京（Asia/Tokyo）',
        'UTC' => '协调世界时（UTC）',
    ];

    public function __construct(
        private readonly InstallationManager $installation,
        private readonly EnvironmentFileWriter $environment,
        private readonly AuditLogger $audit,
    ) {}

    public function index(string $adminPath): View
    {
        $settings = SystemSetting::query()->whereIn('key', [
            'app_name', 'app_url', 'app_timezone', 'admin_path', 'agent_path', 'operational_mode', 'client_service_enabled', 'client_service_message',
            'card_login_trace_enabled',
        ])->pluck('value', 'key');

        return view('admin.settings.system', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $this->actor(),
            'databaseHealthy' => true,
            'settings' => [
                'app_name' => (string) ($settings['app_name'] ?? $this->installation->applicationName()),
                'app_url' => (string) ($settings['app_url'] ?? config('app.url')),
                'app_timezone' => (string) ($settings['app_timezone'] ?? config('app.timezone', 'Asia/Shanghai')),
                'admin_path' => $this->installation->adminPath(),
                'agent_path' => $this->installation->agentPath(),
                'operational_mode' => (string) ($settings['operational_mode'] ?? 'production'),
                'client_service_enabled' => (string) ($settings['client_service_enabled'] ?? '1'),
                'client_service_message' => (string) ($settings['client_service_message'] ?? '系统维护中，请稍后再试。'),
                'card_login_trace_enabled' => (string) ($settings['card_login_trace_enabled'] ?? '0'),
            ],
            'timezones' => self::TIMEZONES,
            'serverTime' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function update(Request $request, string $adminPath): RedirectResponse
    {
        $request->merge([
            'admin_path' => strtolower(trim((string) $request->input('admin_path'), "/ \t\n\r\0\x0B")),
            'agent_path' => strtolower(trim((string) $request->input('agent_path'), "/ \t\n\r\0\x0B")),
        ]);
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'min:2', 'max:80'],
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'app_timezone' => ['required', 'timezone'],
            'admin_path' => [
                'required',
                'string',
                'min:3',
                'max:48',
                'regex:/^[a-z0-9_-]+$/',
                Rule::notIn(self::RESERVED_ENTRY_PATHS),
                'different:agent_path',
            ],
            'agent_path' => [
                'required',
                'string',
                'min:3',
                'max:48',
                'regex:/^[a-z0-9_-]+$/',
                Rule::notIn(self::RESERVED_ENTRY_PATHS),
                'different:admin_path',
            ],
            'operational_mode' => ['required', Rule::in(['production', 'debug'])],
            'client_service_enabled' => ['required', 'boolean'],
            'client_service_message' => ['required', 'string', 'min:2', 'max:500'],
            'card_login_trace_enabled' => ['required', 'boolean'],
        ]);

        $before = SystemSetting::query()->whereIn('key', array_keys($validated))->pluck('value', 'key')->all();
        $types = ['client_service_enabled' => 'boolean', 'card_login_trace_enabled' => 'boolean'];
        $now = now();
        foreach ($validated as $key => $value) {
            SystemSetting::query()->updateOrCreate(['key' => $key], [
                'value' => (string) $value,
                'type' => $types[$key] ?? 'string',
                'group' => match ($key) {
                    'client_service_enabled', 'client_service_message' => 'service',
                    'card_login_trace_enabled' => 'diagnostics',
                    'admin_path', 'agent_path', 'operational_mode' => 'security',
                    default => 'site',
                },
                'updated_at' => $now,
            ]);
        }

        config([
            'app.name' => $validated['app_name'],
            'app.url' => rtrim($validated['app_url'], '/'),
            'app.timezone' => $validated['app_timezone'],
        ]);
        date_default_timezone_set($validated['app_timezone']);

        $oldAdminPath = $this->installation->adminPath();
        $oldAgentPath = $this->installation->agentPath();
        $newAdminPath = $validated['admin_path'];
        $newAgentPath = $validated['agent_path'];
        $adminPathChanged = ! hash_equals($oldAdminPath, $newAdminPath);
        $agentPathChanged = ! hash_equals($oldAgentPath, $newAgentPath);

        if ($adminPathChanged || $agentPathChanged) {
            $this->environment->update([
                'ADMIN_PATH' => $newAdminPath,
                'AGENT_PATH' => $newAgentPath,
            ]);
            $this->installation->updateMetadata([
                'admin_path' => $newAdminPath,
                'agent_path' => $newAgentPath,
            ]);
            config([
                'mybsphp.admin_path' => $newAdminPath,
                'mybsphp.agent_path' => $newAgentPath,
            ]);
            if ($agentPathChanged && app()->routesAreCached()) {
                Artisan::call('route:clear');
            }
        }

        $this->audit->record(new AuditEvent(
            action: 'system.settings_updated',
            targetType: 'system_setting',
            targetId: 'system',
            before: $before,
            after: [...$validated, 'client_service_message' => '[updated]'],
            actorRole: $this->actor()->role,
        ), $request);

        $entryChanges = [];
        if ($adminPathChanged) {
            $entryChanges[] = "后台入口 /{$newAdminPath}";
        }
        if ($agentPathChanged) {
            $entryChanges[] = "代理入口 /{$newAgentPath}/index.php";
        }

        return redirect()
            ->route('admin.settings.system.index', ['adminPath' => $newAdminPath])
            ->with('success', $entryChanges === []
                ? '系统信息设置已保存。'
                : '系统信息设置已保存，'.implode('，', $entryChanges).' 已生效。');
    }

    private function actor(): Administrator
    {
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return $administrator;
    }
}
