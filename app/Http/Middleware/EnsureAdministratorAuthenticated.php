<?php

namespace App\Http\Middleware;

use App\Auth\AdminSession;
use App\Models\Administrator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdministratorAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $administrator = Auth::guard('admin')->user();

        $administrator = $administrator instanceof Administrator ? $administrator->fresh() : null;

        if (! $administrator instanceof Administrator
            || ! app(AdminSession::class)->valid($request, $administrator)) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/'.(string) $request->route('adminPath'));
        }

        Auth::guard('admin')->setUser($administrator);
        Auth::shouldUse('admin');
        $administrator->loadMissing('roles.permissions');

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
