<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/validation.php';

if (is_admin_logged_in()) {
    redirect_to('dashboard.php');
}

$error = null;
$cssPath = public_path('assets/css/admin.css');
$cssVersion = is_file($cssPath) ? filemtime($cssPath) : time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    try {
        $username = required_string($_POST, 'username');
        $password = required_string($_POST, 'password');

        if (attempt_admin_login($username, $password)) {
            redirect_to('dashboard.php');
        }

        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    } catch (InvalidArgumentException $exception) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } catch (Throwable $exception) {
        $error = is_production()
            ? 'ไม่สามารถเข้าสู่ระบบได้ในขณะนี้'
            : 'ฐานข้อมูลยังไม่พร้อมหรือการตั้งค่ายังไม่ถูกต้อง';
    }
}

?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ - <?= htmlspecialchars(app_config('app_name'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= htmlspecialchars((string) $cssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <main class="login-page">
        <section class="login-panel">
            <div class="login-brand">
                <img class="login-logo" src="assets/images/logo-ichiplayv2.png" alt="" aria-hidden="true">
            </div>
            <h1>Welcome back!</h1>
            <p>Login to your account</p>
            <?php if ($error !== null): ?>
                <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="login.php" class="login-form">
                <?= csrf_field() ?>
                <label class="login-field">
                    <span class="login-field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <input type="text" name="username" autocomplete="username" placeholder="Username" required autofocus>
                </label>
                <label class="login-field">
                    <span class="login-field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                            <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                        </svg>
                    </span>
                    <input type="password" name="password" autocomplete="current-password" placeholder="Password" required>
                </label>
                <button type="submit">Sign in</button>
            </form>
        </section>
    </main>
</body>
</html>
