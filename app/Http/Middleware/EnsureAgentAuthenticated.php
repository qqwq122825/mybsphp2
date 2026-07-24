<?php

namespace App\Http\Middleware;

use App\Models\Administrator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAgentAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $agent = Auth::guard('agent')->user();

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
