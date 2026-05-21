<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

final class Router
{
    private array $routes = [];

    public function get(string $path, Closure|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, Closure|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, Closure|array $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, Closure|array $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, Closure|array $handler): void
    {
        $this->routes[$method][$this->normalizePath($path)] = $handler;
    }

    public function dispatch(): mixed
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $this->normalizePath($path);

        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo '404 - Page not found';
            return null;
        }

        if ($handler instanceof Closure) {
            return $handler();
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;

            if (!class_exists($class)) {
                throw new RuntimeException("Controller class not found: {$class}");
            }

            $controller = new $class();

            if (!method_exists($controller, $action)) {
                throw new RuntimeException("Method not found: {$class}::{$action}");
            }

            return $controller->$action();
        }

        throw new RuntimeException('Invalid route handler.');
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
