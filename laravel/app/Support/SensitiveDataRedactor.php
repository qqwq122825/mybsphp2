<?php

namespace App\Support;

final class SensitiveDataRedactor
{
    public const REDACTED = '[REDACTED]';

    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'access_token',
        'api_key',
        'app_key',
        'appsafecode',
        'authorization',
        'card',
        'card_code',
        'card_key',
        'client_secret',
        'cookie',
        'database_password',
        'db_password',
        'device_id',
        'license_key',
        'password',
        'password_confirmation',
        'recharge_card',
        'refresh_token',
        'safe_code',
        'secondary_password',
        'security_code',
        'serial',
        'session_id',
        'set_cookie',
        'token',
    ];

    public function redact(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && $this->isSensitiveKey($key)) {
            return self::REDACTED;
        }

        if (is_array($value)) {
            $redacted = [];

            foreach ($value as $itemKey => $itemValue) {
                $redacted[$itemKey] = $this->redact(
                    $itemValue,
                    is_string($itemKey) ? $itemKey : null,
                );
            }

            return $redacted;
        }

        return is_string($value) ? $this->redactMessage($value) : $value;
    }

    public function redactMessage(string $message): string
    {
        $message = preg_replace(
            '/(Bearer\s+)[A-Za-z0-9._~+\/=-]+/i',
            '$1'.self::REDACTED,
            $message,
        ) ?? $message;

        return preg_replace(
            '/((?:password|secondary_password|security_code|token|api_key|app_key|secret|card_key|license_key|cookie)\s*[=:]\s*)(?:"[^"]*"|\'[^\']*\'|[^\s,;&]+)/i',
            '$1'.self::REDACTED,
            $message,
        ) ?? $message;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $key) ?? $key);
        $normalized = trim($normalized, '_');

        if (in_array($normalized, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        foreach (['_password', '_token', '_secret'] as $suffix) {
            if (str_ends_with($normalized, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
