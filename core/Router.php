<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal method+pattern router with middleware groups and {param} captures.
 * Ported from the house idiom (EN-only — no locale prefix handling).
 */
class Router
{
    /** @var array<int,array{method:string,pattern:string,handler:array,middleware:array}> */
    private array $routes = [];
    /** @var string[] */
    private array $middlewareStack = [];

    public function get(string $p, array $h): void    { $this->addRoute('GET', $p, $h); }
    public function post(string $p, array $h): void   { $this->addRoute('POST', $p, $h); }
    public function put(string $p, array $h): void    { $this->addRoute('PUT', $p, $h); }
    public function patch(string $p, array $h): void  { $this->addRoute('PATCH', $p, $h); }
    public function delete(string $p, array $h): void { $this->addRoute('DELETE', $p, $h); }

    public function group(array $options, callable $callback): void
    {
        $prev = $this->middlewareStack;
        if (!empty($options['middleware'])) {
            $this->middlewareStack = array_merge($this->middlewareStack, (array) $options['middleware']);
        }
        $prefix = $options['prefix'] ?? '';
        if ($prefix !== '') {
            $callback(new PrefixedRouter($this, $prefix));
        } else {
            $callback($this);
        }
        $this->middlewareStack = $prev;
    }

    public function addRoute(string $method, string $pattern, array $handler): void
    {
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $this->middlewareStack,
        ];
    }

    public function dispatch(Request $request, Response $response): void
    {
        $method = $request->method();
        $path = rtrim($request->path(), '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($this->patternToRegex($route['pattern']), $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $request->setParams($params);

            foreach ($route['middleware'] as $mwClass) {
                (new $mwClass())->handle($request, $response);
            }

            [$class, $fn] = $route['handler'];
            (new $class())->$fn($request, $response);
            return;
        }

        abort(404, 'Not Found');
    }

    private function patternToRegex(string $pattern): string
    {
        $pattern = rtrim($pattern, '/') ?: '/';
        $regex = preg_replace_callback('/\{(\w+)\}/', fn($m) => '(?P<' . $m[1] . '>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}

class PrefixedRouter
{
    public function __construct(private Router $router, private string $prefix) {}

    public function get(string $p, array $h): void    { $this->router->addRoute('GET', $this->prefix . $p, $h); }
    public function post(string $p, array $h): void   { $this->router->addRoute('POST', $this->prefix . $p, $h); }
    public function put(string $p, array $h): void    { $this->router->addRoute('PUT', $this->prefix . $p, $h); }
    public function patch(string $p, array $h): void  { $this->router->addRoute('PATCH', $this->prefix . $p, $h); }
    public function delete(string $p, array $h): void { $this->router->addRoute('DELETE', $this->prefix . $p, $h); }

    public function group(array $options, callable $callback): void
    {
        $this->router->group(
            array_merge($options, ['prefix' => $this->prefix . ($options['prefix'] ?? '')]),
            $callback
        );
    }
}
