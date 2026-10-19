<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = database_config('host');
    $port = database_config('port');
    $socket = database_config('socket');
    $database = database_config('database');
    $charset = database_config('charset', 'utf8mb4');
    $dsn = $socket !== ''
        ? "mysql:unix_socket={$socket};dbname={$database};charset={$charset}"
        : "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

    $pdo = new PDO($dsn, database_config('username'), database_config('password'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function test_database_connection(): bool
{
    db()->query('SELECT 1');
    return true;
}
