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

// Org invite links: self-register into an EXISTING tenant via a shareable
// token link (see Users & Roles -> Invite Link), instead of always
// creating a new company via /register.
$router->get('join/{token}', [AuthController::class, 'showJoin']);
$router->post('join/{token}', [AuthController::class, 'join']);

// Dashboard
$router->get('dashboard', [DashboardController::class, 'index']);

require APP_ROOT . '/app/routes/master_data.php';
require APP_ROOT . '/app/routes/modules.php';
