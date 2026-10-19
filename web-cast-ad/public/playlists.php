<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/flash.php';
require_once __DIR__ . '/../app/models/Playlist.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'deactivate' && $id > 0) {
        Playlist::deactivate($id);
        flash_set('success', current_language() === 'en' ? 'Playlist deactivated' : 'ปิดใช้งานเพลย์ลิสต์แล้ว');
    }

    redirect_to('playlists.php');
}

$playlists = Playlist::allWithCounts();
$success = flash_get('success');
$pageTitle = t('playlists');
require app_path('views/layout/header.php');
?>
<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars(t('manage_playlists'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('playlist_intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a class="button-primary" href="playlist-edit.php"><?= htmlspecialchars(t('create_playlist'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>

    <?php if ($success !== null): ?>
        <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars(t('playlist_name'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('version'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('item_count'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('status'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('updated_at'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($playlists as $playlist): ?>
                    <tr>
                        <td data-label="<?= htmlspecialchars(t('playlist_name'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($playlist['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('version'), ENT_QUOTES, 'UTF-8') ?>"><?= (int) $playlist['version'] ?></td>
                        <td data-label="<?= htmlspecialchars(t('item_count'), ENT_QUOTES, 'UTF-8') ?>"><?= (int) $playlist['item_count'] ?></td>
                        <td data-label="<?= htmlspecialchars(t('status'), ENT_QUOTES, 'UTF-8') ?>"><span class="status-badge <?= (int) $playlist['is_active'] === 1 ? 'status-online' : 'status-inactive' ?>"><?= (int) $playlist['is_active'] === 1 ? htmlspecialchars(t('active'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(t('inactive'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td data-label="<?= htmlspecialchars(t('updated_at'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($playlist['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="table-actions" data-label="<?= htmlspecialchars(t('actions'), ENT_QUOTES, 'UTF-8') ?>">
                            <a href="playlist-preview.php?id=<?= (int) $playlist['id'] ?>"><?= htmlspecialchars(t('preview_playlist'), ENT_QUOTES, 'UTF-8') ?></a>
                            <a href="playlist-edit.php?id=<?= (int) $playlist['id'] ?>"><?= htmlspecialchars(t('edit'), ENT_QUOTES, 'UTF-8') ?></a>
                            <?php if ((int) $playlist['is_active'] === 1): ?>
                                <form method="post" action="playlists.php" onsubmit="return confirm('ปิดใช้งาน playlist นี้หรือไม่?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="id" value="<?= (int) $playlist['id'] ?>">
                                    <button type="submit"><?= htmlspecialchars(t('inactive'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($playlists) === 0): ?>
                    <tr>
                        <td colspan="6" class="empty-cell"><?= htmlspecialchars(t('no_playlists'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require app_path('views/layout/footer.php');
