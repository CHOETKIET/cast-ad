<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/database.php';

class PlaylistItem
{
    public static function forPlaylist(int $playlistId): array
    {
        $statement = db()->prepare(
            'SELECT
                pi.*,
                m.media_type,
                m.original_name,
                m.public_url,
                m.file_size,
                m.checksum,
                m.width,
                m.height,
                m.is_active AS media_is_active
             FROM playlist_items pi
             INNER JOIN media m ON m.id = pi.media_id
             WHERE pi.playlist_id = :playlist_id
             ORDER BY pi.sort_order ASC, pi.id ASC'
        );
        $statement->execute(['playlist_id' => $playlistId]);
        return $statement->fetchAll();
    }

    public static function playlistIdsForMedia(int $mediaId): array
    {
        $statement = db()->prepare(
            'SELECT DISTINCT playlist_id
             FROM playlist_items
             WHERE media_id = :media_id'
        );
        $statement->execute(['media_id' => $mediaId]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function add(int $playlistId, int $mediaId, ?int $durationSeconds): void
    {
        $sortOrder = self::nextSortOrder($playlistId);
        $statement = db()->prepare(
            'INSERT INTO playlist_items (
                playlist_id,
                media_id,
                sort_order,
                duration_seconds,
                created_at,
                updated_at
             ) VALUES (
                :playlist_id,
                :media_id,
                :sort_order,
                :duration_seconds,
                NOW(),
                NOW()
             )'
        );
        $statement->execute([
            'playlist_id' => $playlistId,
            'media_id' => $mediaId,
            'sort_order' => $sortOrder,
            'duration_seconds' => $durationSeconds,
        ]);
    }

    public static function updateItem(int $id, int $playlistId, int $sortOrder, ?int $durationSeconds): void
    {
        $statement = db()->prepare(
            'UPDATE playlist_items
             SET sort_order = :sort_order,
                 duration_seconds = :duration_seconds,
                 updated_at = NOW()
             WHERE id = :id AND playlist_id = :playlist_id'
        );
        $statement->execute([
            'id' => $id,
            'playlist_id' => $playlistId,
            'sort_order' => $sortOrder,
            'duration_seconds' => $durationSeconds,
        ]);
    }

    public static function deleteItem(int $id, int $playlistId): void
    {
        $statement = db()->prepare(
            'DELETE FROM playlist_items
             WHERE id = :id AND playlist_id = :playlist_id'
        );
        $statement->execute([
            'id' => $id,
            'playlist_id' => $playlistId,
        ]);
    }

    private static function nextSortOrder(int $playlistId): int
    {
        $statement = db()->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1
             FROM playlist_items
             WHERE playlist_id = :playlist_id'
        );
        $statement->execute(['playlist_id' => $playlistId]);
        return (int) $statement->fetchColumn();
    }
}
