<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/database.php';

class Media
{
    public static function all(): array
    {
        $statement = db()->query(
            'SELECT *
             FROM media
             ORDER BY is_active DESC, created_at DESC, id DESC'
        );
        return $statement->fetchAll();
    }

    public static function active(): array
    {
        $statement = db()->query(
            'SELECT *
             FROM media
             WHERE is_active = 1
             ORDER BY media_type ASC, original_name ASC'
        );
        return $statement->fetchAll();
    }

    public static function create(array $data): int
    {
        $statement = db()->prepare(
            'INSERT INTO media (
                media_type,
                original_name,
                file_name,
                file_path,
                public_url,
                mime_type,
                file_size,
                width,
                height,
                duration_seconds,
                is_active,
                created_at,
                updated_at
             ) VALUES (
                :media_type,
                :original_name,
                :file_name,
                :file_path,
                :public_url,
                :mime_type,
                :file_size,
                :width,
                :height,
                :duration_seconds,
                1,
                NOW(),
                NOW()
             )'
        );
        $statement->execute([
            'media_type' => $data['media_type'],
            'original_name' => $data['original_name'],
            'file_name' => $data['file_name'],
            'file_path' => $data['file_path'],
            'public_url' => $data['public_url'],
            'mime_type' => $data['mime_type'],
            'file_size' => $data['file_size'],
            'width' => $data['width'],
            'height' => $data['height'],
            'duration_seconds' => $data['duration_seconds'],
        ]);

        return (int) db()->lastInsertId();
    }

    public static function deactivate(int $id): void
    {
        $statement = db()->prepare(
            'UPDATE media
             SET is_active = 0,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }
}
