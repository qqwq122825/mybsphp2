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
            // Laravel's back() prefers the HTTP Referer over the session's previous
            // URL. Workspace forms are submitted with fetch() while the browser URL
            // remains on the software overview, so keep both sources aligned with
            // the logical workspace tab that initiated the request.
            $request->headers->set('referer', $workspaceUrl);
            $request->session()->setPreviousUrl($workspaceUrl);
        }

        return $next($request);
    }
}
