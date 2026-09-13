<?php

declare(strict_types=1);

use Positron\Autoloader;
use Positron\Core\Config;
use Positron\Core\Database;
use Positron\Core\Env;
use Positron\Core\Session;

define('POSITRON_ROOT', dirname(__DIR__));
define('POSITRON_APP', POSITRON_ROOT . '/app');
define('POSITRON_VIEWS', POSITRON_ROOT . '/views');
define('POSITRON_STORAGE', POSITRON_ROOT . '/storage');

require POSITRON_APP . '/Autoloader.php';
Autoloader::register(POSITRON_APP);
require POSITRON_APP . '/helpers.php';

$envFile = POSITRON_ROOT . '/.env';
if (!is_file($envFile)) {
    $message = 'Falta el archivo .env. Copia .env.example y configura el entorno.';
    if (PHP_SAPI === 'cli' || defined('POSITRON_CLI')) {
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
        if (PHP_SAPI === 'cli' || defined('POSITRON_CLI')) {
            fwrite(STDERR, $message . PHP_EOL);
            exit(1);
        }
        http_response_code(500);
        echo $message;
        exit(1);
    }
}

if (!is_dir(POSITRON_STORAGE . '/logs')) {
    @mkdir(POSITRON_STORAGE . '/logs', 0775, true);
}

Database::boot();

if (PHP_SAPI !== 'cli' && !defined('POSITRON_CLI')) {
    Session::start();
}
