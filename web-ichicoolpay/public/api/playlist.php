<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/core/bootstrap.php';
require_once __DIR__ . '/../../app/core/api.php';
require_once __DIR__ . '/../../app/models/Device.php';
require_once __DIR__ . '/../../app/models/DevicePlaylist.php';
require_once __DIR__ . '/../../app/models/PlaylistItem.php';

api_require_method('GET');

$deviceId = trim((string) ($_GET['device_id'] ?? ''));
if ($deviceId === '') {
    json_response([
        'playlist_id' => 0,
        'playlist_version' => 0,
        'items' => [],
        'success' => false,
        'message' => 'กรุณาระบุ device_id',
        'server_time' => api_server_time(),
    ], 422);
}

$device = Device::findActiveByDeviceId($deviceId);
if ($device === null) {
    json_response([
        'playlist_id' => 0,
        'playlist_version' => 0,
        'items' => [],
        'success' => false,
        'message' => 'ไม่พบอุปกรณ์หรืออุปกรณ์ถูกปิดใช้งาน',
        'server_time' => api_server_time(),
    ], 404);
}

$assignment = DevicePlaylist::findForDevice((int) $device['id']);
if ($assignment === null) {
    json_response([
        'playlist_id' => 0,
        'playlist_version' => 0,
        'items' => [],
        'success' => true,
        'message' => 'ยังไม่มีเพลย์ลิสต์ที่เปิดใช้งาน',
        'server_time' => api_server_time(),
    ]);
}

if ((int) $assignment['playlist_is_active'] !== 1) {
    json_response([
        'playlist_id' => (int) $assignment['playlist_id'],
        'playlist_version' => (int) $assignment['playlist_version'],
        'items' => [],
        'success' => true,
        'message' => 'เพลย์ลิสต์ที่กำหนดไว้ถูกปิดใช้งาน',
        'server_time' => api_server_time(),
    ]);
}

$items = array_values(array_filter(
    PlaylistItem::forPlaylist((int) $assignment['playlist_id']),
    static fn (array $item): bool => (int) $item['media_is_active'] === 1
));

$payloadItems = array_map(static function (array $item): array {
    return [
        'id' => (int) $item['id'],
        'media_id' => (int) $item['media_id'],
        'type' => strtolower((string) $item['media_type']),
        'url' => api_absolute_url((string) $item['public_url']),
        'duration_seconds' => $item['duration_seconds'] === null ? null : (int) $item['duration_seconds'],
        'sort_order' => (int) $item['sort_order'],
        'file_size' => $item['file_size'] === null ? null : (int) $item['file_size'],
        'checksum' => null,
        'local_path' => null,
    ];
}, $items);

json_response([
    'playlist_id' => (int) $assignment['playlist_id'],
    'playlist_version' => (int) $assignment['playlist_version'],
    'items' => $payloadItems,
    'success' => true,
    'message' => 'พร้อมใช้งาน',
    'server_time' => api_server_time(),
]);
