<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/database.php';

class Device
{
    public static function allWithPlaylist(): array
    {
        $statement = db()->query(
            'SELECT
                d.*,
                landscape.name AS landscape_playlist_name,
                landscape.version AS landscape_playlist_version,
                portrait.name AS portrait_playlist_name,
                portrait.version AS portrait_playlist_version
             FROM devices d
             LEFT JOIN device_playlist dp ON dp.device_id = d.id
             LEFT JOIN playlists landscape ON landscape.id = dp.playlist_id
             LEFT JOIN playlists portrait ON portrait.id = dp.portrait_playlist_id
             ORDER BY d.is_active DESC, d.device_name ASC'
        );
        return $statement->fetchAll();
    }

    public static function activeRecent(int $limit = 8): array
    {
        $statement = db()->prepare(
            'SELECT
                d.*,
                landscape.name AS landscape_playlist_name,
                landscape.version AS landscape_playlist_version,
                portrait.name AS portrait_playlist_name,
                portrait.version AS portrait_playlist_version
             FROM devices d
             LEFT JOIN device_playlist dp ON dp.device_id = d.id
             LEFT JOIN playlists landscape ON landscape.id = dp.playlist_id
             LEFT JOIN playlists portrait ON portrait.id = dp.portrait_playlist_id
             WHERE d.is_active = 1
             ORDER BY d.last_seen DESC, d.updated_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $statement = db()->prepare('SELECT * FROM devices WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $device = $statement->fetch();
        return $device ?: null;
    }

    public static function findByDeviceId(string $deviceId): ?array
    {
        $statement = db()->prepare('SELECT * FROM devices WHERE device_id = :device_id LIMIT 1');
        $statement->execute(['device_id' => $deviceId]);
        $device = $statement->fetch();
        return $device ?: null;
    }

    public static function findActiveByDeviceId(string $deviceId): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM devices
             WHERE device_id = :device_id
               AND is_active = 1
             LIMIT 1'
        );
        $statement->execute(['device_id' => $deviceId]);
        $device = $statement->fetch();
        return $device ?: null;
    }

    public static function create(array $data): int
    {
        $statement = db()->prepare(
            'INSERT INTO devices (
                device_id,
                device_name,
                status,
                is_active,
                created_at,
                updated_at
             ) VALUES (
                :device_id,
                :device_name,
                :status,
                :is_active,
                NOW(),
                NOW()
             )'
        );
        $statement->execute([
            'device_id' => $data['device_id'],
            'device_name' => $data['device_name'],
            'status' => 'offline',
            'is_active' => $data['is_active'],
        ]);

        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $statement = db()->prepare(
            'UPDATE devices
             SET device_id = :device_id,
                 device_name = :device_name,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'device_id' => $data['device_id'],
            'device_name' => $data['device_name'],
            'is_active' => $data['is_active'],
        ]);
    }

    public static function deactivate(int $id): void
    {
        $statement = db()->prepare(
            'UPDATE devices
             SET is_active = 0,
                 status = "offline",
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public static function requestForceResync(int $id): string
    {
        $token = bin2hex(random_bytes(16));
        $statement = db()->prepare(
            'UPDATE devices
             SET force_resync_token = :force_resync_token,
                 force_resync_requested_at = NOW(),
                 force_resync_completed_at = NULL,
                 updated_at = NOW()
             WHERE id = :id
               AND is_active = 1'
        );
        $statement->execute([
            'id' => $id,
            'force_resync_token' => $token,
        ]);
        return $token;
    }

    public static function recordHeartbeat(
        int $id,
        string $appVersion,
        int $currentPlaylistVersion,
        ?string $lastSyncTime,
        ?string $lastAppliedResyncToken = null,
        array $status = []
    ): void {
        $device = self::find($id);
        $shouldMarkResyncCompleted = $device !== null
            && $lastAppliedResyncToken !== null
            && hash_equals((string) ($device['force_resync_token'] ?? ''), $lastAppliedResyncToken);

        $statement = db()->prepare(
            'UPDATE devices
             SET status = "online",
                 last_seen = NOW(),
                 app_version = :app_version,
                 current_playlist_version = :current_playlist_version,
                 current_playlist_item_id = :current_playlist_item_id,
                 current_media_id = :current_media_id,
                 current_media_type = :current_media_type,
                 last_playback_time = :last_playback_time,
                 last_sync_time = :last_sync_time,
                 last_download_status = :last_download_status,
                 last_download_message = :last_download_message,
                 last_download_time = :last_download_time,
                 storage_available_bytes = :storage_available_bytes,
                 force_resync_completed_at = CASE
                     WHEN :mark_resync_completed = 1 THEN NOW()
                     ELSE force_resync_completed_at
                 END,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'app_version' => $appVersion,
            'current_playlist_version' => $currentPlaylistVersion,
            'current_playlist_item_id' => $status['current_playlist_item_id'] ?? null,
            'current_media_id' => $status['current_media_id'] ?? null,
            'current_media_type' => $status['current_media_type'] ?? null,
            'last_playback_time' => $status['last_playback_time'] ?? null,
            'last_sync_time' => $lastSyncTime,
            'last_download_status' => $status['last_download_status'] ?? null,
            'last_download_message' => $status['last_download_message'] ?? null,
            'last_download_time' => $status['last_download_time'] ?? null,
            'storage_available_bytes' => $status['storage_available_bytes'] ?? null,
            'mark_resync_completed' => $shouldMarkResyncCompleted ? 1 : 0,
        ]);
    }
}
