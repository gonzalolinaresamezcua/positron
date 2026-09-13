<?php

declare(strict_types=1);

use Positrom\Core\Config;
use Positrom\Core\Csrf;
use Positrom\Core\Database;
use Positrom\Core\Router;
use Positrom\Core\Session;
use Positrom\Core\Validator;
use Positrom\Models\Setting;
use Positrom\Models\UsageEvent;
use Positrom\Models\User;
use Positrom\Services\EnvWriter;
use Positrom\Services\OpenAIClient;
use Positrom\Services\UsageLimiter;
use Positrom\Tests\FakeTransport;

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
$hash = password_hash('Positrom#Admin2026', PASSWORD_DEFAULT);
expect(is_string($hash) && password_verify('Positrom#Admin2026', $hash), 'password_hash / verify');
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
$v2 = new Validator(['email' => 'ok@positrom.local', 'name' => 'Ada']);
$v2->required('name', 'El nombre')->email('email', 'El correo');
expect($v2->ok(), 'validador email correcto');

$router = new Router();
expect($router->match('/admin/clientes/{id}', '/admin/clientes/7') === ['id' => '7'], 'router captura id');
expect($router->match('/chat', '/cuenta') === null, 'router no coincide');

$client = new OpenAIClient(new FakeTransport(200, [
    'choices' => [['message' => ['content' => 'Hola desde gpt-6-astra']]],
    'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 8],
]));
expect(!$client->configured(), 'OpenAI placeholder no está configurado');
expect(OpenAIClient::estimateTokens('abcd') >= 1, 'estimación de tokens');

echo "== Integración MySQL ==\n";
$schema = file_get_contents($root . '/database/schema.sql');
expect($schema !== false && str_contains($schema, 'CREATE TABLE'), 'schema.sql presente');
expect($schema !== false && !str_contains($schema, 'subscriptions'), 'schema sin suscripciones');

try {
    Database::query('SELECT 1');
    expect(true, 'conexión PDO');
} catch (Throwable $e) {
    expect(false, 'conexión PDO: ' . $e->getMessage());
    echo "No se puede continuar sin MySQL.\n";
    exit(1);
}

$tables = Database::fetchAll('SHOW TABLES');
expect(count($tables) >= 6, 'tablas creadas');

$admin = User::findByEmail('admin@positrom.local');
expect($admin !== null && $admin['role'] === 'admin', 'admin semilla');
expect($admin !== null && password_verify('Positrom#Admin2026', $admin['password_hash']), 'hash admin documentado');
expect(User::countByRole('admin') === 1, 'un solo administrador');

$limiter = new UsageLimiter();
expect($limiter->estimateCost(1_000_000, 1_000_000) > 0, 'coste de 1M+1M tokens > 0');

$email = 'cliente.prueba+' . bin2hex(random_bytes(3)) . '@positrom.local';
$uid = User::create($email, 'ClaveSegura#99', 'Cliente Prueba');
UsageEvent::record($uid, null, 'gpt-6-astra', 100, 50, 0.01);
$snap = $limiter->snapshot($uid);
expect($snap['tokens'] === 150 && $snap['requests'] >= 1, 'snapshot de uso real');
expect($snap['exhausted'] === false, 'sin tope por defecto no agotado');

Setting::set('usage.monthly_token_allowance', '100');
Config::set('usage.token_allowance', '100');
$limiter2 = new UsageLimiter();
UsageEvent::record($uid, null, 'gpt-6-astra', 0, 60, 0.01);
$snap2 = $limiter2->snapshot($uid);
expect($snap2['exhausted'] === true, 'agotado al superar tope opcional');
Setting::set('usage.monthly_token_allowance', '');

$envBackup = file_get_contents($envPath);
$written = EnvWriter::update(['APP_URL' => 'https://positrom.test']);
expect(in_array('APP_URL', $written, true), 'EnvWriter permite APP_URL');
$after = file_get_contents($envPath);
expect(is_string($after) && str_contains($after, 'APP_URL=https://positrom.test'), 'EnvWriter persiste');
$blockedKeys = EnvWriter::update(['NOT_ALLOWED' => 'x']);
expect($blockedKeys === [], 'EnvWriter ignora claves no listadas');
file_put_contents($envPath, (string) $envBackup);
\Positrom\Core\Env::reload($envPath);
Config::boot();

