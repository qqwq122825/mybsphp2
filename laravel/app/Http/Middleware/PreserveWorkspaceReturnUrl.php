<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreserveWorkspaceReturnUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceUrl = $request->header('X-Workspace-Url');
        $adminPath = $request->route('adminPath');
        $allowedPrefix = $request->getSchemeAndHttpHost().'/'.trim((string) $adminPath, '/').'/';

        if (is_string($workspaceUrl) && str_starts_with($workspaceUrl, $allowedPrefix)) {
            $request->session()->setPreviousUrl($workspaceUrl);
        }

        return $next($request);
    }
}
