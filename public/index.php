<?php

declare(strict_types=1);

use Positrom\Core\Router;
use Positrom\Core\View;
use Positrom\Services\VisitTracker;

require dirname(__DIR__) . '/app/bootstrap.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$path = request_path();
VisitTracker::track($path);

$router = new Router();
$register = require POSITROM_APP . '/routes.php';
$register($router);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
} catch (Throwable $e) {
    $log = POSITROM_STORAGE . '/logs/app-' . date('Y-m-d') . '.log';
    @file_put_contents($log, '[' . now() . '] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    if (Positrom\Core\Config::get('app.debug')) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $e->getMessage() . PHP_EOL . $e->getTraceAsString();
        exit;
    }
    View::render('errors/500', ['title' => 'Error'], 'layouts/public');
}
