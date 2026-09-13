<?php

declare(strict_types=1);

namespace Positrom;

final class Autoloader
{
    public static function register(string $baseDir): void
    {
        spl_autoload_register(static function (string $class) use ($baseDir): void {
            $prefix = 'Positrom\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $baseDir . '/' . $relative . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }
}
