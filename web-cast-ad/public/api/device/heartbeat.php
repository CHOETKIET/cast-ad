<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/core/bootstrap.php';
require_once __DIR__ . '/../../../app/core/api.php';
require_once __DIR__ . '/../../../app/models/Device.php';

api_require_method('POST');

$input = api_json_input();
$deviceId = api_required_string($input, 'device_id');
$appVersion = trim((string) ($input['app_version'] ?? ''));
if ($appVersion === '') {
    $appVersion = 'unknown';
}

$device = Device::findActiveByDeviceId($deviceId);
if ($device === null) {
    json_response([
        'success' => false,
        'server_time' => api_server_time(),
        'message' => 'ไม่พบอุปกรณ์หรืออุปกรณ์ถูกปิดใช้งาน',
    ], 404);
}

$currentPlaylistVersion = (int) ($input['current_playlist_version'] ?? 0);
$lastSyncTime = api_normalize_datetime($input['last_sync_time'] ?? null);
$lastAppliedResyncToken = trim((string) ($input['last_applied_resync_token'] ?? ''));
$currentPlaylistItemId = api_optional_positive_int($input['current_playlist_item_id'] ?? null);
$currentMediaId = api_optional_positive_int($input['current_media_id'] ?? null);
$currentMediaType = api_optional_media_type($input['current_media_type'] ?? null);
$lastPlaybackTime = api_normalize_datetime($input['last_playback_time'] ?? null);
$lastDownloadStatus = api_optional_download_status($input['last_download_status'] ?? null);
$lastDownloadMessage = api_optional_short_text($input['last_download_message'] ?? null, 255);
$lastDownloadTime = api_normalize_datetime($input['last_download_time'] ?? null);
$storageAvailableBytes = api_optional_non_negative_int($input['storage_available_bytes'] ?? null);

Device::recordHeartbeat(
    (int) $device['id'],
    substr($appVersion, 0, 50),
    max(0, $currentPlaylistVersion),
    $lastSyncTime,
    $lastAppliedResyncToken === '' ? null : $lastAppliedResyncToken,
    [
        'current_playlist_item_id' => $currentPlaylistItemId,
        'current_media_id' => $currentMediaId,
        'current_media_type' => $currentMediaType,
        'last_playback_time' => $lastPlaybackTime,
        'last_download_status' => $lastDownloadStatus,
        'last_download_message' => $lastDownloadMessage,
        'last_download_time' => $lastDownloadTime,
        'storage_available_bytes' => $storageAvailableBytes,
    ]
);

json_response([
    'success' => true,
    'server_time' => api_server_time(),
    'message' => 'รับข้อมูล heartbeat แล้ว',
]);

function api_optional_positive_int($value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $integer = (int) $value;
    return $integer > 0 ? $integer : null;
}

function api_optional_non_negative_int($value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $integer = (int) $value;
    return $integer >= 0 ? $integer : null;
}

function api_optional_media_type($value): ?string
{
    $mediaType = strtolower(trim((string) $value));
    return in_array($mediaType, ['image', 'video'], true) ? $mediaType : null;
}

function api_optional_download_status($value): ?string
{
    $status = strtolower(trim((string) $value));
    return in_array($status, ['success', 'failed'], true) ? $status : null;
}

function api_optional_short_text($value, int $maxLength): ?string
{
    $text = trim((string) $value);
    if ($text === '') {
        return null;
    }

    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $maxLength);
    }

    return substr($text, 0, $maxLength);
}
