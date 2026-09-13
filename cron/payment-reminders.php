<?php

declare(strict_types=1);

define('POSITROM_CLI', true);

require dirname(__DIR__) . '/app/bootstrap.php';

use Positrom\Services\BillingService;

$sent = (new BillingService())->sendDueReminders();
fwrite(STDOUT, 'Recordatorios procesados: ' . $sent . PHP_EOL);
