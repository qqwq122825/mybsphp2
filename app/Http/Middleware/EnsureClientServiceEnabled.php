<?php

namespace App\Http\Middleware;

use App\Http\ApiResponse;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EnsureClientServiceEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $settings = SystemSetting::query()
                ->whereIn('key', ['client_service_enabled', 'client_service_message'])
                ->pluck('value', 'key');
            $enabled = filter_var($settings['client_service_enabled'] ?? true, FILTER_VALIDATE_BOOL);
            if (! $enabled) {
                return ApiResponse::error(
                    'SERVICE_DISABLED',
                    (string) ($settings['client_service_message'] ?? '系统维护中，请稍后再试。'),
                    503,
                    request: $request,
                );
            }
        } catch (Throwable) {
            // Installation and recovery requests keep working before the settings table exists.
        }

        return $next($request);
    }
}
