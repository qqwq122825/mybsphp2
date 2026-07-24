<?php

namespace App\Install;

use RuntimeException;

final class EnvironmentFileWriter
{
    /**
     * @param  array<string, string|bool|int>  $values
     */
    public function update(array $values): void
    {
        $path = (string) config('mybsphp.environment_file', base_path('.env'));
        $contents = is_file($path) ? file_get_contents($path) : file_get_contents(base_path('.env.example'));

        if (! is_string($contents)) {
            throw new RuntimeException('读取环境配置失败。');
        }

        foreach ($values as $key => $value) {
            $encoded = $this->encode($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $contents) === 1) {
                $contents = preg_replace($pattern, $key.'='.$encoded, $contents) ?? $contents;
            } else {
                $contents = rtrim($contents).PHP_EOL.$key.'='.$encoded.PHP_EOL;
            }
        }

        $temporary = $path.'.installing';

        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new RuntimeException('写入临时环境配置失败。');
        }

        @chmod($temporary, 0600);

        if (! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('保存环境配置失败。');
        }
    }

    private function encode(string|bool|int $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        return '"'.str_replace(
            ['\\', '"', "\r", "\n"],
            ['\\\\', '\\"', '', ''],
            $value,
        ).'"';
    }
}
