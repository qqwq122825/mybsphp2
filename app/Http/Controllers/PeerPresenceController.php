<?php

namespace App\Http\Controllers;

use App\Http\ApiResponse;
use App\Models\Application;
use App\Presence\PeerPresenceRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class PeerPresenceController extends Controller
{
    public function configuration(Request $request, Application $application): JsonResponse
    {
        $this->authenticate($request, $application);

        return $this->reply($request, $application, [
            'enabled' => (bool) $application->peer_display_enabled,
            'heartbeat_seconds' => 600,
            'ttl_seconds' => 1200,
        ]);
    }

    public function heartbeat(Request $request, Application $application, PeerPresenceRegistry $registry): JsonResponse
    {
        $this->authenticate($request, $application);
        if (! $application->peer_display_enabled) {
            $registry->clear($application);

            return $this->reply($request, $application, ['enabled' => false, 'heartbeat_seconds' => 600, 'ttl_seconds' => 1200, 'peers' => []]);
        }
        $data = $request->validate([
            'installation_id' => ['required', 'uuid'],
            'game_name' => ['required', 'string', 'max:127'],
            'roster_protocol' => ['sometimes', 'integer', 'in:2'],
            'roster_md5' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9a-f]{32}$/'],
        ]);
        // Validate the original JSON string too; global TrimStrings must not hide control bytes.
        $raw = json_decode($request->getContent(), true);
        $name = trim($raw['game_name'] ?? $data['game_name'], " \t\r\n");
        abort_if($name === '' || strlen($name) > 127 || preg_match('/[\x00-\x1f\x7f]/u', $name), 422);
        $peers = $registry->update($application, $data['installation_id'], $name);

        if (($data['roster_protocol'] ?? null) == 2) {
            $snapshot = PeerPresenceRegistry::snapshot($application->code, $peers);
            $updated = ! hash_equals($snapshot['roster_md5'], $data['roster_md5'] ?? '');
            $payload = [
                'enabled' => true, 'heartbeat_seconds' => 600, 'ttl_seconds' => 1200,
                'roster_protocol' => 2, 'roster_format' => 'digest-lines-v1',
                'roster_updated' => $updated, 'roster_md5' => $snapshot['roster_md5'],
                // A successful unchanged response renews this snapshot lease too.
                // Online membership is re-evaluated on every heartbeat before comparison.
                'roster_ttl_seconds' => 1200,
            ];
            if ($updated) {
                $payload['roster'] = $snapshot['roster'];
            }

            return $this->reply($request, $application, $payload);
        }

        // Older clients retain the original per-row expiry protocol during rollout.
        return $this->reply($request, $application, ['enabled' => true, 'heartbeat_seconds' => 600, 'ttl_seconds' => 1200, 'peers' => $peers]);
    }

    public function leave(Request $request, Application $application, PeerPresenceRegistry $registry): JsonResponse
    {
        $this->authenticate($request, $application);
        $data = $request->validate(['installation_id' => ['required', 'uuid']]);
        $registry->update($application, $data['installation_id'], null);

        return $this->reply($request, $application, ['enabled' => (bool) $application->peer_display_enabled, 'peers' => []]);
    }

    private function authenticate(Request $request, Application $app): void
    {
        $time = (string) $request->header('X-Peer-Time', '');
        $nonce = (string) $request->header('X-Peer-Nonce', '');
        $signature = (string) $request->header('X-Peer-Signature', '');
        $key = $app->communicationKey();
        abort_if($key === '' || ! ctype_digit($time) || abs(now()->timestamp - (int) $time) > 300
            || ! preg_match('/^[0-9a-f-]{36}$/i', $nonce), 401);
        $message = $request->method()."\n".$request->getPathInfo()."\n".$time."\n".$nonce."\n".$request->getContent();
        abort_unless(hash_equals(hash_hmac('sha256', $message, $key), $signature), 401);
        $cache = Cache::store(config('peer_presence.store'));
        abort_unless($cache->add('peer-nonce:'.$app->id.':'.$nonce, true, 600), 409);
    }

    /** @param array<string, mixed> $data */
    private function reply(Request $request, Application $app, array $data): JsonResponse
    {
        $response = ApiResponse::success($data, request: $request);
        $message = $request->header('X-Peer-Time')."\n".$request->header('X-Peer-Nonce')."\n".$response->getContent();
        $response->headers->set('X-Peer-Response-Signature', hash_hmac('sha256', $message, $app->communicationKey()));
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
