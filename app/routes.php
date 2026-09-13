<?php

declare(strict_types=1);

use Positrom\Controllers\AccountController;
use Positrom\Controllers\Admin\ClientsController;
use Positrom\Controllers\Admin\DashboardController;
use Positrom\Controllers\Admin\SettingsController;
use Positrom\Controllers\Admin\UsageController;
use Positrom\Controllers\AuthController;
use Positrom\Controllers\ChatController;
use Positrom\Controllers\HomeController;
use Positrom\Core\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/privacidad', [HomeController::class, 'privacidad']);
    $router->get('/terminos', [HomeController::class, 'terminos']);
    $router->get('/salud', [HomeController::class, 'salud']);

    $router->get('/acceso', [AuthController::class, 'showLogin']);
    $router->post('/acceso', [AuthController::class, 'login']);
    $router->get('/registro', [AuthController::class, 'showRegister']);
    $router->post('/registro', [AuthController::class, 'register']);
    $router->post('/salida', [AuthController::class, 'logout'], ['auth' => 'user']);

    $router->get('/chat', [ChatController::class, 'index'], ['auth' => 'user']);
    $router->post('/chat/nueva', [ChatController::class, 'createConversation'], ['auth' => 'user']);
    $router->post('/chat/mensajes', [ChatController::class, 'send'], ['auth' => 'user']);

    $router->get('/cuenta', [AccountController::class, 'index'], ['auth' => 'user']);

    $router->get('/admin', [DashboardController::class, 'index'], ['auth' => 'admin']);
    $router->get('/admin/clientes', [ClientsController::class, 'index'], ['auth' => 'admin']);
    $router->get('/admin/clientes/{id}', [ClientsController::class, 'show'], ['auth' => 'admin']);
    $router->post('/admin/clientes/{id}/estado', [ClientsController::class, 'toggle'], ['auth' => 'admin']);
    $router->get('/admin/uso', [UsageController::class, 'index'], ['auth' => 'admin']);
    $router->get('/admin/ajustes', [SettingsController::class, 'index'], ['auth' => 'admin']);
    $router->post('/admin/ajustes/claves', [SettingsController::class, 'saveKeys'], ['auth' => 'admin']);
    $router->post('/admin/ajustes/uso', [SettingsController::class, 'saveUsage'], ['auth' => 'admin']);
};
