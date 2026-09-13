<?php

declare(strict_types=1);

use Positrom\Autoloader;
use Positrom\Core\Config;
use Positrom\Core\Database;
use Positrom\Core\Env;
use Positrom\Core\Session;

define('POSITROM_ROOT', dirname(__DIR__));
define('POSITROM_APP', POSITROM_ROOT . '/app');
define('POSITROM_VIEWS', POSITROM_ROOT . '/views');
define('POSITROM_STORAGE', POSITROM_ROOT . '/storage');

require POSITROM_APP . '/Autoloader.php';
Autoloader::register(POSITROM_APP);
require POSITROM_APP . '/helpers.php';

$envFile = POSITROM_ROOT . '/.env';
if (!is_file($envFile)) {
    $message = 'Falta el archivo .env. Copia .env.example y configura el entorno.';
    if (PHP_SAPI === 'cli' || defined('POSITROM_CLI')) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit(1);
}

Env::load($envFile);
Config::boot();

$required = ['pdo_mysql', 'mbstring', 'json', 'curl'];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        $message = 'Extensión PHP requerida no cargada: ' . $ext;
        if (PHP_SAPI === 'cli' || defined('POSITROM_CLI')) {
            fwrite(STDERR, $message . PHP_EOL);
            exit(1);
        }
        http_response_code(500);
        echo $message;
        exit(1);
    }
}

if (!is_dir(POSITROM_STORAGE . '/logs')) {
    @mkdir(POSITROM_STORAGE . '/logs', 0775, true);
}

Database::boot();

if (PHP_SAPI !== 'cli' && !defined('POSITROM_CLI')) {
    Session::start();
}
