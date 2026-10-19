<?php
$admin = current_admin();
$language = current_language();
$cssPath = public_path('assets/css/admin.css');
$cssVersion = is_file($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>">
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
                    <span><?= htmlspecialchars($admin['display_name'] ?? t('admin_name'), ENT_QUOTES, 'UTF-8') ?></span>
                    <a href="logout.php"><?= htmlspecialchars(t('logout'), ENT_QUOTES, 'UTF-8') ?></a>
                    <div class="language-switch" aria-label="Language">
                        <a class="<?= $language === 'en' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(language_switch_url('en'), ENT_QUOTES, 'UTF-8') ?>">EN</a>
                        <span>/</span>
                        <a class="<?= $language === 'th' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(language_switch_url('th'), ENT_QUOTES, 'UTF-8') ?>">TH</a>
                    </div>
                </div>
            </header>
            <main class="admin-content">
