<?php
declare(strict_types=1);

namespace App\Core;

final class ErrorHandler
{
    public static function init(string $env): void
    {
        if ($env === 'dev') {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            error_reporting(E_ALL);
        }

        set_exception_handler(function (\Throwable $e) use ($env): void {
            if ($env === 'dev') {
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
                echo "Unhandled exception:\n\n" . $e;
                return;
            }

            // Prod: geen details lekken
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Server error.";
        });
    }
}
