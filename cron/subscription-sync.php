<?php

declare(strict_types=1);

define('POSITRON_CLI', true);

require dirname(__DIR__) . '/app/bootstrap.php';

use Positron\Services\BillingService;

$n = (new BillingService())->syncRemoteSubscriptions();
fwrite(STDOUT, 'Suscripciones sincronizadas: ' . $n . PHP_EOL);
