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
                landscape.id AS landscape_playlist_id,
                landscape.name AS landscape_playlist_name,
                landscape.version AS landscape_playlist_version,
                landscape.is_active AS landscape_playlist_is_active,
                portrait.id AS portrait_playlist_id,
                portrait.name AS portrait_playlist_name,
                portrait.version AS portrait_playlist_version,
                portrait.is_active AS portrait_playlist_is_active
             FROM device_playlist dp
             INNER JOIN playlists landscape ON landscape.id = dp.playlist_id
             LEFT JOIN playlists portrait ON portrait.id = dp.portrait_playlist_id
             WHERE dp.device_id = :device_id
             LIMIT 1'
        );
        $statement->execute(['device_id' => $deviceId]);
        $assignment = $statement->fetch();
        return $assignment ?: null;
    }

    public static function findActivePlaylistForDevice(int $deviceId, string $orientation = 'landscape'): ?array
    {
        $assignment = self::findForDevice($deviceId);
        if ($assignment === null) {
            return null;
        }

        $playlistPrefix = self::normalizeOrientation($orientation) === 'portrait' ? 'portrait' : 'landscape';
        if ($playlistPrefix === 'portrait' && $assignment['portrait_playlist_id'] === null) {
            return null;
        }

        if ((int) ($assignment[$playlistPrefix . '_playlist_is_active'] ?? 0) !== 1) {
            return null;
        }

        return [
            'playlist_id' => (int) $assignment[$playlistPrefix . '_playlist_id'],
            'playlist_name' => $assignment[$playlistPrefix . '_playlist_name'],
            'playlist_version' => (int) $assignment[$playlistPrefix . '_playlist_version'],
            'playlist_is_active' => (int) $assignment[$playlistPrefix . '_playlist_is_active'],
            'orientation' => $playlistPrefix,
        ];
    }

    public static function assign(int $deviceId, int $landscapePlaylistId, ?int $portraitPlaylistId, ?int $assignedBy): void
    {
        $existing = self::findForDevice($deviceId);
        if ($existing === null) {
            $statement = db()->prepare(
                'INSERT INTO device_playlist (device_id, playlist_id, portrait_playlist_id, assigned_at, assigned_by)
                 VALUES (:device_id, :landscape_playlist_id, :portrait_playlist_id, NOW(), :assigned_by)'
            );
        } else {
            $statement = db()->prepare(
                'UPDATE device_playlist
                 SET playlist_id = :landscape_playlist_id,
                     portrait_playlist_id = :portrait_playlist_id,
                     assigned_at = NOW(),
                     assigned_by = :assigned_by
                 WHERE device_id = :device_id'
            );
        }

        $statement->execute([
            'device_id' => $deviceId,
            'landscape_playlist_id' => $landscapePlaylistId,
            'portrait_playlist_id' => $portraitPlaylistId,
            'assigned_by' => $assignedBy,
        ]);
    }

    public static function normalizeOrientation(string $orientation): string
    {
        return strtolower(trim($orientation)) === 'portrait' ? 'portrait' : 'landscape';
    }
}
