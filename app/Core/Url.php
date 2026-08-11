<?php

namespace App\Core;

/**
 * URL helper. Links are always generated as index.php?r=<route>&... so the app
 * works whether or not the host allows .htaccess/mod_rewrite for pretty URLs.
 */
class Url
{
    public static function to(string $route, array $params = []): string
    {
        $base = rtrim((string) Config::get('app.url'), '/');
        $query = array_merge(['r' => $route], $params);
        return $base . '/index.php?' . http_build_query($query);
    }

    public static function asset(string $path): string
    {
        $base = rtrim((string) Config::get('app.url'), '/');
        return $base . '/assets/' . ltrim($path, '/');
    }

    public static function current(): string
    {
        return self::to(Request::route(), $_GET);
    }
}
