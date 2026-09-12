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

Device::recordHeartbeat(
    (int) $device['id'],
    substr($appVersion, 0, 50),
    max(0, $currentPlaylistVersion),
    $lastSyncTime,
    $lastAppliedResyncToken === '' ? null : $lastAppliedResyncToken
);

json_response([
    'success' => true,
    'server_time' => api_server_time(),
    'message' => 'รับข้อมูล heartbeat แล้ว',
]);
