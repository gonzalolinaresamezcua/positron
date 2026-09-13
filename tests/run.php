<?php

declare(strict_types=1);

use Positron\Core\Config;
use Positron\Core\Csrf;
use Positron\Core\Database;
use Positron\Core\Router;
use Positron\Core\Session;
use Positron\Core\Validator;
use Positron\Models\Setting;
use Positron\Models\Subscription;
use Positron\Models\UsageEvent;
use Positron\Models\User;
use Positron\Services\CursorClient;
use Positron\Services\EnvWriter;
use Positron\Services\MollieClient;
use Positron\Services\UsageLimiter;
use Positron\Tests\FakeTransport;

$root = dirname(__DIR__);
$envPath = $root . '/.env';
if (!is_file($envPath)) {
    copy($root . '/.env.example', $envPath);
}

require $root . '/app/bootstrap.php';
require $root . '/tests/FakeTransport.php';

$failed = 0;
$passed = 0;

function expect(bool $ok, string $name): void
{
    global $failed, $passed;
    if ($ok) {
        $passed++;
        echo '  ok  ' . $name . PHP_EOL;
        return;
    }
    $failed++;
    echo '  FAIL ' . $name . PHP_EOL;
}

echo "== Lint PHP ==\n";
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    if (str_contains($path, '/vendor/')) {
        continue;
    }
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    expect($code === 0, 'lint ' . substr($path, strlen($root) + 1));
    if ($code !== 0) {
        echo implode(PHP_EOL, $out) . PHP_EOL;
    }
}

echo "== Unit ==\n";
$hash = password_hash('Positron#Admin2026', PASSWORD_DEFAULT);
expect(is_string($hash) && password_verify('Positron#Admin2026', $hash), 'password_hash / verify');
expect(!password_verify('otra', $hash), 'password_verify rechaza otra clave');

$_SESSION = [];
Session::set('_csrf', '');
$token = Csrf::token();
expect(strlen($token) >= 32, 'CSRF genera token');
expect(Csrf::verify($token), 'CSRF verifica token');
expect(!Csrf::verify($token . 'x'), 'CSRF rechaza token falso');

$v = new Validator(['email' => 'mal', 'name' => '']);
$v->required('name', 'El nombre')->email('email', 'El correo');
expect(!$v->ok() && str_contains($v->first(), 'nombre'), 'validador required');
$v2 = new Validator(['email' => 'ok@positron.local', 'name' => 'Ada']);
$v2->required('name', 'El nombre')->email('email', 'El correo');
expect($v2->ok(), 'validador email correcto');

$router = new Router();
expect($router->match('/admin/clientes/{id}', '/admin/clientes/7') === ['id' => '7'], 'router captura id');
expect($router->match('/chat', '/cuenta') === null, 'router no coincide');

$client = new CursorClient(new FakeTransport(200, [
    'choices' => [['message' => ['content' => 'Hola desde composer']]],
    'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 8],
]));
expect(!$client->configured(), 'Cursor placeholder no está configurado');

$mollie = new MollieClient(new FakeTransport(200, ['id' => 'tr_test']));
expect(!$mollie->configured(), 'Mollie placeholder no está configurado');
expect(CursorClient::estimateTokens('abcd') >= 1, 'estimación de tokens');

echo "== Integración MySQL ==\n";
$schema = file_get_contents($root . '/database/schema.sql');
expect($schema !== false && str_contains($schema, 'CREATE TABLE'), 'schema.sql presente');

try {
    Database::query('SELECT 1');
    expect(true, 'conexión PDO');
} catch (Throwable $e) {
    expect(false, 'conexión PDO: ' . $e->getMessage());
    echo "No se puede continuar sin MySQL.\n";
    exit(1);
}

$tables = Database::fetchAll('SHOW TABLES');
expect(count($tables) >= 8, 'tablas creadas');

$admin = User::findByEmail('admin@positron.local');
expect($admin !== null && $admin['role'] === 'admin', 'admin semilla');
expect($admin !== null && password_verify('Positron#Admin2026', $admin['password_hash']), 'hash admin documentado');

$limiter = new UsageLimiter();
expect($limiter->budgetEur() > 0, 'presupuesto mensual configurado');
expect($limiter->estimateCost(1_000_000, 1_000_000) > 0, 'coste de 1M+1M tokens > 0');

$email = 'cliente.prueba+' . bin2hex(random_bytes(3)) . '@positron.local';
$uid = User::create($email, 'ClaveSegura#99', 'Cliente Prueba');
Subscription::createForUser($uid, 12.00);
UsageEvent::record($uid, null, 'composer-2.5', 100, 50, 0.01);
$snap = $limiter->snapshot($uid);
expect($snap['tokens'] === 150 && $snap['requests'] >= 1, 'snapshot de uso real');
expect($snap['exhausted'] === false, 'no agotado con 0.01 €');

UsageEvent::record($uid, null, 'composer-2.5', 0, 0, $limiter->budgetEur() + 1);
$snap2 = $limiter->snapshot($uid);
expect($snap2['exhausted'] === true, 'agotado al superar presupuesto');
$blocked = false;
try {
    $limiter->assertCanSpend($uid);
} catch (\Positron\Services\UsageExhaustedException) {
    $blocked = true;
}
expect($blocked, 'assertCanSpend lanza al agotar');

