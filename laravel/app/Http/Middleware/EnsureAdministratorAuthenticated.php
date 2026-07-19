<?php

namespace App\Http\Middleware;

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

        if (! $administrator instanceof Administrator) {
            return redirect('/'.(string) $request->route('adminPath'));
        }

        Auth::shouldUse('admin');
        $administrator->loadMissing('roles.permissions');

        return $next($request);
    }
}
