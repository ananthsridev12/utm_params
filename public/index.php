<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/Core/Autoload.php';
require APP_ROOT . '/app/Core/helpers.php';

use App\Core\Config;
use App\Core\Router;

$sessionName = Config::get('app.session_name', 'utm_app_session');
session_name($sessionName);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => (bool) Config::get('app.secure_cookies', false),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if ((bool) Config::get('app.debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require APP_ROOT . '/app/routes.php';

/** @var Router $router */
$router->dispatch();
