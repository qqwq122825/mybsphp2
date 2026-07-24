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
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AdminSystemSettingController extends Controller
{
    private const RESERVED_ADMIN_PATHS = [
        'activation-query', 'agent', 'api', 'assets', 'demo', 'install', 'storage', 'up', 'vendor',
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
            'app_name', 'app_url', 'app_timezone', 'admin_path', 'operational_mode', 'client_service_enabled', 'client_service_message',
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
                'operational_mode' => (string) ($settings['operational_mode'] ?? 'production'),
                'client_service_enabled' => (string) ($settings['client_service_enabled'] ?? '1'),
                'client_service_message' => (string) ($settings['client_service_message'] ?? '系统维护中，请稍后再试。'),
            ],
            'timezones' => self::TIMEZONES,
            'serverTime' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function update(Request $request, string $adminPath): RedirectResponse
    {
        $request->merge([
            'admin_path' => strtolower(trim((string) $request->input('admin_path'), "/ \t\n\r\0\x0B")),
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
                Rule::notIn(self::RESERVED_ADMIN_PATHS),
            ],
            'operational_mode' => ['required', Rule::in(['production', 'debug'])],
            'client_service_enabled' => ['required', 'boolean'],
            'client_service_message' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $before = SystemSetting::query()->whereIn('key', array_keys($validated))->pluck('value', 'key')->all();
        $types = ['client_service_enabled' => 'boolean'];
        $now = now();
        foreach ($validated as $key => $value) {
            SystemSetting::query()->updateOrCreate(['key' => $key], [
                'value' => (string) $value,
                'type' => $types[$key] ?? 'string',
                'group' => match ($key) {
                    'client_service_enabled', 'client_service_message' => 'service',
                    'admin_path', 'operational_mode' => 'security',
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

        $newAdminPath = $validated['admin_path'];
        if (! hash_equals($this->installation->adminPath(), $newAdminPath)) {
            $this->environment->update(['ADMIN_PATH' => $newAdminPath]);
            $this->installation->updateMetadata(['admin_path' => $newAdminPath]);
            config(['mybsphp.admin_path' => $newAdminPath]);
        }

        $this->audit->record(new AuditEvent(
            action: 'system.settings_updated',
            targetType: 'system_setting',
            targetId: 'system',
            before: $before,
            after: [...$validated, 'client_service_message' => '[updated]'],
            actorRole: $this->actor()->role,
        ), $request);

        return redirect()
            ->route('admin.settings.system.index', ['adminPath' => $newAdminPath])
            ->with('success', $adminPath === $newAdminPath
                ? '系统信息设置已保存。'
                : "系统信息设置已保存，后台入口已更新为 /{$newAdminPath}。");
    }

    private function actor(): Administrator
    {
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return $administrator;
    }
}
