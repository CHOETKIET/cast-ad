<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/session.php';

function current_admin(): ?array
{
    start_app_session();
    return isset($_SESSION['admin']) && is_array($_SESSION['admin']) ? $_SESSION['admin'] : null;
}

function is_admin_logged_in(): bool
{
    return current_admin() !== null;
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect_to('login.php');
    }
}

function attempt_admin_login(string $username, string $password): bool
{
    $statement = db()->prepare(
        'SELECT id, username, password_hash, display_name
         FROM users
         WHERE username = :username AND is_active = 1
         LIMIT 1'
    );
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    start_app_session();
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'display_name' => $user['display_name'] ?: $user['username'],
    ];

    return true;
}

function logout_admin(): void
{
    start_app_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}
