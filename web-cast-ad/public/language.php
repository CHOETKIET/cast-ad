<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/response.php';

$language = (string) ($_GET['lang'] ?? 'th');
set_current_language($language);

$returnTo = (string) ($_GET['return'] ?? 'dashboard.php');
if ($returnTo === '' || strpos($returnTo, "\n") !== false || strpos($returnTo, "\r") !== false) {
    $returnTo = 'dashboard.php';
}

if (preg_match('#^https?://#i', $returnTo) === 1 || strpos($returnTo, '//') === 0) {
    $returnTo = 'dashboard.php';
}

redirect_to($returnTo);