Config::set('openai.key', 'sk_test_realish_key_value_1234567890');
$ft = new FakeTransport(200, [
    'choices' => [['message' => ['content' => 'Respuesta de prueba']]],
    'usage' => ['prompt_tokens' => 3, 'completion_tokens' => 5],
]);
$oc = new OpenAIClient($ft);
$done = $oc->complete([['role' => 'user', 'content' => 'hola']]);
expect($done->text === 'Respuesta de prueba' && $done->tokensIn === 3, 'OpenAIClient parsea completions');
expect(str_contains($ft->calls[0]['url'], '/v1/chat/completions'), 'OpenAI usa chat path');
expect(($ft->calls[0]['headers']['Authorization'] ?? '') === 'Bearer sk_test_realish_key_value_1234567890', 'OpenAI envía Bearer');
expect($done->model === 'gpt-6-astra' || $done->model !== '', 'modelo en completion');
Config::set('openai.key', 'sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

Setting::set('openai.model', 'gpt-6-astra');
expect((string) Setting::get('openai.model') === 'gpt-6-astra', 'settings OpenAI persistidas');

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
    for ($i = 0; $i < 30; $i++) {
        usleep(150000);
        $hit = @file_get_contents("http://{$host}:{$port}/salud", false, stream_context_create(['http' => ['timeout' => 1, 'ignore_errors' => true]]));
        if (is_string($hit) && str_contains($hit, 'POSITROM')) {
            $ready = true;
            break;
        }
    }
    expect($ready, 'GET /salud');

    $home = @file_get_contents("http://{$host}:{$port}/", false, stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
    expect(is_string($home) && str_contains($home, 'POSITROM') && str_contains($home, 'gratis'), 'GET / marketing gratuito');
    expect(is_string($home) && str_contains($home, 'gpt-6-astra') && str_contains($home, 'OpenAI'), 'landing OpenAI gpt-6-astra');
    expect(is_string($home) && !str_contains($home, 'Mollie') && !str_contains($home, 'composer-2.5'), 'landing sin Mollie ni composer-2.5');
    expect(is_string($home) && !str_contains($home, 'POSITRON'), 'landing sin POSITRON');
    expect(is_string($home) && str_contains($home, 'positrom-galactico.css'), 'CSS Positrom Galáctico');
    expect(is_string($home) && str_contains($home, 'logo-positrom.svg'), 'logo SVG');

    $login = @file_get_contents("http://{$host}:{$port}/acceso", false, stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
    expect(is_string($login) && str_contains($login, 'name="password"'), 'GET /acceso');

    $reg = @file_get_contents("http://{$host}:{$port}/registro", false, stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
    expect(is_string($reg) && str_contains($reg, 'password_confirmation'), 'GET /registro');
    expect(is_string($reg) && !str_contains($reg, '12 €'), 'registro sin paywall');

    $priv = @file_get_contents("http://{$host}:{$port}/privacidad", false, stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]));
    expect(is_string($priv) && str_contains($priv, 'Privacidad'), 'GET /privacidad');

    @file_get_contents("http://{$host}:{$port}/admin", false, stream_context_create([
        'http' => ['timeout' => 3, 'ignore_errors' => true, 'follow_location' => 0],
    ]));
    $headers = $http_response_header ?? [];
    $redir = implode("\n", $headers);
    expect(str_contains($redir, '302') || str_contains($redir, '419'), 'GET /admin exige login');

    $css = @file_get_contents("http://{$host}:{$port}/assets/css/positrom-galactico.css", false, stream_context_create(['http' => ['timeout' => 3]]));
    expect(is_string($css) && str_contains($css, '--pg-ion') && str_contains($css, '.pg-orbit'), 'framework CSS propio');

    $svg = @file_get_contents("http://{$host}:{$port}/assets/img/logo-positrom.svg", false, stream_context_create(['http' => ['timeout' => 3]]));
    expect(is_string($svg) && str_contains($svg, '<svg'), 'logo SVG servido');

    proc_terminate($proc);
    proc_close($proc);
}

echo PHP_EOL . "Pasados: {$passed}  Fallidos: {$failed}\n";
exit($failed === 0 ? 0 : 1);
