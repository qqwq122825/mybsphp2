<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AttachRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = strtoupper((string) $request->headers->get('X-Request-ID'));

        if (! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $requestId)) {
            $requestId = (string) Str::ulid();
        }

        $request->attributes->set('request_id', $requestId);
        $contextKeys = ['request_id', 'method', 'path', 'ip_hash'];
        $startedAt = hrtime(true);

        Log::shareContext([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        try {
            $response = $next($request);

            Log::info('http.request.completed', [
                'request_id' => $requestId,
                'status' => $response->getStatusCode(),
                'duration_ms' => $this->durationMilliseconds($startedAt),
            ]);

            $response->headers->set('X-Request-ID', $requestId);

            return $response;
        } catch (Throwable $exception) {
            Log::warning('http.request.failed', [
                'request_id' => $requestId,
                'exception_class' => $exception::class,
                'duration_ms' => $this->durationMilliseconds($startedAt),
            ]);

            throw $exception;
        } finally {
            Log::withoutContext($contextKeys);
            Log::flushSharedContext();
        }
    }

    private function durationMilliseconds(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
