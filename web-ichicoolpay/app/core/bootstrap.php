<?php

declare(strict_types=1);

date_default_timezone_set(app_config('timezone', 'Asia/Bangkok'));

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__, 2);
    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function app_path(string $path = ''): string
{
    return base_path('app' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR)));
}

function public_path(string $path = ''): string
{
    $publicDirectory = (string) app_config('public_directory', 'public');
    $publicBase = base_path($publicDirectory);

    if (!is_dir($publicBase) && $publicDirectory === 'public' && is_dir(base_path('public_html'))) {
        $publicBase = base_path('public_html');
    }

    return $path === '' ? $publicBase : $publicBase . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR)));
}

function app_config(string $key, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require app_path('config/config.php');
    }
    return array_key_exists($key, $config) ? $config[$key] : $default;
}

function database_config(string $key, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require app_path('config/database.php');
    }
    return array_key_exists($key, $config) ? $config[$key] : $default;
}

function is_production(): bool
{
    return app_config('environment') === 'production';
}

if (is_production()) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
