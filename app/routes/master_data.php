<?php

/**
 * Routes for the 9 simple tenant-scoped master-data (taxonomy) modules.
 * Every module follows the same CRUD shape handled by BaseController:
 *   GET  {base}             index
 *   GET  {base}/create      create form
 *   POST {base}/create      store
 *   GET  {base}/edit/{id}   edit form
 *   POST {base}/edit/{id}   update
 *   POST {base}/delete/{id} destroy
 *   GET  {base}/export      CSV export
 */

use App\Controllers\VerticalController;
use App\Controllers\ServiceController;
use App\Controllers\PageTypeController;
use App\Controllers\FormTypeController;
use App\Controllers\FormLocationController;
use App\Controllers\FunnelStageController;
use App\Controllers\EventController;
use App\Controllers\LeadMagnetController;
use App\Controllers\TrafficTypeController;
use App\Controllers\ChannelController;
use App\Controllers\CustomVariableController;

/** @var \App\Core\Router $router */

foreach ([
    'verticals' => VerticalController::class,
    'services' => ServiceController::class,
    'page-types' => PageTypeController::class,
    'form-types' => FormTypeController::class,
    'form-locations' => FormLocationController::class,
    'funnel-stages' => FunnelStageController::class,
    'events' => EventController::class,
    'lead-magnets' => LeadMagnetController::class,
    'traffic-types' => TrafficTypeController::class,
    'channels' => ChannelController::class,
    'custom-variables' => CustomVariableController::class,
] as $base => $controllerClass) {
    $router->get($base, [$controllerClass, 'index']);
    $router->get($base . '/create', [$controllerClass, 'create']);
    $router->post($base . '/create', [$controllerClass, 'store']);
    $router->get($base . '/edit/{id}', [$controllerClass, 'edit']);
    $router->post($base . '/edit/{id}', [$controllerClass, 'update']);
    $router->post($base . '/delete/{id}', [$controllerClass, 'destroy']);
    $router->get($base . '/export', [$controllerClass, 'exportCsv']);
}
