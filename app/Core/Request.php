<?php

namespace App\Core;

class Request
{
    public static function method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Allow HTML forms (which only support GET/POST) to submit PUT/DELETE via a hidden field.
        if ($method === 'POST' && !empty($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        }
        return strtoupper($method);
    }

    public static function route(): string
    {
        $route = $_GET['r'] ?? 'dashboard';
        $route = trim((string) $route, '/');
        return $route === '' ? 'dashboard' : $route;
    }

    public static function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public static function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public static function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    public static function file(string $key): ?array
    {
        if (!empty($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE) {
            return $_FILES[$key];
        }
        return null;
    }
}
