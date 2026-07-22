<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AdminSystemSettingController extends Controller
{
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
        private readonly AuditLogger $audit,
    ) {}

    public function index(string $adminPath): View
    {
        $settings = SystemSetting::query()->whereIn('key', [
            'app_name', 'app_url', 'app_timezone', 'client_service_enabled', 'client_service_message',
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
                'client_service_enabled' => (string) ($settings['client_service_enabled'] ?? '1'),
                'client_service_message' => (string) ($settings['client_service_message'] ?? '系统维护中，请稍后再试。'),
            ],
            'timezones' => self::TIMEZONES,
            'serverTime' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function update(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'min:2', 'max:80'],
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'app_timezone' => ['required', 'timezone'],
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
                'group' => $key === 'client_service_enabled' || $key === 'client_service_message' ? 'service' : 'site',
                'updated_at' => $now,
            ]);
        }

        config([
            'app.name' => $validated['app_name'],
            'app.url' => rtrim($validated['app_url'], '/'),
            'app.timezone' => $validated['app_timezone'],
        ]);
        date_default_timezone_set($validated['app_timezone']);
        $this->audit->record(new AuditEvent(
            action: 'system.settings_updated',
            targetType: 'system_setting',
            targetId: 'system',
            before: $before,
            after: [...$validated, 'client_service_message' => '[updated]'],
            actorRole: $this->actor()->role,
        ), $request);

        return back()->with('success', '系统信息设置已保存。');
    }

    private function actor(): Administrator
    {
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return $administrator;
    }
}
