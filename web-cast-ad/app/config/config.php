<?php

return [
    'app_name' => 'Cast Ad',
    'base_url' => getenv('CAST_AD_BASE_URL') !== false ? getenv('CAST_AD_BASE_URL') : 'https://cast-ad.example.com/',
    'public_directory' => getenv('CAST_AD_PUBLIC_DIRECTORY') !== false ? getenv('CAST_AD_PUBLIC_DIRECTORY') : 'public',
    'timezone' => 'Asia/Bangkok',
    'environment' => getenv('CAST_AD_ENV') !== false ? getenv('CAST_AD_ENV') : 'local',
    'online_threshold_seconds' => 120,
    'sync_interval_seconds' => 60,
    'heartbeat_interval_seconds' => 60,
];
