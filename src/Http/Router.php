<?php
declare(strict_types=1);

namespace App\Http;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            Response::json(['ok' => false, 'message' => 'Not found'], 404);
            return;
        }
        $handler();
    }
}

