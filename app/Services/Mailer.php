<?php

declare(strict_types=1);

namespace Positrom\Services;

use Positrom\Core\Config;

final class Mailer
{
    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $host = (string) Config::get('smtp.host', '');
        if ($host === '') {
            return false;
        }
        // Envío SMTP opcional para futuras notificaciones del admin.
        return false;
    }
}
