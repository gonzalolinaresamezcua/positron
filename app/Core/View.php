<?php

declare(strict_types=1);

namespace Positron\Core;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/public'): void
    {
        $content = self::capture($template, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        $data['content'] = $content;
        echo self::capture($layout, $data);
    }

    /** @param array<string, mixed> $data */
    public static function capture(string $template, array $data = []): string
    {
        $file = POSITRON_VIEWS . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vista no encontrada: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }
}
