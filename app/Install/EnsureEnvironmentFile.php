<?php

namespace App\Install;

use RuntimeException;

/**
 * Ensures a bootable .env exists before Laravel boots.
 * git does not ship .env; first deploy / first visit should not 500 for that reason alone.
 */
final class EnsureEnvironmentFile
{
    public static function bootstrap(string $basePath): void
    {
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $envPath = $basePath.DIRECTORY_SEPARATOR.'.env';
        $examplePath = $basePath.DIRECTORY_SEPARATOR.'.env.example';

        if (! is_file($envPath)) {
            if (! is_file($examplePath)) {
                return;
            }

            $example = file_get_contents($examplePath);
            if (! is_string($example) || $example === '') {
                throw new RuntimeException('读取 .env.example 失败。');
            }

            self::writeAtomically($envPath, $example);
        }

        $contents = file_get_contents($envPath);
        if (! is_string($contents)) {
            throw new RuntimeException('读取 .env 失败。');
        }

        if (self::hasValidAppKey($contents)) {
            return;
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        self::writeAtomically($envPath, self::setEnvValue($contents, 'APP_KEY', $key));
    }

    public static function hasValidAppKey(string $contents): bool
    {
        if (preg_match('/^APP_KEY=(.*)$/m', $contents, $matches) !== 1) {
            return false;
        }

        $raw = trim($matches[1]);
        if (
            $raw === ''
            || $raw === 'null'
            || str_contains($raw, 'change-this')
            || str_contains($raw, 'generate')
        ) {
            return false;
        }

        if (
            (str_starts_with($raw, '"') && str_ends_with($raw, '"'))
            || (str_starts_with($raw, "'") && str_ends_with($raw, "'"))
        ) {
            $raw = substr($raw, 1, -1);
        }

        if (str_starts_with($raw, 'base64:')) {
            $decoded = base64_decode(substr($raw, 7), true);

            return is_string($decoded) && in_array(strlen($decoded), [16, 32], true);
        }

        // Reject common mistaken 64-char hex dumps.
        if (preg_match('/^[0-9a-fA-F]{64}$/', $raw) === 1) {
            return false;
        }

        return in_array(strlen($raw), [16, 32], true);
    }

    public static function setEnvValue(string $contents, string $key, string $value): string
    {
        $encoded = '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $key.'='.$encoded, $contents);
        }

        return rtrim($contents).PHP_EOL.$key.'='.$encoded.PHP_EOL;
    }

    private static function writeAtomically(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException('项目根目录不可写，无法创建 .env。');
        }

        $temporary = $path.'.bootstrap-'.bin2hex(random_bytes(4));
        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new RuntimeException('写入临时 .env 失败。');
        }

        @chmod($temporary, 0600);

        if (! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('保存 .env 失败。');
        }

        @chmod($path, 0600);
    }
}
