<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/core/bootstrap.php';
require_once __DIR__ . '/../app/core/database.php';

$select = db()->query(
    "SELECT id, file_path
     FROM media
     WHERE checksum IS NULL OR checksum = ''"
);
$mediaRows = $select->fetchAll();

$update = db()->prepare(
    'UPDATE media
     SET checksum = :checksum,
         updated_at = NOW()
     WHERE id = :id'
);

$updated = 0;
$missing = 0;

foreach ($mediaRows as $media) {
    $filePath = public_path((string) $media['file_path']);
    if (!is_file($filePath)) {
        $missing++;
        fwrite(STDERR, 'Missing media file for media #' . (int) $media['id'] . ': ' . $filePath . PHP_EOL);
        continue;
    }

    $checksum = hash_file('sha256', $filePath);
    if (!is_string($checksum) || $checksum === '') {
        fwrite(STDERR, 'Unable to checksum media #' . (int) $media['id'] . ': ' . $filePath . PHP_EOL);
        continue;
    }

    $update->execute([
        'checksum' => strtolower($checksum),
        'id' => (int) $media['id'],
    ]);
    $updated++;
}

echo 'Updated media checksums: ' . $updated . PHP_EOL;
echo 'Missing media files: ' . $missing . PHP_EOL;
