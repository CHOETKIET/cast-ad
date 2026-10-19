<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/flash.php';
require_once __DIR__ . '/../app/models/Media.php';
require_once __DIR__ . '/../app/models/PlaylistItem.php';
require_once __DIR__ . '/../app/services/PlaylistVersionService.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'deactivate' && $id > 0) {
        $affectedPlaylistIds = PlaylistItem::playlistIdsForMedia($id);
        Media::deactivate($id);
        foreach ($affectedPlaylistIds as $playlistId) {
            PlaylistVersionService::markContentChanged($playlistId);
        }
        $message = current_language() === 'en' ? 'Media deactivated' : 'ปิดใช้งานสื่อแล้ว';
        if (count($affectedPlaylistIds) > 0) {
            $message .= current_language() === 'en'
                ? ' and updated ' . count($affectedPlaylistIds) . ' related playlist version(s)'
                : ' และอัปเดตเวอร์ชันเพลย์ลิสต์ที่เกี่ยวข้อง ' . count($affectedPlaylistIds) . ' รายการ';
        }
        flash_set('success', $message);
    }

    redirect_to('media.php');
}

$mediaItems = Media::all();
$success = flash_get('success');
$pageTitle = t('media_library');
require app_path('views/layout/header.php');
?>
<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars(t('media_library'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('media_intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a class="button-primary" href="media-upload.php"><?= htmlspecialchars(t('upload_media'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>

    <?php if ($success !== null): ?>
        <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars(t('preview'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('file_name'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('type'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('size'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('dimensions'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th>URL</th>
                    <th><?= htmlspecialchars(t('status'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mediaItems as $item): ?>
                    <tr>
                        <td data-label="<?= htmlspecialchars(t('preview'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($item['media_type'] === 'image'): ?>
                                <img class="media-preview" src="<?= htmlspecialchars($item['public_url'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                            <?php else: ?>
                                <span class="media-video-preview">MP4</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= htmlspecialchars(t('file_name'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('type'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(media_type_label($item['media_type']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('size'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(format_bytes((int) $item['file_size']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('dimensions'), ENT_QUOTES, 'UTF-8') ?>"><?= $item['width'] && $item['height'] ? (int) $item['width'] . ' x ' . (int) $item['height'] : '-' ?></td>
                        <td data-label="URL"><a href="<?= htmlspecialchars($item['public_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars(t('open'), ENT_QUOTES, 'UTF-8') ?></a></td>
                        <td data-label="<?= htmlspecialchars(t('status'), ENT_QUOTES, 'UTF-8') ?>"><span class="status-badge <?= (int) $item['is_active'] === 1 ? 'status-online' : 'status-inactive' ?>"><?= (int) $item['is_active'] === 1 ? htmlspecialchars(t('active'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(t('inactive'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="table-actions" data-label="<?= htmlspecialchars(t('actions'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ((int) $item['is_active'] === 1): ?>
                                <form method="post" action="media.php" onsubmit="return confirm('ปิดใช้งาน media นี้หรือไม่?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit"><?= htmlspecialchars(t('inactive'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($mediaItems) === 0): ?>
                    <tr>
                        <td colspan="8" class="empty-cell"><?= htmlspecialchars(t('no_media'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require app_path('views/layout/footer.php');

function media_type_label(string $mediaType): string
{
    return $mediaType === 'image' ? t('image') : t('video');
}
