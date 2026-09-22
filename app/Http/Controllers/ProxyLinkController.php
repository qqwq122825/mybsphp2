<?php

namespace App\Http\Controllers;

use App\Models\ProxyLink;
use App\Models\SystemSetting;
use App\Proxy\ProxyLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ProxyLinkController extends Controller
{
    public function __invoke(Request $request, string $path, ProxyLinkService $service): JsonResponse
    {
        return DB::transaction(function () use ($request, $path, $service): JsonResponse {
            $link = ProxyLink::query()->where('path', $path)->orWhere(function ($q) use ($path): void {
                $q->where('previous_path', $path)->where('previous_path_until', '>', now());
            })->lockForUpdate()->first();
            if (! $link || ! $link->application) {
                return $this->error('NOT_FOUND', 404);
            }
            // The unguessable HTTPS URL is the credential for the single-field client.
            // Continue validating an explicitly supplied legacy bearer token.
            if ($request->bearerToken() !== null && ! hash_equals((string) $link->token_ciphertext, (string) $request->bearerToken())) {
                return $this->error('AUTH_FAILED', 401);
            }
            if (! $link->enabled) {
                return $this->error('LINK_DISABLED', 403);
            }
            $application = $link->application;
            if ($application->status !== 'active') {
                return $this->error('APP_DISABLED', 403);
            }
            $global = SystemSetting::query()->whereKey('client_service_enabled')->value('value');
            if ($global !== null && ! filter_var($global, FILTER_VALIDATE_BOOL)) {
                return $this->error('SERVICE_DISABLED', 403);
            }
            $input = $request->validate([
                'action' => ['sometimes', 'in:ping,sync'],
                'node_id' => ['nullable', 'uuid'],
                'node_name' => ['nullable', 'string', 'max:80'],
                'app_id' => ['nullable', 'string', 'max:40'],
            ]);
            if (! empty($input['app_id']) && $input['app_id'] !== $application->code) {
                return $this->error('APP_MISMATCH', 403);
            }
            $data = [
                'protocol' => ProxyLinkService::PROTOCOL,
                'application_id' => $application->public_id,
                'app_id' => $application->code, 'name' => $application->name,
                'server_time' => now()->getTimestamp(), 'lease_seconds' => 300,
            ];
            if (($input['action'] ?? 'sync') === 'sync') {
                $data['users'] = $service->users($application, $link);
                $data['complete'] = true;
                $link->forceFill([
                    'last_sync_at' => now(), 'last_user_count' => count($data['users']),
                ])->save();
            }

            return response()->json(['ok' => true, 'data' => $data])->header('Cache-Control', 'no-store');
        });
    }

    private function error(string $code, int $status): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => ['code' => $code]], $status)
            ->header('Cache-Control', 'no-store');
    }
}
