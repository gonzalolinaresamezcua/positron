<?php

declare(strict_types=1);

use Positron\Controllers\AccountController;
use Positron\Controllers\Admin\ClientsController;
use Positron\Controllers\Admin\DashboardController;
use Positron\Controllers\Admin\PaymentsController;
use Positron\Controllers\Admin\SettingsController;
use Positron\Controllers\Admin\UsageController;
use Positron\Controllers\AuthController;
use Positron\Controllers\ChatController;
use Positron\Controllers\CheckoutController;
use Positron\Controllers\HomeController;
use Positron\Controllers\WebhookController;
use Positron\Core\Router;

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

    $router->get('/checkout', [CheckoutController::class, 'index'], ['auth' => 'user']);
    $router->post('/checkout', [CheckoutController::class, 'start'], ['auth' => 'user']);
    $router->get('/checkout/retorno', [CheckoutController::class, 'retorno'], ['auth' => 'user']);

    $router->post('/webhooks/mollie', [WebhookController::class, 'mollie'], ['csrf' => false]);

    $router->get('/chat', [ChatController::class, 'index'], ['auth' => 'user']);
    $router->post('/chat/nueva', [ChatController::class, 'createConversation'], ['auth' => 'user']);
    $router->post('/chat/mensajes', [ChatController::class, 'send'], ['auth' => 'user']);

    $router->get('/cuenta', [AccountController::class, 'index'], ['auth' => 'user']);
    $router->post('/cuenta/cancelar', [AccountController::class, 'cancel'], ['auth' => 'user']);

    $router->get('/admin', [DashboardController::class, 'index'], ['auth' => 'admin']);
    $router->get('/admin/clientes', [ClientsController::class, 'index'], ['auth' => 'admin']);
    $router->get('/admin/clientes/{id}', [ClientsController::class, 'show'], ['auth' => 'admin']);
    $router->post('/admin/clientes/{id}/activar', [ClientsController::class, 'activate'], ['auth' => 'admin']);
    $router->post('/admin/clientes/{id}/cancelar', [ClientsController::class, 'cancel'], ['auth' => 'admin']);
    $router->post('/admin/clientes/{id}/estado', [ClientsController::class, 'toggle'], ['auth' => 'admin']);
    $router->get('/admin/pagos', [PaymentsController::class, 'index'], ['auth' => 'admin']);
    $router->get('/admin/uso', [UsageController::class, 'index'], ['auth' => 'admin']);
    $router->get('/admin/ajustes', [SettingsController::class, 'index'], ['auth' => 'admin']);
    $router->post('/admin/ajustes/claves', [SettingsController::class, 'saveKeys'], ['auth' => 'admin']);
    $router->post('/admin/ajustes/uso', [SettingsController::class, 'saveUsage'], ['auth' => 'admin']);
};
