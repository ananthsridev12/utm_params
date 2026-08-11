<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Core\Router;

$router = new Router();

// Auth
$router->get('login', [AuthController::class, 'showLogin']);
$router->post('login', [AuthController::class, 'login']);
$router->get('logout', [AuthController::class, 'logout']);
$router->get('register', [AuthController::class, 'showRegister']);
$router->post('register', [AuthController::class, 'register']);

// Dashboard
$router->get('dashboard', [DashboardController::class, 'index']);

require APP_ROOT . '/app/routes/master_data.php';
require APP_ROOT . '/app/routes/modules.php';
