<?php

namespace App\Http\Controllers;

use App\Install\EnvironmentChecker;
use App\Install\InstallationException;
use App\Install\InstallationManager;
use App\Install\InstallerService;
use App\Install\ReinstallationTokenManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class InstallController extends Controller
{
    private const STEPS = ['requirements', 'database', 'site', 'administrator', 'complete'];

    public function __construct(
        private readonly InstallationManager $installation,
        private readonly EnvironmentChecker $environment,
        private readonly InstallerService $installer,
    ) {}

    public function show(Request $request, string $step = 'requirements'): View|RedirectResponse
    {
        $step = in_array($step, self::STEPS, true) ? $step : 'requirements';
        $summary = $request->session()->get('install.completed');
        $reinstallationAuthorized = $request->session()->get('install.reinstallation_authorized') === true;

        if ($this->installation->isInstalled()) {
            if ($reinstallationAuthorized && $step === 'requirements') {
                return redirect()->route('install.show', ['step' => 'database']);
            }

            if (! $reinstallationAuthorized && $step !== 'complete') {
                abort(404);
            }

            if (! is_array($summary)) {
                $metadata = $this->installation->metadata();
                $summary = [
                    'app_name' => $this->installation->applicationName(),
                    'admin_path' => $this->installation->adminPath(),
                    'username' => (string) ($metadata['admin_username'] ?? 'admin'),
                    'secondary_password_enabled' => $this->installation->secondaryPasswordEnabled(),
                ];
            }
        }

        if ($step === 'site' && ! $request->session()->has('install.database')) {
            return redirect()->route('install.show', ['step' => 'database']);
        }

        if ($step === 'administrator' && ! $request->session()->has('install.site')) {
            return redirect()->route('install.show', ['step' => 'site']);
        }

        if ($step === 'complete' && ! is_array($summary)) {
            return redirect()->route('install.show');
        }

        $checks = $this->environment->checks();

        return view('install.wizard', [
            'step' => $step,
            'steps' => self::STEPS,
            'checks' => $checks,
            'hasBlockingRequirement' => $this->environment->hasBlockingFailure($checks),
            'database' => $request->session()->get('install.database', []),
            'site' => $request->session()->get('install.site', []),
            'summary' => $summary,
            'timezones' => ['Asia/Shanghai', 'Asia/Hong_Kong', 'Asia/Taipei', 'UTC'],
        ]);
    }

    public function authorizeReinstallation(
        Request $request,
        ReinstallationTokenManager $tokens,
    ): RedirectResponse {
        if (! $this->installation->isInstalled()) {
            abort(404);
        }

        $token = (string) $request->query('token', '');

        if (! $tokens->consume($token)) {
            abort(404);
        }

        $request->session()->regenerate();
        $request->session()->forget([
            'install.database',
            'install.site',
            'install.current_step',
            'install.completed',
        ]);
        $request->session()->put('install.reinstallation_authorized', true);
        $request->session()->put('install.current_step', 'database');

        return redirect()->route('install.show', ['step' => 'database']);
    }

    public function requirements(Request $request): RedirectResponse
    {
        $checks = $this->environment->checks();

        if ($this->environment->hasBlockingFailure($checks)) {
            return back()->withErrors(['requirements' => '必要环境项目尚未全部通过。']);
        }

        $request->session()->put('install.current_step', 'database');

        return redirect()->route('install.show', ['step' => 'database']);
    }

    public function database(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'regex:/^[A-Za-z0-9_]{1,64}$/'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:512'],
            'create_database' => ['nullable', 'boolean'],
            'overwrite_database' => ['nullable', 'boolean'],
        ]);

        $database = [
            ...$validated,
            'password' => (string) ($validated['password'] ?? ''),
            'create_database' => $request->boolean('create_database'),
            'overwrite_database' => $request->boolean('overwrite_database'),
        ];

        try {
            $probe = $this->installer->testDatabase($database);

            if ($probe['table_count'] > 0 && ! $database['overwrite_database']) {
                return back()
                    ->withErrors(['database' => '数据库已包含数据表，请选择其他数据库或勾选覆盖现有数据库。'])
                    ->withInput($request->except('password'));
            }
        } catch (InstallationException $exception) {
            Log::warning('install.database.failed', [
                'exception' => $exception::class,
                'request_id' => $request->attributes->get('request_id'),
            ]);

            return back()
                ->withErrors(['database' => $exception->getMessage()])
                ->withInput($request->except('password'));
        }

        $database['password'] = Crypt::encryptString($database['password']);
        $request->session()->put('install.database', $database);
        $request->session()->put('install.current_step', 'site');

        return redirect()->route('install.show', ['step' => 'site']);
    }

    public function site(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'app_url' => ['required', 'url', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'admin_path' => ['required', 'regex:/^[A-Za-z0-9_-]{3,48}$/'],
        ]);

        $site = [
            ...$validated,
            'app_name' => trim($validated['app_name']),
            'app_url' => rtrim($validated['app_url'], '/'),
            'admin_path' => strtolower(trim($validated['admin_path'], '/')),
        ];

        try {
            $this->installer->validateSite($site);
        } catch (InstallationException $exception) {
            return back()->withErrors(['site' => $exception->getMessage()])->withInput();
        }

        $request->session()->put('install.site', $site);
        $request->session()->put('install.current_step', 'administrator');

        return redirect()->route('install.show', ['step' => 'administrator']);
    }

    public function complete(Request $request): RedirectResponse
    {
        $database = $request->session()->get('install.database');
        $site = $request->session()->get('install.site');

        if (! is_array($database) || ! is_array($site)) {
            return redirect()->route('install.show');
        }

        $validated = $request->validate([
            'username' => ['required', 'regex:/^[A-Za-z0-9_.-]{3,32}$/'],
            'password' => ['required', 'string', 'min:6', 'max:128', 'confirmed'],
            'secondary_password_enabled' => ['nullable', 'boolean'],
            'secondary_password' => ['nullable', 'string', 'min:6', 'max:128', 'confirmed'],
            'confirm_install' => ['accepted'],
            'confirm_overwrite' => ['nullable', 'boolean'],
            'overwrite_database_name' => ['nullable', 'string', 'max:64'],
        ]);

        $admin = [
            ...$validated,
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
            'secondary_password_enabled' => $request->boolean('secondary_password_enabled'),
            'secondary_password' => (string) ($validated['secondary_password'] ?? ''),
            'secondary_password_confirmation' => (string) $request->input('secondary_password_confirmation', ''),
            'confirm_overwrite' => $request->boolean('confirm_overwrite'),
            'overwrite_database_name' => (string) ($validated['overwrite_database_name'] ?? ''),
        ];

        if ($admin['secondary_password_enabled'] && $admin['secondary_password'] === '') {
            return back()->withErrors(['secondary_password' => '启用二级密码后需要填写并确认二级密码。']);
        }

        try {
            $database['password'] = Crypt::decryptString((string) $database['password']);
            $summary = $this->installer->install(
                $database,
                $site,
                $admin,
                $request->session()->get('install.reinstallation_authorized') === true,
            );
        } catch (InstallationException $exception) {
            Log::error('install.complete.failed', [
                'exception' => $exception::class,
                'previous_exception' => $exception->getPrevious()?->getMessage(),
                'request_id' => $request->attributes->get('request_id'),
            ]);

            return back()->withErrors(['install' => $exception->getMessage()]);
        }

        $request->session()->forget([
            'install.database',
            'install.site',
            'install.current_step',
            'install.reinstallation_authorized',
        ]);
        $request->session()->put('install.completed', $summary);
        $request->session()->regenerate();

        return redirect()->route('install.show', ['step' => 'complete']);
    }
}
