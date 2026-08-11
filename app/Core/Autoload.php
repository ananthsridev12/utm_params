<?php
/**
 * Minimal PSR-4-ish autoloader so the app runs on shared hosting with no
 * Composer step required. Maps App\Xyz\ClassName -> app/Xyz/ClassName.php.
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
