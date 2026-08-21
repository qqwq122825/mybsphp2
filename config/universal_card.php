<?php

return [
    'enabled' => (bool) env('UNIVERSAL_CARD_ENABLED', true),

    // Used only as the initial value before the operator saves a replacement
    // from the admin console. Replacements are encrypted with APP_KEY.
    'default_code' => env('UNIVERSAL_CARD_CODE', 'jfkdls1314520'),

    // Store only a SHA-256 digest in configuration. The default digest matches
    // the operator-provided universal card and can be replaced from .env.
    'code_hash' => env(
        'UNIVERSAL_CARD_CODE_HASH',
        '78c4fa241a79f932c39e0d8cb66b41bbea7b7bb326d7a51c2250ffc38f5c38ae',
    ),

    // The legacy client requires a concrete ISO-8601 expiry value.
    'expires_at' => env('UNIVERSAL_CARD_EXPIRES_AT', '2099-12-31T23:59:59+08:00'),
];
