<?php

declare(strict_types=1);

namespace Positron\Services;

use Positron\Core\Auth;
use Positron\Core\Config;
use Positron\Models\Visit;

final class VisitTracker
{
    public static function track(string $path): void
    {
        if (str_starts_with($path, '/assets') || str_starts_with($path, '/webhooks')) {
            return;
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = (string) Config::get('app.key', 'positron');
        $hash = hash_hmac('sha256', $ip, $key);
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        try {
            Visit::record($path, $hash, $ua, Auth::id());
        } catch (\Throwable) {
            // Las visitas no deben tumbar la petición.
        }
    }
}
