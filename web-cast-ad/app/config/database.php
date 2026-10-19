<?php

return [
    'host' => getenv('CAST_AD_DB_HOST') !== false ? getenv('CAST_AD_DB_HOST') : 'localhost',
    'port' => getenv('CAST_AD_DB_PORT') !== false ? getenv('CAST_AD_DB_PORT') : '3306',
    'socket' => getenv('CAST_AD_DB_SOCKET') !== false ? getenv('CAST_AD_DB_SOCKET') : '/tmp/mysql.sock',
    'database' => getenv('CAST_AD_DB_NAME') !== false ? getenv('CAST_AD_DB_NAME') : 'cast_ad_db',
    'username' => getenv('CAST_AD_DB_USER') !== false ? getenv('CAST_AD_DB_USER') : 'root',
    'password' => getenv('CAST_AD_DB_PASS') !== false ? getenv('CAST_AD_DB_PASS') : '',
    'charset' => 'utf8mb4',
];
