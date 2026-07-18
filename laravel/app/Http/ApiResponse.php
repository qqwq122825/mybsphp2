<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        array $meta = [],
        int $status = 200,
        ?Request $request = null,
    ): JsonResponse {
        return response()->json([
            'ok' => true,
            'data' => $data,
            'meta' => self::meta($request, $meta),
        ], $status);
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    public static function error(
        string $code,
        string $message,
        int $status,
        ?array $details = null,
        ?Request $request = null,
    ): JsonResponse {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json([
            'ok' => false,
            'error' => $error,
            'meta' => self::meta($request),
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function meta(?Request $request, array $extra = []): array
    {
        $request ??= request();
        $requestId = $request->attributes->get('request_id');

        return array_replace([
            'request_id' => is_string($requestId) ? $requestId : (string) Str::ulid(),
            'server_time' => now()->utc()->toISOString(),
        ], $extra);
    }
}
