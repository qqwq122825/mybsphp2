<?php

namespace App\Install;

use Carbon\CarbonImmutable;
use RuntimeException;

final class ReinstallationTokenManager
{
    public function issue(int $ttlMinutes = 30): string
    {
        $ttlMinutes = max(1, min($ttlMinutes, 120));
        $directory = $this->directory();

        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException('创建重装令牌目录失败。');
        }

        $this->removeExpiredTokens();

        $token = bin2hex(random_bytes(32));
        $path = $this->path($token);
        $temporary = $path.'.tmp';
        $payload = json_encode([
            'expires_at' => now()->addMinutes($ttlMinutes)->toAtomString(),
            'issued_at' => now()->toAtomString(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($payload) || file_put_contents($temporary, $payload.PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('写入重装令牌失败。');
        }

        @chmod($temporary, 0600);

        if (! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('保存重装令牌失败。');
        }

        return $token;
    }

    public function consume(string $token): bool
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return false;
        }

        $path = $this->path($token);
        $contents = @file_get_contents($path);

        if (! is_string($contents)) {
            return false;
        }

        @unlink($path);
        $payload = json_decode($contents, true);
        $expiresAt = is_array($payload) ? ($payload['expires_at'] ?? null) : null;

        if (! is_string($expiresAt)) {
            return false;
        }

        try {
            return CarbonImmutable::parse($expiresAt)->isFuture();
        } catch (\Throwable) {
            return false;
        }
    }

    private function removeExpiredTokens(): void
    {
        $paths = glob($this->directory().'/*.json');

        if (! is_array($paths)) {
            return;
        }

        foreach ($paths as $path) {
            $contents = @file_get_contents($path);
            $payload = is_string($contents) ? json_decode($contents, true) : null;
            $expiresAt = is_array($payload) ? ($payload['expires_at'] ?? null) : null;

            try {
                if (! is_string($expiresAt) || CarbonImmutable::parse($expiresAt)->isPast()) {
                    @unlink($path);
                }
            } catch (\Throwable) {
                @unlink($path);
            }
        }
    }

    private function path(string $token): string
    {
        return $this->directory().'/'.hash('sha256', $token).'.json';
    }

    private function directory(): string
    {
        return (string) config(
            'mybsphp.reinstall_tokens_directory',
            storage_path('framework/reinstall-tokens'),
        );
    }
}
