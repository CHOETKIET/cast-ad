<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

class DeviceStatusService
{
    public static function statusFor(?string $lastSeen, bool $isActive = true): string
    {
        if (!$isActive) {
            return 'inactive';
        }

        if ($lastSeen === null || $lastSeen === '') {
            return 'offline';
        }

        $lastSeenTimestamp = strtotime($lastSeen);
        if ($lastSeenTimestamp === false) {
            return 'offline';
        }

        $threshold = (int) app_config('online_threshold_seconds', 120);
        return (time() - $lastSeenTimestamp) <= $threshold ? 'online' : 'offline';
    }

    public static function label(string $status): string
    {
        $labels = [
            'online' => 'ออนไลน์',
            'offline' => 'ออฟไลน์',
            'inactive' => 'ปิดใช้งาน',
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    public static function summary(array $devices): array
    {
        $summary = [
            'total' => 0,
            'online' => 0,
            'offline' => 0,
            'inactive' => 0,
        ];

        foreach ($devices as $device) {
            $status = self::statusFor($device['last_seen'] ?? null, (bool) $device['is_active']);
            $summary['total']++;
            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
        }

        return $summary;
    }
}
