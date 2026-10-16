<?php

return [
    // Own ephemeral store; no license/session records are read or written.
    'store' => env('PEER_PRESENCE_CACHE_STORE', 'file'),
    'heartbeat_seconds' => 600,
    'ttl_seconds' => 1200,
    'maximum_peers' => 4096,
];
