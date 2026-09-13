<?php

declare(strict_types=1);

namespace Positrom\Core;

final class Router
{
    /** @var list<array{method:string,pattern:string,handler:callable|array,csrf:bool,auth:?string}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler, array $opts = []): void
    {
        $this->add('GET', $pattern, $handler, $opts);
    }

    public function post(string $pattern, callable|array $handler, array $opts = []): void
    {
        $this->add('POST', $pattern, $handler, $opts);
    }

    /** @param callable|array{0:class-string,1:string} $handler */
    public function add(string $method, string $pattern, callable|array $handler, array $opts = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'csrf' => (bool) ($opts['csrf'] ?? true),
            'auth' => $opts['auth'] ?? null,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        foreach ($this->routes as $route) {
            $params = $this->match($route['pattern'], $path);
            if ($params === null || $route['method'] !== $method) {
                continue;
            }
            if ($route['auth'] === 'user') {
                Auth::requireUser();
            } elseif ($route['auth'] === 'admin') {
                Auth::requireAdmin();
            }
            if ($method === 'POST' && $route['csrf'] && !Csrf::verify(Csrf::fromRequest())) {
                http_response_code(419);
                if (self::wantsJson()) {
                    json_response(['ok' => false, 'error' => 'Token CSRF no válido.'], 419);
                }
                set_flash('error', 'La sesión de seguridad caducó. Vuelve a intentarlo.');
                redirect($_SERVER['HTTP_REFERER'] ?? '/');
            }
            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->{$action}(...array_values($params));
                return;
            }
            $handler(...array_values($params));
            return;
        }
        http_response_code(404);
        View::render('errors/404', ['title' => 'No encontrado'], 'layouts/public');
    }

    /** @return array<string, string>|null */
    public function match(string $pattern, string $path): ?array
    {
        $pattern = rtrim($pattern, '/') ?: '/';
        $path = rtrim($path, '/') ?: '/';
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        if ($regex === null) {
            return null;
        }
        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }

    public static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return str_contains($accept, 'application/json') || strcasecmp((string) $xhr, 'XMLHttpRequest') === 0;
    }
}
