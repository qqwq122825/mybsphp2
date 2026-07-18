<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = strtoupper((string) $request->headers->get('X-Request-ID'));

        if (! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $requestId)) {
            $requestId = (string) Str::ulid();
        }

        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
