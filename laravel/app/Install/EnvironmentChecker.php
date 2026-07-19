<?php

namespace App\Install;

final class EnvironmentChecker
{
    /**
     * @return list<array{key:string,label:string,ok:bool,required:bool,detail:string}>
     */
    public function checks(): array
    {
        $checks = [
            $this->check(
                'php',
                'PHP 版本',
                version_compare(PHP_VERSION, '8.3.0', '>='),
                PHP_VERSION.'，要求 8.3 或更高',
            ),
        ];

        foreach (['pdo', 'pdo_mysql', 'openssl', 'mbstring', 'json', 'ctype', 'tokenizer', 'xml'] as $extension) {
            $loaded = extension_loaded($extension);
            $checks[] = $this->check(
                'extension_'.$extension,
                'PHP 扩展：'.$extension,
                $loaded,
                $loaded ? '已启用' : '请在 PHP 配置中启用',
            );
        }

        foreach ([
            'storage' => storage_path(),
            'bootstrap_cache' => base_path('bootstrap/cache'),
        ] as $key => $path) {
            $writable = is_dir($path) && is_writable($path);
            $checks[] = $this->check(
                'directory_'.$key,
                '目录可写：'.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path),
                $writable,
                $writable ? '正常' : '请调整目录所有者或写入权限',
            );
        }

        $https = request()->isSecure();
        $checks[] = $this->check(
            'https',
            'HTTPS',
            $https,
            $https ? '当前连接已加密' : '本地开发可继续，生产环境建议启用',
            false,
        );

        return $checks;
    }

    /**
     * @param  list<array{key:string,label:string,ok:bool,required:bool,detail:string}>  $checks
     */
    public function hasBlockingFailure(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['required'] && ! $check['ok']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{key:string,label:string,ok:bool,required:bool,detail:string}
     */
    private function check(
        string $key,
        string $label,
        bool $ok,
        string $detail,
        bool $required = true,
    ): array {
        return compact('key', 'label', 'ok', 'required', 'detail');
    }
}
