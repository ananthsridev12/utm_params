<?php

namespace App\Core;

/**
 * URL helper. Generates clean paths (e.g. /login, /verticals/edit/3) rather
 * than index.php?r=... -- the .htaccess files (public/.htaccess for hosts
 * that let you point the document root at /public, root .htaccess as a
 * fallback) rewrite those clean paths back into index.php?r=... internally,
 * so this requires mod_rewrite to be enabled (the default on virtually all
 * cPanel/Apache shared hosting). See README.md if a host truly can't do that.
 */
class Url
{
    public static function to(string $route, array $params = []): string
    {
        $base = rtrim((string) Config::get('app.url'), '/');
        $route = trim($route, '/');
        // 'r' is the internal query param the rewrite rules populate -- never
        // let a stray one (e.g. from passing $_GET straight through) leak
        // into the visible query string alongside the clean path.
        unset($params['r']);
        $query = $params ? ('?' . http_build_query($params)) : '';
        return $base . ($route !== '' ? '/' . $route : '') . $query;
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
