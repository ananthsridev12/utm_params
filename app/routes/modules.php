<?php

use App\Controllers\CampaignController;
use App\Controllers\LandingPageController;
use App\Controllers\SnippetTemplateController;
use App\Controllers\SuperAdminController;
use App\Controllers\TenantSettingsController;
use App\Controllers\TrackingConfigController;
use App\Controllers\UserController;

/** @var \App\Core\Router $router */

// Modules that follow the standard CRUD route shape (see master_data.php for the pattern).
foreach ([
    'landing-pages' => LandingPageController::class,
    'tracking-configs' => TrackingConfigController::class,
    'campaigns' => CampaignController::class,
] as $base => $controllerClass) {
    $router->get($base, [$controllerClass, 'index']);
    $router->get($base . '/create', [$controllerClass, 'create']);
    $router->post($base . '/create', [$controllerClass, 'store']);
    $router->get($base . '/edit/{id}', [$controllerClass, 'edit']);
    $router->post($base . '/edit/{id}', [$controllerClass, 'update']);
    $router->post($base . '/delete/{id}', [$controllerClass, 'destroy']);
    $router->get($base . '/export', [$controllerClass, 'exportCsv']);
}

// Tracking Configurations also get a second, richer export (full details + snippets)
// alongside the basic-details-+-snippets one registered above.
$router->get('tracking-configs/export-full', [TrackingConfigController::class, 'exportCsvFull']);

// Landing Pages CSV import (template download + upload).
$router->get('landing-pages/import', [LandingPageController::class, 'showImport']);
$router->post('landing-pages/import', [LandingPageController::class, 'import']);
$router->get('landing-pages/import-template', [LandingPageController::class, 'downloadTemplate']);

// Snippet Templates -- custom shape (no delete-of-defaults, no CSV export).
$router->get('snippet-templates', [SnippetTemplateController::class, 'index']);
$router->get('snippet-templates/create', [SnippetTemplateController::class, 'create']);
$router->post('snippet-templates/create', [SnippetTemplateController::class, 'store']);
$router->get('snippet-templates/edit/{id}', [SnippetTemplateController::class, 'edit']);
$router->post('snippet-templates/edit/{id}', [SnippetTemplateController::class, 'update']);
$router->post('snippet-templates/delete/{id}', [SnippetTemplateController::class, 'destroy']);

// Users & Roles (within the current tenant).
$router->get('users', [UserController::class, 'index']);
$router->get('users/invite', [UserController::class, 'create']);
$router->post('users/invite', [UserController::class, 'store']);
$router->get('users/edit/{id}', [UserController::class, 'edit']);
$router->post('users/edit/{id}', [UserController::class, 'update']);
$router->post('users/reset-password/{id}', [UserController::class, 'resetPassword']);
$router->post('users/delete/{id}', [UserController::class, 'destroy']);
$router->post('users/invite-link/generate', [UserController::class, 'generateInviteLink']);
$router->post('users/invite-link/disable', [UserController::class, 'disableInviteLink']);

// Tenant (company) settings.
$router->get('tenant-settings', [TenantSettingsController::class, 'edit']);
$router->post('tenant-settings', [TenantSettingsController::class, 'update']);

// Platform-level Super Admin panel.
$router->get('super-admin', [SuperAdminController::class, 'index']);
$router->post('super-admin/suspend/{id}', [SuperAdminController::class, 'suspend']);
$router->post('super-admin/activate/{id}', [SuperAdminController::class, 'activate']);
