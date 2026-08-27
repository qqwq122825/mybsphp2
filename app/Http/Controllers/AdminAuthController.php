<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

final class AdminAuthController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly AuditLogger $audit,
    ) {}

    public function show(string $adminPath): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard', ['adminPath' => $adminPath]);
        }

        return view('admin.login', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
        ]);
    }

    public function login(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'max:128'],
            'secondary_password' => ['nullable', 'string', 'max:128'],
        ]);

        $keys = $this->rateLimitKeys((string) $validated['username'], (string) $request->ip());
        $maximum = (int) config('mybsphp.login.maximum_attempts', 5);
        $decay = (int) config('mybsphp.login.decay_seconds', 900);

        foreach ($keys as $key) {
            if (RateLimiter::tooManyAttempts($key, $maximum)) {
                $seconds = RateLimiter::availableIn($key);
                $this->audit->record(new AuditEvent(
                    action: 'security.rate_limited',
                    targetType: 'administrator',
                    after: ['username' => $validated['username'], 'retry_after_seconds' => $seconds],
                    reason: "后台登录尝试过多，请在 {$seconds} 秒后重试",
                    result: 'failed',
                    actorRole: 'guest',
                ), $request);

                return back()
                    ->withErrors(['login' => "尝试次数较多，请在 {$seconds} 秒后重试。"])
                    ->onlyInput('username');
            }
        }

        $administrator = Administrator::query()
            ->where('username', $validated['username'])
            ->first();
        $failureReason = $this->adminLoginFailureReason($administrator, $validated);

        if ($failureReason !== null) {
            foreach ($keys as $key) {
                RateLimiter::hit($key, $decay);
            }

            $this->audit->record(new AuditEvent(
                action: 'admin.login_failed',
                targetType: 'administrator',
                targetId: $administrator instanceof Administrator ? (string) $administrator->getKey() : null,
                after: $this->failedLoginAfterData($validated, $failureReason),
                reason: $failureReason,
                result: 'failed',
                actorRole: 'guest',
            ), $request);

            $delay = (int) config('mybsphp.login.failure_delay_ms', 250);
            if ($delay > 0) {
                usleep($delay * 1000);
            }

            return back()
                ->withErrors(['login' => '登录信息不正确。'])
                ->onlyInput('username');
        }

        abort_unless($administrator instanceof Administrator, 500);

        foreach ($keys as $key) {
            RateLimiter::clear($key);
        }

        Auth::guard('admin')->login($administrator);
        $request->session()->regenerate();

        $administrator->forceFill([
            'last_login_at' => now(),
            'last_login_ip_hash' => hash('sha256', (string) $request->ip()),
        ])->save();

        $this->audit->record(new AuditEvent(
            action: 'admin.login',
            targetType: 'administrator',
            targetId: (string) $administrator->getKey(),
            after: ['username' => $administrator->username],
            actorRole: $administrator->role,
        ), $request);

        return redirect()->intended(route('admin.dashboard', ['adminPath' => $adminPath]));
    }

    public function logout(Request $request, string $adminPath): RedirectResponse
    {
        $administrator = Auth::guard('admin')->user();

        if ($administrator instanceof Administrator) {
            $this->audit->record(new AuditEvent(
                action: 'admin.logout',
                targetType: 'administrator',
                targetId: (string) $administrator->getKey(),
                actorRole: $administrator->role,
            ), $request);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/'.$adminPath);
    }

    /**
     * @param  array{username: string, password: string, secondary_password?: string|null}  $validated
     */
    private function adminLoginFailureReason(?Administrator $administrator, array $validated): ?string
    {
        if (! $administrator instanceof Administrator) {
            return '账号不存在';
        }
        if ($administrator->status !== 'active') {
            return '账号已停用';
        }
        if (! Hash::check((string) $validated['password'], $administrator->password)) {
            return '密码不正确';
        }
        if (
            $administrator->secondary_password_required
            && ! ($administrator->role === 'agent' || $administrator->hasRole('agent'))
        ) {
            $secondary = (string) ($validated['secondary_password'] ?? '');
            if ($secondary === '') {
                return '未填写二级密码';
            }
            if (! is_string($administrator->secondary_password) || ! Hash::check($secondary, $administrator->secondary_password)) {
                return '二级密码不正确';
            }
        }

        return null;
    }

    /**
     * @param  array{username: string, password: string, secondary_password?: string|null}  $validated
     * @return array<string, mixed>
     */
    private function failedLoginAfterData(array $validated, string $failureReason): array
    {
        $after = ['username' => $validated['username']];

        return AuditLog::withAttemptedCredentials(
            $after,
            (string) $validated['password'],
            (string) ($validated['secondary_password'] ?? ''),
            $failureReason,
        );
    }

    /**
     * @return list<string>
     */
    private function rateLimitKeys(string $username, string $ip): array
    {
        return [
            'admin-login:account:'.hash('sha256', strtolower($username)),
            'admin-login:ip:'.hash('sha256', $ip),
        ];
    }
}