$envBackup = file_get_contents($envPath);
$written = EnvWriter::update(['APP_URL' => 'https://positron.test']);
expect(in_array('APP_URL', $written, true), 'EnvWriter permite APP_URL');
$after = file_get_contents($envPath);
expect(is_string($after) && str_contains($after, 'APP_URL=https://positron.test'), 'EnvWriter persiste');
$blockedKeys = EnvWriter::update(['NOT_ALLOWED' => 'x']);
expect($blockedKeys === [], 'EnvWriter ignora claves no listadas');
file_put_contents($envPath, (string) $envBackup);
\Positron\Core\Env::reload($envPath);
Config::boot();

$mollie2 = new MollieClient(new FakeTransport(200, ['id' => 'tr_demo']));
expect(!$mollie2->configured(), 'sigue sin clave Mollie real');

Config::set('cursor.key', 'crsr_test_realish_key_value');
$ft = new FakeTransport(200, [
    'choices' => [['message' => ['content' => 'Respuesta de prueba']]],
    'usage' => ['prompt_tokens' => 3, 'completion_tokens' => 5],
]);
$cc = new CursorClient($ft);
$done = $cc->complete([['role' => 'user', 'content' => 'hola']]);
expect($done->text === 'Respuesta de prueba' && $done->tokensIn === 3, 'CursorClient parsea completions');
expect(str_contains($ft->calls[0]['url'], '/v1/chat/completions'), 'Cursor usa chat path');
expect(($ft->calls[0]['headers']['Authorization'] ?? '') === 'Bearer crsr_test_realish_key_value', 'Cursor envía Bearer');
Config::set('cursor.key', 'crsr_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

Setting::set('usage.monthly_budget_eur', '12.00');
expect((string) Setting::get('usage.monthly_budget_eur') === '12.00', 'settings persistidas');

echo "== HTTP ==\n";
$host = '127.0.0.1';
$port = 8765;
$cmd = sprintf(
    '%s -S %s:%d -t %s %s',
    escapeshellarg(PHP_BINARY),
    $host,
    $port,
    escapeshellarg($root . '/public'),
    escapeshellarg($root . '/public/router.php')
);
$log = $root . '/storage/logs/php-server.log';
$proc = proc_open($cmd, [
    1 => ['file', $log, 'a'],
    2 => ['file', $log, 'a'],
], $pipes);
if (!is_resource($proc)) {
    expect(false, 'arrancar servidor PHP');
} else {
    $ready = false;
    $hit = '';
    for ($i = 0; $i < 30; $i++) {
        usleep(150000);
        $ctx = stream_context_create(['http' => ['timeout' => 1, 'ignore_errors' => true]]);
        $hit = @file_get_contents("http://{$host}:{$port}/salud", false, $ctx);
        if (is_string($hit) && str_contains($hit, 'POSITRON')) {
            $ready = true;
            break;
        }
    }
    expect($ready, 'GET /salud');

    $home = @file_get_contents("http://{$host}:{$port}/", false, stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
    expect(is_string($home) && str_contains($home, 'POSITRON') && str_contains($home, '12'), 'GET / marketing');
    expect(is_string($home) && str_contains($home, 'positron-galactico.css'), 'CSS Positron Galáctico');
    expect(is_string($home) && str_contains($home, 'logo-positron.svg'), 'logo SVG');

    $login = @file_get_contents("http://{$host}:{$port}/acceso", false, stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
    expect(is_string($login) && str_contains($login, 'name="password"'), 'GET /acceso');

    $reg = @file_get_contents("http://{$host}:{$port}/registro", false, stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
    expect(is_string($reg) && str_contains($reg, 'password_confirmation'), 'GET /registro');

    $priv = @file_get_contents("http://{$host}:{$port}/privacidad", false, stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
    expect(is_string($priv) && str_contains($priv, 'Privacidad'), 'GET /privacidad');

    @file_get_contents("http://{$host}:{$port}/admin", false, stream_context_create([
        'http' => ['timeout' => 3, 'ignore_errors' => true, 'follow_location' => 0],
    ]));
    $headers = $http_response_header ?? [];
    $redir = implode("\n", $headers);
    expect(str_contains($redir, '302') || str_contains($redir, '419'), 'GET /admin exige login');

    $css = @file_get_contents("http://{$host}:{$port}/assets/css/positron-galactico.css", false, stream_context_create(['http' => ['timeout' => 3]]));
    expect(is_string($css) && str_contains($css, '--pg-ion') && str_contains($css, '.pg-orbit'), 'framework CSS propio');

    $svg = @file_get_contents("http://{$host}:{$port}/assets/img/logo-positron.svg", false, stream_context_create(['http' => ['timeout' => 3]]));
    expect(is_string($svg) && str_contains($svg, '<svg'), 'logo SVG servido');

    proc_terminate($proc);
    proc_close($proc);
}

echo PHP_EOL . "Pasados: {$passed}  Fallidos: {$failed}\n";
exit($failed === 0 ? 0 : 1);
