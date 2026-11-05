<?php

namespace App\Http\Middleware;

use App\Auth\AgentSupportSession;
use App\Models\Administrator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAgentAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('agent');
        if ($request->session()->has(AgentSupportSession::KEY)) {
            $agent = app(AgentSupportSession::class)->agent($request);
            if (! $agent) {
                $request->session()->forget(AgentSupportSession::KEY);
                $guard->forgetUser();

                return redirect()->route('agent.login');
            }
            abort_unless($request->isMethodSafe() || $request->routeIs('agent.logout'), 403, '管理员快速登录仅供查看。');
            $request->attributes->set(AgentSupportSession::ATTRIBUTE, true);
            $guard->setUser($agent);
            Auth::shouldUse('agent');

            try {
                $response = $next($request);
                $response->headers->set('Cache-Control', 'no-store, private');

                return $response;
            } finally {
                // setUser is request-only: never issue an agent credential or overwrite a real agent login.
                $guard->forgetUser();
            }
        }

        $agent = $guard->user();

        if (! $agent instanceof Administrator
            || $agent->status !== 'active'
            || ! ($agent->role === 'agent' || $agent->hasRole('agent'))) {
            Auth::guard('agent')->logout();

            return redirect()->route('agent.login');
        }

        Auth::shouldUse('agent');

        return $next($request);
    }
}
