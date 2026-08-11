<?php
/**
 * Dev-only router for PHP's built-in server, which doesn't read .htaccess.
 * Mimics the Apache rewrite rules (public/.htaccess) so clean URLs like
 * /login work the same way locally as they will on real Apache/cPanel
 * hosting. Not used in production -- Apache + .htaccess handles this there.
 *
 * Usage: php -S localhost:8000 -t public dev-router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . '/public' . $uri;

// Let the built-in server serve real files (css/js/etc.) natively.
if ($uri !== '/' && is_file($file)) {
    return false;
}

$_GET['r'] = ltrim($uri, '/');
require __DIR__ . '/public/index.php';
