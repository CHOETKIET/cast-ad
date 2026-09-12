<?php
$admin = current_admin();
$cssPath = public_path('assets/css/admin.css');
$cssVersion = is_file($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? app_config('app_name'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= htmlspecialchars((string) $cssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <?php require app_path('views/layout/sidebar.php'); ?>
        <div class="admin-main">
            <header class="admin-topbar">
                <div>
                    <h1><?= htmlspecialchars($pageTitle ?? app_config('app_name'), ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
                <div class="admin-user">
                    <span><?= htmlspecialchars($admin['display_name'] ?? 'ผู้ดูแลระบบ', ENT_QUOTES, 'UTF-8') ?></span>
                    <a href="logout.php">ออกจากระบบ</a>
                </div>
            </header>
            <main class="admin-content">
