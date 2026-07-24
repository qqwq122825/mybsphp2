<?php

return [
    'installed' => env('APP_INSTALLED', false),
    'admin_path' => env('ADMIN_PATH', 'admin'),
    'agent_path' => env('AGENT_PATH', 'agent'),
    'secondary_password_enabled' => env('ADMIN_SECONDARY_PASSWORD_ENABLED', false),
    'install_lock' => env('INSTALL_LOCK_PATH', storage_path('installed.lock')),
    'environment_file' => env('INSTALL_ENV_PATH', base_path('.env')),
    'reinstall_tokens_directory' => env(
        'REINSTALL_TOKENS_DIRECTORY',
        storage_path('framework/reinstall-tokens'),
    ),

    'login' => [
        'maximum_attempts' => (int) env('ADMIN_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('ADMIN_LOGIN_DECAY_SECONDS', 900),
        'failure_delay_ms' => (int) env('ADMIN_LOGIN_FAILURE_DELAY_MS', 250),
    ],

    'agent_login' => [
        'language_enabled' => (bool) env('AGENT_LOGIN_LANGUAGE_ENABLED', false),
        'captcha_enabled' => (bool) env('AGENT_LOGIN_CAPTCHA_ENABLED', false),
    ],
];
