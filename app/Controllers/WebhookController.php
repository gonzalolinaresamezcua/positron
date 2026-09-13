<?php

declare(strict_types=1);

namespace Positrom\Controllers;

use Positrom\Services\BillingService;

final class WebhookController
{
    public function mollie(): void
    {
        $id = (string) ($_POST['id'] ?? '');
        if ($id === '' || !preg_match('/^tr_[A-Za-z0-9]+$/', $id)) {
            http_response_code(400);
            echo 'invalid';
            return;
        }
        try {
            (new BillingService())->handleWebhook($id);
        } catch (\Throwable $e) {
            @file_put_contents(
                POSITROM_STORAGE . '/logs/mollie-webhook.log',
                '[' . now() . '] ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
            http_response_code(500);
            echo 'error';
            return;
        }
        http_response_code(200);
        echo 'ok';
    }
}
