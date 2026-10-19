<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Playlist.php';

class PlaylistVersionService
{
    public static function markContentChanged(int $playlistId): void
    {
        Playlist::incrementVersion($playlistId);
    }
}
