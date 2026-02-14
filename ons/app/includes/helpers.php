<?php
declare(strict_types=1);

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? (string)$_POST[$key] : $default;
}
