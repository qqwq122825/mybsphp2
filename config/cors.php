<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | 允许本地 http 页面（file:// / localhost）调试 AppEn 与 API。
    | 原生插件不依赖 CORS；此项主要方便浏览器联调。
    |
    */

    'paths' => ['api/*', 'AppEn.php'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Request-ID', 'Retry-After'],

    'max_age' => 3600,

    'supports_credentials' => false,

];
