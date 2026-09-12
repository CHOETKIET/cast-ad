<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

function flash_set(string $key, string $message): void
{
    start_app_session();
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    start_app_session();
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = (string) $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}
