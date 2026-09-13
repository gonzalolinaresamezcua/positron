<?php

declare(strict_types=1);

$root = dirname(__DIR__);
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/run.php'), $code);
exit($code);
