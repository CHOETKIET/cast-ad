<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/database.php';

class Playlist
{
    public static function allWithCounts(): array
    {
        $statement = db()->query(
            'SELECT
                p.*,
                COUNT(pi.id) AS item_count
             FROM playlists p
             LEFT JOIN playlist_items pi ON pi.playlist_id = p.id
             GROUP BY p.id
             ORDER BY p.is_active DESC, p.updated_at DESC, p.id DESC'
        );
        return $statement->fetchAll();
    }

    public static function active(): array
    {
        $statement = db()->query(
            'SELECT *
             FROM playlists
             WHERE is_active = 1
             ORDER BY name ASC'
        );
        return $statement->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $statement = db()->prepare('SELECT * FROM playlists WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $playlist = $statement->fetch();
        return $playlist ?: null;
    }

    public static function create(array $data): int
    {
        $statement = db()->prepare(
            'INSERT INTO playlists (
                name,
                description,
                version,
                is_active,
                created_at,
                updated_at
             ) VALUES (
                :name,
                :description,
                1,
                :is_active,
                NOW(),
                NOW()
             )'
        );
        $statement->execute([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $data['is_active'],
        ]);

        return (int) db()->lastInsertId();
    }

    public static function updateInfo(int $id, array $data): void
    {
        $statement = db()->prepare(
            'UPDATE playlists
             SET name = :name,
                 description = :description,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $data['is_active'],
        ]);
    }

    public static function updateInfoAndVersion(int $id, array $data): void
    {
        $statement = db()->prepare(
            'UPDATE playlists
             SET name = :name,
                 description = :description,
                 is_active = :is_active,
                 version = version + 1,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $data['is_active'],
        ]);
    }

    public static function incrementVersion(int $id): void
    {
        $statement = db()->prepare(
            'UPDATE playlists
             SET version = version + 1,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public static function deactivate(int $id): void
    {
        $statement = db()->prepare(
            'UPDATE playlists
             SET is_active = 0,
                 version = version + 1,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }
}
