<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Install\InstallationManager;
use App\Models\Administrator;
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
            'secondaryPasswordEnabled' => $this->installation->secondaryPasswordEnabled(),
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

                return back()
                    ->withErrors(['login' => "尝试次数较多，请在 {$seconds} 秒后重试。"])
                    ->onlyInput('username');
            }
        }

        $administrator = Administrator::query()
            ->where('username', $validated['username'])
            ->where('status', 'active')
            ->first();

        $valid = $administrator !== null
            && Hash::check((string) $validated['password'], $administrator->password);

        if (
            $valid
            && $administrator->secondary_password_required
            && $this->installation->secondaryPasswordEnabled()
        ) {
            $secondary = (string) ($validated['secondary_password'] ?? '');
            $valid = $secondary !== ''
                && is_string($administrator->secondary_password)
                && Hash::check($secondary, $administrator->secondary_password);
        }

        if (! $valid || $administrator === null) {
            foreach ($keys as $key) {
                RateLimiter::hit($key, $decay);
            }

            $this->audit->record(new AuditEvent(
                action: 'admin.login_failed',
                targetType: 'administrator',
                targetId: $administrator === null ? null : (string) $administrator->getKey(),
                after: ['username' => $validated['username']],
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
