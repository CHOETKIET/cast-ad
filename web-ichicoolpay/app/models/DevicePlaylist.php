<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/database.php';

class DevicePlaylist
{
    public static function findForDevice(int $deviceId): ?array
    {
        $statement = db()->prepare(
            'SELECT
                dp.*,
                p.name AS playlist_name,
                p.version AS playlist_version,
                p.is_active AS playlist_is_active
             FROM device_playlist dp
             INNER JOIN playlists p ON p.id = dp.playlist_id
             WHERE dp.device_id = :device_id
             LIMIT 1'
        );
        $statement->execute(['device_id' => $deviceId]);
        $assignment = $statement->fetch();
        return $assignment ?: null;
    }

    public static function findActivePlaylistForDevice(int $deviceId): ?array
    {
        $statement = db()->prepare(
            'SELECT
                dp.*,
                p.id AS playlist_id,
                p.name AS playlist_name,
                p.version AS playlist_version,
                p.is_active AS playlist_is_active
             FROM device_playlist dp
             INNER JOIN playlists p ON p.id = dp.playlist_id
             WHERE dp.device_id = :device_id
               AND p.is_active = 1
             LIMIT 1'
        );
        $statement->execute(['device_id' => $deviceId]);
        $assignment = $statement->fetch();
        return $assignment ?: null;
    }

    public static function assign(int $deviceId, int $playlistId, ?int $assignedBy): void
    {
        $existing = self::findForDevice($deviceId);
        if ($existing === null) {
            $statement = db()->prepare(
                'INSERT INTO device_playlist (device_id, playlist_id, assigned_at, assigned_by)
                 VALUES (:device_id, :playlist_id, NOW(), :assigned_by)'
            );
        } else {
            $statement = db()->prepare(
                'UPDATE device_playlist
                 SET playlist_id = :playlist_id,
                     assigned_at = NOW(),
                     assigned_by = :assigned_by
                 WHERE device_id = :device_id'
            );
        }

        $statement->execute([
            'device_id' => $deviceId,
            'playlist_id' => $playlistId,
            'assigned_by' => $assignedBy,
        ]);
    }
}
