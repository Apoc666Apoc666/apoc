<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], int $code = 200): void
    {
        $tpl = dirname(__DIR__) . '/templates/' . $template . '.php';
        if (!is_file($tpl)) {
            throw new \RuntimeException("Template not found: $template");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $tpl;
        $content = ob_get_clean();

        ob_start();
        require dirname(__DIR__) . '/templates/layout.php';
        $html = ob_get_clean();

        Response::html($html, $code);
    }
}
