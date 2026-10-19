<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/flash.php';
require_once __DIR__ . '/../app/models/Device.php';
require_once __DIR__ . '/../app/services/DeviceStatusService.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'deactivate' && $id > 0) {
        Device::deactivate($id);
        flash_set('success', current_language() === 'en' ? 'Device deactivated' : 'ปิดใช้งานอุปกรณ์แล้ว');
    }

    if ($action === 'force_resync' && $id > 0) {
        Device::requestForceResync($id);
        flash_set('success', current_language() === 'en' ? 'Re-sync command sent. Android will receive it on the next sync cycle.' : 'ส่งคำสั่ง Re-sync Now แล้ว Android จะรับคำสั่งในรอบ sync ถัดไป');
    }

    redirect_to('devices.php');
}

$devices = Device::allWithPlaylist();
$success = flash_get('success');
$pageTitle = t('devices');
require app_path('views/layout/header.php');
?>
<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars(t('manage_devices'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('device_intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a class="button-primary" href="device-form.php"><?= htmlspecialchars(t('add_device'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>

    <?php if ($success !== null): ?>
        <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars(t('device_id'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('device_name'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('status'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('last_seen'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('app_version'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('current_playlist'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('device_telemetry'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('resync'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devices as $device): ?>
                    <?php $status = DeviceStatusService::statusFor($device['last_seen'], (bool) $device['is_active']); ?>
                    <tr>
                        <td data-label="<?= htmlspecialchars(t('device_id'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($device['device_id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('device_name'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($device['device_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('status'), ENT_QUOTES, 'UTF-8') ?>"><span class="status-badge status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t($status), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td data-label="<?= htmlspecialchars(t('last_seen'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($device['last_seen'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('app_version'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($device['app_version'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('current_playlist'), ENT_QUOTES, 'UTF-8') ?>">
                            <div>
                                <?= htmlspecialchars(t('landscape_playlist'), ENT_QUOTES, 'UTF-8') ?>:
                                <?= htmlspecialchars($device['landscape_playlist_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($device['landscape_playlist_version'] !== null): ?>
                                    <span class="muted-text">v<?= (int) $device['landscape_playlist_version'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="muted-text">
                                <?= htmlspecialchars(t('portrait_playlist'), ENT_QUOTES, 'UTF-8') ?>:
                                <?= htmlspecialchars($device['portrait_playlist_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($device['portrait_playlist_version'] !== null): ?>
                                    v<?= (int) $device['portrait_playlist_version'] ?>
                                <?php endif; ?>
                            </div>
                            <div class="muted-text"><?= htmlspecialchars(t('version'), ENT_QUOTES, 'UTF-8') ?>: v<?= (int) $device['current_playlist_version'] ?></div>
                        </td>
                        <td data-label="<?= htmlspecialchars(t('device_telemetry'), ENT_QUOTES, 'UTF-8') ?>">
                            <div>
                                <?= htmlspecialchars(t('playing_item'), ENT_QUOTES, 'UTF-8') ?>:
                                <?= $device['current_playlist_item_id'] === null ? '-' : htmlspecialchars(t('item_number'), ENT_QUOTES, 'UTF-8') . ' #' . (int) $device['current_playlist_item_id'] ?>
                                <?php if ($device['current_media_id'] !== null): ?>
                                    <span class="muted-text">(<?= htmlspecialchars(t('media_number'), ENT_QUOTES, 'UTF-8') ?> #<?= (int) $device['current_media_id'] ?><?= $device['current_media_type'] ? ', ' . htmlspecialchars($device['current_media_type'], ENT_QUOTES, 'UTF-8') : '' ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="muted-text"><?= htmlspecialchars(t('last_played_at'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($device['last_playback_time'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="muted-text">
                                <?= htmlspecialchars(t('download_status'), ENT_QUOTES, 'UTF-8') ?>:
                                <?= htmlspecialchars(device_download_status_label($device['last_download_status'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($device['last_download_time'])): ?>
                                    <?= htmlspecialchars($device['last_download_time'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($device['last_download_message'])): ?>
                                <div class="muted-text"><?= htmlspecialchars($device['last_download_message'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <div class="muted-text"><?= htmlspecialchars(t('storage_available'), ENT_QUOTES, 'UTF-8') ?>: <?= $device['storage_available_bytes'] === null ? '-' : htmlspecialchars(format_bytes((int) $device['storage_available_bytes']), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td data-label="<?= htmlspecialchars(t('resync'), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="muted-text"><?= htmlspecialchars(t('requested_at'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($device['force_resync_requested_at'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="muted-text"><?= htmlspecialchars(t('completed_at'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($device['force_resync_completed_at'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td class="table-actions" data-label="<?= htmlspecialchars(t('actions'), ENT_QUOTES, 'UTF-8') ?>">
                            <a href="device-form.php?id=<?= (int) $device['id'] ?>"><?= htmlspecialchars(t('edit'), ENT_QUOTES, 'UTF-8') ?></a>
                            <?php if ((bool) $device['is_active']): ?>
                                <a href="assign-playlist.php?device_id=<?= (int) $device['id'] ?>"><?= htmlspecialchars(t('assign_playlist'), ENT_QUOTES, 'UTF-8') ?></a>
                                <form method="post" action="devices.php" onsubmit="return confirm('บังคับให้ device นี้ re-sync ข้อมูลใหม่ทั้งหมดหรือไม่?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="force_resync">
                                    <input type="hidden" name="id" value="<?= (int) $device['id'] ?>">
                                    <button type="submit"><?= htmlspecialchars(t('resync_now'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                                <form method="post" action="devices.php" onsubmit="return confirm('ปิดใช้งาน device นี้หรือไม่?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="id" value="<?= (int) $device['id'] ?>">
                                    <button type="submit"><?= htmlspecialchars(t('inactive'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($devices) === 0): ?>
                    <tr>
                        <td colspan="9" class="empty-cell"><?= htmlspecialchars(t('no_devices'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require app_path('views/layout/footer.php');

function device_download_status_label(?string $status): string
{
    if ($status === 'success') {
        return t('download_success');
    }

    if ($status === 'failed') {
        return t('download_failed');
    }

    return '-';
}
