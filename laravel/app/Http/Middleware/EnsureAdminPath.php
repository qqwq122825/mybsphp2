<?php

namespace App\Http\Middleware;

use App\Install\InstallationManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdminPath
{
    public function __construct(
        private readonly InstallationManager $installation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->installation->isInstalled()) {
            return redirect()->route('install.show');
        }

        if (! hash_equals(
            $this->installation->adminPath(),
            (string) $request->route('adminPath'),
        )) {
            abort(404);
        }

        return $next($request);
    }
}
