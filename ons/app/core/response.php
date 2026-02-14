<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    private static int $status = 200;
    private static array $headers = [];
    private static string $body = '';

    public static function init(): void
    {
        self::$status = 200;
        self::$headers = [];
        self::$body = '';
    }

    public static function status(int $code): void
    {
        self::$status = $code;
    }

    public static function header(string $name, string $value): void
    {
        self::$headers[$name] = $value;
    }

    public static function html(string $html, int $code = 200): void
    {
        self::status($code);
        self::header('Content-Type', 'text/html; charset=utf-8');
        self::$body = $html;
    }

    public static function json(array $data, int $code = 200): void
    {
        self::status($code);
        self::header('Content-Type', 'application/json; charset=utf-8');
        self::$body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function redirect(string $to, int $code = 302): void
    {
        self::status($code);
        self::header('Location', $to);
        self::$body = '';
    }

    public static function send(): void
    {
        http_response_code(self::$status);
        foreach (self::$headers as $k => $v) {
            header($k . ': ' . $v);
        }
        echo self::$body;
    }
}
