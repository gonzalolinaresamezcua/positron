<?php

declare(strict_types=1);

define('POSITROM_CLI', true);

require dirname(__DIR__) . '/app/bootstrap.php';

use Positrom\Services\BillingService;

$n = (new BillingService())->syncRemoteSubscriptions();
fwrite(STDOUT, 'Suscripciones sincronizadas: ' . $n . PHP_EOL);
