<?php

namespace App\Presence;

use App\Models\Application;
use Illuminate\Support\Facades\Cache;

final class PeerPresenceRegistry
{
    public static function digest(string $code, string $name): string
    {
        return hash('sha256', "mtx-peer:v1:".$code.":".trim($name, " \t\r\n"));
    }

    /** @return list<array{digest: string, expires_in: int}> */
    public function update(Application $app, string $installation, ?string $name): array
    {
        $cache = Cache::store(config('peer_presence.store'));
        $key = 'peer-presence:v1:'.$app->id;

        return $cache->lock($key.':lock', 5)->block(3, function () use ($cache, $key, $app, $installation, $name): array {
            $now = now()->timestamp;
            $rows = array_filter($cache->get($key, []), fn (array $row): bool => $row['expires'] > $now);
            if ($name === null) {
                unset($rows[$installation]);
            } else {
                abort_if(! isset($rows[$installation]) && count($rows) >= config('peer_presence.maximum_peers'), 503);
                $rows[$installation] = [
                    'digest' => self::digest($app->code, $name),
                    'expires' => $now + config('peer_presence.ttl_seconds'),
                ];
            }
            $cache->put($key, $rows, config('peer_presence.ttl_seconds'));
            unset($rows[$installation]);

            $peers = [];
            foreach ($rows as $row) {
                $digest = $row['digest'];
                $peers[$digest] = ['digest' => $digest, 'expires_in' => max($peers[$digest]['expires_in'] ?? 0, $row['expires'] - $now)];
            }

            return array_values($peers);
        });
    }

    /** Stable membership only: lease renewal must not change the roster version.
     * @param list<array{digest: string, expires_in: int}> $peers
     * @return array{roster: string, roster_md5: string}
     */
    public static function snapshot(string $code, array $peers): array
    {
        $digests = array_values(array_unique(array_column($peers, 'digest')));
        sort($digests, SORT_STRING);
        $roster = implode("\n", $digests);

        return ['roster' => $roster, 'roster_md5' => md5("mtx-peer-roster:v2:".$code.":\n".$roster)];
    }

    public function clear(Application $app): void
    {
        $cache = Cache::store(config('peer_presence.store'));
        $key = 'peer-presence:v1:'.$app->id;
        $cache->lock($key.':lock', 5)->block(3, fn () => $cache->forget($key));
    }
}
