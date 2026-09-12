<?php

return [
    'host' => getenv('ICHICOOL_DB_HOST') ?: 'localhost',
    'port' => getenv('ICHICOOL_DB_PORT') ?: '3306',
    'socket' => getenv('ICHICOOL_DB_SOCKET') ?: '',
    'database' => getenv('ICHICOOL_DB_NAME') ?: 'softwayg_ichiplay',
    'username' => getenv('ICHICOOL_DB_USER') ?: 'softwayg_ichiplay',
    'password' => getenv('ICHICOOL_DB_PASS') ?: 'waztec-fasgim-6joQco',
    'charset' => 'utf8mb4',
];
