<?php

return [
    'app_name' => 'iCHI-COOL Play',
    'base_url' => getenv('ICHICOOL_BASE_URL') ?: 'https://ichiplay.ichicool.com/',
    'public_directory' => getenv('ICHICOOL_PUBLIC_DIRECTORY') ?: 'public',
    'timezone' => 'Asia/Bangkok',
    'environment' => getenv('ICHICOOL_ENV') ?: 'local',
    'online_threshold_seconds' => 120,
    'sync_interval_seconds' => 60,
    'heartbeat_interval_seconds' => 60,
];
