<?php

namespace App\Core;

class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable|array, segments:array}> */
    private array $routes = [];

    public function get(string $pattern, $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function any(array $methods, string $pattern, $handler): void
    {
        foreach ($methods as $method) {
            $this->add($method, $pattern, $handler);
        }
    }

    private function add(string $method, string $pattern, $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => trim($pattern, '/'),
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $route = Request::route();
        $method = Request::method();
        $routeSegments = $route === '' ? [] : explode('/', $route);

        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) {
                continue;
            }
            $patternSegments = $r['pattern'] === '' ? [] : explode('/', $r['pattern']);
            if (count($patternSegments) !== count($routeSegments)) {
                continue;
            }
            $params = [];
            $match = true;
            foreach ($patternSegments as $i => $seg) {
                if (str_starts_with($seg, '{') && str_ends_with($seg, '}')) {
                    $params[substr($seg, 1, -1)] = $routeSegments[$i];
                } elseif ($seg !== $routeSegments[$i]) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $this->invoke($r['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        View::render('errors/404', [], 'layout/app');
    }

    private function invoke($handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $action] = $handler;
            $controller = new $class();
            $controller->$action($params);
            return;
        }
        $handler($params);
    }
}
