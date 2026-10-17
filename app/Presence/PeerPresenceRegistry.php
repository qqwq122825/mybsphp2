<?php

namespace App\Presence;

use App\Models\Application;
use Illuminate\Support\Facades\Cache;

final class PeerPresenceRegistry
{
    public static function digest(string $code, string $name): string
    {
        return hash('sha256', 'mtx-peer:v1:'.$code.':'.trim($name, " \t\r\n"));
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
                    'game_name' => trim($name, " \t\r\n"),
                    'last_seen_at' => $now,
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
     * @param  list<array{digest: string, expires_in: int}>  $peers
     * @return array{roster: string, roster_md5: string}
     */
    public static function snapshot(string $code, array $peers): array
    {
        $digests = array_values(array_unique(array_column($peers, 'digest')));
        sort($digests, SORT_STRING);
        $roster = implode("\n", $digests);

        return ['roster' => $roster, 'roster_md5' => md5('mtx-peer-roster:v2:'.$code.":\n".$roster)];
    }

    /** Admin-only display data; never include these fields in client rosters.
     * @return array{rows: list<array{game_name: string, last_seen_at: int}>, pending_names: int}
     */
    public function administrationSnapshot(Application $app): array
    {
        if (! $app->peer_display_enabled) {
            return ['rows' => [], 'pending_names' => 0];
        }
        $now = now()->timestamp;
        $records = Cache::store(config('peer_presence.store'))->get('peer-presence:v1:'.$app->id, []);
        $names = [];
        $pending = 0;
        foreach ($records as $row) {
            if ($row['expires'] <= $now) {
                continue;
            }
            // Old digest-only records acquire their display name at the next heartbeat.
            if (! is_string($row['game_name'] ?? null) || $row['game_name'] === '') {
                $pending++;

                continue;
            }
            $digest = self::digest($app->code, $row['game_name']);
            $seen = (int) ($row['last_seen_at'] ?? ($row['expires'] - config('peer_presence.ttl_seconds')));
            if (! isset($names[$digest]) || $seen > $names[$digest]['last_seen_at']) {
                $names[$digest] = ['game_name' => $row['game_name'], 'last_seen_at' => $seen];
            }
        }
        $rows = array_values($names);
        usort($rows, fn (array $a, array $b): int => ($b['last_seen_at'] <=> $a['last_seen_at']) ?: strcmp($a['game_name'], $b['game_name']));

        return ['rows' => $rows, 'pending_names' => $pending];
    }

    public function clear(Application $app): void
    {
        $cache = Cache::store(config('peer_presence.store'));
        $key = 'peer-presence:v1:'.$app->id;
        $cache->lock($key.':lock', 5)->block(3, fn () => $cache->forget($key));
    }
}
