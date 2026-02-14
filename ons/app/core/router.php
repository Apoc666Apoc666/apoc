<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][$this->norm($path)] = $handler;
    }

    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][$this->norm($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // strip base_path (/ons)
        $base = Env::config()['app']['base_path'] ?? '';
        if ($base && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        $path = $this->norm($path);

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            Response::html('Not found', 404);
            return;
        }

        [$class, $action] = explode('@', $handler, 2);
        if (!class_exists($class)) {
            throw new \RuntimeException("Controller not found: $class");
        }

        $obj = new $class();
        if (!method_exists($obj, $action)) {
            throw new \RuntimeException("Action not found: $class@$action");
        }

        $obj->$action();
    }

    private function norm(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path;
    }
}
