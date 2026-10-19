<?php

declare(strict_types=1);

require_once __DIR__ . '/response.php';

function api_require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        json_response([
            'success' => false,
            'message' => 'วิธีเรียก API ไม่ถูกต้อง',
            'server_time' => api_server_time(),
        ], 405);
    }
}

function api_json_input(): array
{
    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);
    if (!is_array($decoded)) {
        json_response([
            'success' => false,
            'message' => 'ข้อมูล JSON ไม่ถูกต้อง',
            'server_time' => api_server_time(),
        ], 400);
    }

    return $decoded;
}

function api_server_time(): string
{
    return date(DATE_ATOM);
}

function api_required_string(array $data, string $key): string
{
    $value = trim((string) ($data[$key] ?? ''));
    if ($value === '') {
        json_response([
            'success' => false,
            'message' => 'กรุณาระบุ ' . $key,
            'server_time' => api_server_time(),
        ], 422);
    }

    return $value;
}

function api_normalize_datetime(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function api_absolute_url(string $path): string
{
    if (preg_match('/^https?:\/\//i', $path) === 1) {
        return $path;
    }

    return rtrim((string) app_config('base_url', ''), '/') . '/' . ltrim($path, '/');
}
