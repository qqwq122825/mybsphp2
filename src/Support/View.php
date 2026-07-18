<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = []): string
    {
        $path = dirname(__DIR__, 2) . '/resources/views/' . ltrim($template, '/');
        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}

