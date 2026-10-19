<?php

declare(strict_types=1);

$routes = [
    '/api/device/config' => __DIR__ . '/api/device/config.php',
    '/api/device/heartbeat' => __DIR__ . '/api/device/heartbeat.php',
    '/api/playlist' => __DIR__ . '/api/playlist.php',
];

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

if (is_string($path) && isset($routes[$path])) {
    require $routes[$path];
    return true;
}

$file = __DIR__ . $path;
if (is_string($path) && $path !== '/' && is_file($file)) {
    return false;
}

return false;
