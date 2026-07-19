<?php

namespace App\Install;

use RuntimeException;

final class InstallationManager
{
    public function isInstalled(): bool
    {
        return is_file($this->lockPath());
    }

    public function lockFilePath(): string
    {
        $path = $this->lockPath();
        $basePath = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $basePath)
            ? str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($basePath)))
            : $path;
    }

    public function adminPath(): string
    {
        $path = (string) ($this->metadata()['admin_path'] ?? config('mybsphp.admin_path', 'admin'));

        return trim($path, '/') ?: 'admin';
    }

    public function applicationName(): string
    {
        return (string) ($this->metadata()['app_name'] ?? config('app.name', 'MyBsPHP2'));
    }

    public function secondaryPasswordEnabled(): bool
    {
        $value = $this->metadata()['secondary_password_enabled']
            ?? config('mybsphp.secondary_password_enabled', false);

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        $contents = @file_get_contents($this->lockPath());

        if (! is_string($contents)) {
            return [];
        }

        $metadata = json_decode($contents, true);

        return is_array($metadata) ? $metadata : [];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function writeLock(array $metadata): void
    {
        $path = $this->lockPath();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException('创建安装锁目录失败。');
        }

        $temporary = $path.'.tmp';
        $payload = json_encode(
            [
                ...$metadata,
                'installed_at' => now()->toAtomString(),
                'version' => '0.2.0-dev',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        if (! is_string($payload) || file_put_contents($temporary, $payload.PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('写入安装锁失败。');
        }

        @chmod($temporary, 0600);

        if (! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('保存安装锁失败。');
        }
    }

    private function lockPath(): string
    {
        return (string) config('mybsphp.install_lock', storage_path('installed.lock'));
    }
}
