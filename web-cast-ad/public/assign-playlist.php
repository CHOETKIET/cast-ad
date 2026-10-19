<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/flash.php';
require_once __DIR__ . '/../app/models/Device.php';
require_once __DIR__ . '/../app/models/DevicePlaylist.php';
require_once __DIR__ . '/../app/models/Playlist.php';

require_admin();

$deviceId = (int) ($_GET['device_id'] ?? $_POST['device_id'] ?? 0);
$device = $deviceId > 0 ? Device::find($deviceId) : null;
if ($device === null) {
    http_response_code(404);
    exit(current_language() === 'en' ? 'Device not found' : 'ไม่พบอุปกรณ์');
}

if ((int) $device['is_active'] !== 1) {
    http_response_code(400);
    exit(current_language() === 'en' ? 'Cannot assign a playlist to an inactive device' : 'ไม่สามารถกำหนดเพลย์ลิสต์ให้อุปกรณ์ที่ปิดใช้งานได้');
}

$assignment = DevicePlaylist::findForDevice($deviceId);
$playlists = Playlist::active();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $landscapePlaylistId = (int) ($_POST['landscape_playlist_id'] ?? 0);
    $portraitPlaylistId = (int) ($_POST['portrait_playlist_id'] ?? 0);
    $landscapePlaylist = $landscapePlaylistId > 0 ? Playlist::find($landscapePlaylistId) : null;
    $portraitPlaylist = $portraitPlaylistId > 0 ? Playlist::find($portraitPlaylistId) : null;

    if ($landscapePlaylist === null || (int) $landscapePlaylist['is_active'] !== 1) {
        $errors[] = current_language() === 'en' ? 'Please select an active landscape playlist' : 'กรุณาเลือกเพลย์ลิสต์แนวนอนที่เปิดใช้งาน';
    }

    if ($portraitPlaylistId > 0 && ($portraitPlaylist === null || (int) $portraitPlaylist['is_active'] !== 1)) {
        $errors[] = current_language() === 'en' ? 'Please select an active portrait playlist' : 'กรุณาเลือกเพลย์ลิสต์แนวตั้งที่เปิดใช้งาน';
    }

    if (count($errors) === 0) {
        $admin = current_admin();
        DevicePlaylist::assign($deviceId, $landscapePlaylistId, $portraitPlaylistId > 0 ? $portraitPlaylistId : null, isset($admin['id']) ? (int) $admin['id'] : null);
        flash_set('success', current_language() === 'en' ? 'Playlist assigned to device' : 'กำหนดเพลย์ลิสต์ให้อุปกรณ์แล้ว');
        redirect_to('devices.php');
    }
}

$pageTitle = t('assign_playlist');
require app_path('views/layout/header.php');
?>
<section class="content-panel form-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars(t('assign_playlist'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($device['device_id'] . ' - ' . $device['device_name'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a class="button-secondary" href="devices.php"><?= htmlspecialchars(t('back'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="assign-playlist.php?device_id=<?= (int) $deviceId ?>" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="device_id" value="<?= (int) $deviceId ?>">
        <label>
            <?= htmlspecialchars(t('landscape_playlist'), ENT_QUOTES, 'UTF-8') ?>
            <select name="landscape_playlist_id" required data-playlist-preview-select="landscape">
                <option value=""><?= htmlspecialchars(t('select_playlist'), ENT_QUOTES, 'UTF-8') ?></option>
                <?php foreach ($playlists as $playlist): ?>
                    <option value="<?= (int) $playlist['id'] ?>" <?= $assignment && (int) $assignment['landscape_playlist_id'] === (int) $playlist['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($playlist['name'] . ' (' . t('version') . ' ' . $playlist['version'] . ')', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a class="button-secondary playlist-preview-link" href="#" data-playlist-preview-link="landscape" target="_blank" rel="noopener"><?= htmlspecialchars(t('preview_landscape_playlist'), ENT_QUOTES, 'UTF-8') ?></a>
        </label>
        <label>
            <?= htmlspecialchars(t('portrait_playlist'), ENT_QUOTES, 'UTF-8') ?>
            <select name="portrait_playlist_id" data-playlist-preview-select="portrait">
                <option value=""><?= htmlspecialchars(t('select_optional_playlist'), ENT_QUOTES, 'UTF-8') ?></option>
                <?php foreach ($playlists as $playlist): ?>
                    <option value="<?= (int) $playlist['id'] ?>" <?= $assignment && $assignment['portrait_playlist_id'] !== null && (int) $assignment['portrait_playlist_id'] === (int) $playlist['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($playlist['name'] . ' (' . t('version') . ' ' . $playlist['version'] . ')', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a class="button-secondary playlist-preview-link" href="#" data-playlist-preview-link="portrait" target="_blank" rel="noopener"><?= htmlspecialchars(t('preview_portrait_playlist'), ENT_QUOTES, 'UTF-8') ?></a>
        </label>
        <?php if (count($playlists) === 0): ?>
            <div class="alert-error"><?= htmlspecialchars(current_language() === 'en' ? 'No active playlists are available' : 'ยังไม่มีเพลย์ลิสต์ที่เปิดใช้งานให้เลือก', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="form-actions">
            <button type="submit" class="button-primary"><?= htmlspecialchars(t('save_assignment'), ENT_QUOTES, 'UTF-8') ?></button>
            <a class="button-secondary" href="devices.php"><?= htmlspecialchars(t('cancel'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </form>
</section>
<script>
(function () {
    function wirePreviewLink(name) {
        const select = document.querySelector('[data-playlist-preview-select="' + name + '"]');
        const link = document.querySelector('[data-playlist-preview-link="' + name + '"]');
        if (!select || !link) {
            return;
        }

        function updateLink() {
            if (select.value) {
                link.href = 'playlist-preview.php?id=' + encodeURIComponent(select.value);
                link.style.display = 'inline-flex';
            } else {
                link.removeAttribute('href');
                link.style.display = 'none';
            }
        }

        select.addEventListener('change', updateLink);
        updateLink();
    }

    wirePreviewLink('landscape');
    wirePreviewLink('portrait');
})();
</script>
<?php
require app_path('views/layout/footer.php');
