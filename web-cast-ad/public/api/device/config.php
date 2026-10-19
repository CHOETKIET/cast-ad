<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../app/core/bootstrap.php';
require_once __DIR__ . '/../../../app/core/api.php';
require_once __DIR__ . '/../../../app/models/Device.php';
require_once __DIR__ . '/../../../app/models/DevicePlaylist.php';

api_require_method('GET');

$deviceId = trim((string) ($_GET['device_id'] ?? ''));
$orientation = DevicePlaylist::normalizeOrientation((string) ($_GET['orientation'] ?? 'landscape'));
if ($deviceId === '') {
    json_response([
        'success' => false,
        'playlist_id' => null,
        'playlist_version' => 0,
        'sync_interval_seconds' => (int) app_config('sync_interval_seconds', 60),
        'heartbeat_interval_seconds' => (int) app_config('heartbeat_interval_seconds', 60),
        'server_time' => api_server_time(),
        'orientation' => $orientation,
        'message' => 'กรุณาระบุ device_id',
    ], 422);
}

$device = Device::findActiveByDeviceId($deviceId);
if ($device === null) {
    json_response([
        'success' => false,
        'device_id' => $deviceId,
        'playlist_id' => null,
        'playlist_version' => 0,
        'sync_interval_seconds' => (int) app_config('sync_interval_seconds', 60),
        'heartbeat_interval_seconds' => (int) app_config('heartbeat_interval_seconds', 60),
        'server_time' => api_server_time(),
        'orientation' => $orientation,
        'message' => 'ไม่พบอุปกรณ์หรืออุปกรณ์ถูกปิดใช้งาน',
    ], 404);
}

$assignment = DevicePlaylist::findActivePlaylistForDevice((int) $device['id'], $orientation);
$hasActivePlaylist = $assignment !== null;

json_response([
    'success' => true,
    'device_id' => $device['device_id'],
    'playlist_id' => $hasActivePlaylist ? (int) $assignment['playlist_id'] : null,
    'playlist_version' => $hasActivePlaylist ? (int) $assignment['playlist_version'] : 0,
    'orientation' => $orientation,
    'sync_interval_seconds' => (int) app_config('sync_interval_seconds', 60),
    'heartbeat_interval_seconds' => (int) app_config('heartbeat_interval_seconds', 60),
    'force_resync_token' => $device['force_resync_token'] ?? null,
    'force_resync_requested_at' => $device['force_resync_requested_at'] ?? null,
    'server_time' => api_server_time(),
    'message' => $hasActivePlaylist ? 'พร้อมใช้งาน' : 'ยังไม่มีเพลย์ลิสต์ที่เปิดใช้งาน',
]);
