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

final class AgentAuthController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly AuditLogger $audit,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::guard('agent')->check()) {
            return redirect()->route('agent.dashboard');
        }

        $captchaEnabled = (bool) config('mybsphp.agent_login.captcha_enabled', false);
        $languageEnabled = (bool) config('mybsphp.agent_login.language_enabled', false);

        if ($captchaEnabled && ! $request->session()->has('agent_captcha')) {
            $this->renewCaptcha($request);
        } elseif (! $captchaEnabled) {
            $request->session()->forget('agent_captcha');
        }

        return view('agent.login', [
            'appName' => $this->installation->applicationName(),
            'captchaEnabled' => $captchaEnabled,
            'languageEnabled' => $languageEnabled,
            'captcha' => $captchaEnabled ? (string) $request->session()->get('agent_captcha') : null,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $captchaEnabled = (bool) config('mybsphp.agent_login.captcha_enabled', false);
        $languageEnabled = (bool) config('mybsphp.agent_login.language_enabled', false);
        $rules = [
            'username' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'max:128'],
        ];
        if ($captchaEnabled) {
            $rules['captcha'] = ['required', 'string', 'size:4'];
        }
        if ($languageEnabled) {
            $rules['language'] = ['nullable', 'in:zh-CN'];
        }
        $validated = $request->validate($rules);

        $expectedCaptcha = $captchaEnabled
            ? (string) $request->session()->pull('agent_captcha', '')
            : '';
        if ($captchaEnabled && ($expectedCaptcha === '' || ! hash_equals($expectedCaptcha, (string) $validated['captcha']))) {
            $this->renewCaptcha($request);
            $this->audit->record(new AuditEvent(
                action: 'agent.login_failed',
                targetType: 'administrator',
                after: ['username' => $validated['username']],
                reason: '图形验证码不正确',
                result: 'failed',
                actorRole: 'guest',
            ), $request);

            return back()->withErrors(['captcha' => '图形验证码不正确。'])->onlyInput('username');
        }

        $rateKey = 'agent-login:'.hash('sha256', strtolower((string) $validated['username']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            if ($captchaEnabled) {
                $this->renewCaptcha($request);
            }
            $this->audit->record(new AuditEvent(
                action: 'security.rate_limited',
                targetType: 'administrator',
                after: ['username' => $validated['username']],
                reason: '代理登录尝试过多，请稍后重试',
                result: 'failed',
                actorRole: 'guest',
            ), $request);

            return back()->withErrors(['login' => '登录尝试较多，请稍后重试。'])->onlyInput('username');
        }

        $agent = Administrator::query()
            ->where('username', $validated['username'])
            ->first();
        $failureReason = $this->agentLoginFailureReason($agent, (string) $validated['password']);

        if ($failureReason !== null) {
            RateLimiter::hit($rateKey, 900);
            if ($captchaEnabled) {
                $this->renewCaptcha($request);
            }
            $this->audit->record(new AuditEvent(
                action: 'agent.login_failed',
                targetType: 'administrator',
                targetId: $agent instanceof Administrator ? (string) $agent->getKey() : null,
                after: AuditLog::withAttemptedCredentials(
                    ['username' => $validated['username']],
                    (string) $validated['password'],
                    '',
                    $failureReason,
                ),
                reason: $failureReason,
                result: 'failed',
                actorRole: 'guest',
            ), $request);

            return back()->withErrors(['login' => '代理账号或密码不正确。'])->onlyInput('username');
        }

        abort_unless($agent instanceof Administrator, 500);

        RateLimiter::clear($rateKey);
        Auth::guard('agent')->login($agent);
        $request->session()->regenerate();
        $agent->forceFill([
            'last_login_at' => now(),
            'last_login_ip_hash' => hash('sha256', (string) $request->ip()),
        ])->save();
        $this->audit->record(new AuditEvent(
            action: 'agent.login',
            targetType: 'administrator',
            targetId: (string) $agent->getKey(),
            after: ['username' => $agent->username],
            actorRole: 'agent',
        ), $request);

        return redirect()->intended(route('agent.dashboard'));
    }

    public function legacyLogin(Request $request): RedirectResponse
    {
        // 307 preserves a submitted login body for stacks where the legacy
        // virtual route reaches Laravel. GET/HEAD can use the normal 302.
        $status = $request->isMethod('GET') || $request->isMethod('HEAD') ? 302 : 307;

        return redirect()->route('agent.login', status: $status);
    }

    public function logout(Request $request): RedirectResponse
    {
        $agent = Auth::guard('agent')->user();
        if ($agent instanceof Administrator) {
            $this->audit->record(new AuditEvent(
                action: 'agent.logout',
                targetType: 'administrator',
                targetId: (string) $agent->getKey(),
                actorRole: 'agent',
            ), $request);
        }

        Auth::guard('agent')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('agent.login');
    }

    private function agentLoginFailureReason(?Administrator $agent, string $password): ?string
    {
        if (! $agent instanceof Administrator) {
            return '账号不存在';
        }
        if ($agent->status !== 'active') {
            return '账号已停用';
        }
        if (! ($agent->role === 'agent' || $agent->hasRole('agent'))) {
            return '不是代理账号';
        }
        if (! Hash::check($password, (string) $agent->password)) {
            return '密码不正确';
        }

        return null;
    }

    private function renewCaptcha(Request $request): string
    {
        $captcha = (string) random_int(1000, 9999);
        $request->session()->put('agent_captcha', $captcha);

        return $captcha;
    }
}
