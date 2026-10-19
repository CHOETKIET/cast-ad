<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/models/Device.php';
require_once __DIR__ . '/../app/services/DeviceStatusService.php';

require_admin();

$devices = Device::allWithPlaylist();
$summary = DeviceStatusService::summary($devices);
$recentDevices = Device::activeRecent(8);

$pageTitle = t('dashboard');
require app_path('views/layout/header.php');
?>
<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars(t('device_status'), ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
        <a class="button-primary" href="devices.php"><?= htmlspecialchars(t('manage_devices'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>

    <div class="status-grid dashboard-stats">
        <div>
            <span><?= htmlspecialchars(t('all_devices'), ENT_QUOTES, 'UTF-8') ?></span>
            <strong><?= (int) $summary['total'] ?></strong>
        </div>
        <div>
            <span><?= htmlspecialchars(t('online'), ENT_QUOTES, 'UTF-8') ?></span>
            <strong><?= (int) $summary['online'] ?></strong>
        </div>
        <div>
            <span><?= htmlspecialchars(t('offline'), ENT_QUOTES, 'UTF-8') ?></span>
            <strong><?= (int) $summary['offline'] ?></strong>
        </div>
        <div>
            <span><?= htmlspecialchars(t('inactive'), ENT_QUOTES, 'UTF-8') ?></span>
            <strong><?= (int) $summary['inactive'] ?></strong>
        </div>
    </div>

    <h3><?= htmlspecialchars(t('recent_devices'), ENT_QUOTES, 'UTF-8') ?></h3>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentDevices as $device): ?>
                    <?php $status = DeviceStatusService::statusFor($device['last_seen'], (bool) $device['is_active']); ?>
                    <tr>
                        <td data-label="<?= htmlspecialchars(t('device_id'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($device['device_id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('device_name'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($device['device_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('status'), ENT_QUOTES, 'UTF-8') ?>"><span class="status-badge status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t($status), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td data-label="<?= htmlspecialchars(t('last_seen'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($device['last_seen'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('app_version'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($device['app_version'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="<?= htmlspecialchars(t('current_playlist'), ENT_QUOTES, 'UTF-8') ?>">
                            <div><?= htmlspecialchars(t('landscape_playlist'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($device['landscape_playlist_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="muted-text"><?= htmlspecialchars(t('portrait_playlist'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($device['portrait_playlist_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
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
                            <div class="muted-text"><?= htmlspecialchars(t('storage_available'), ENT_QUOTES, 'UTF-8') ?>: <?= $device['storage_available_bytes'] === null ? '-' : htmlspecialchars(format_bytes((int) $device['storage_available_bytes']), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($recentDevices) === 0): ?>
                    <tr>
                        <td colspan="7" class="empty-cell"><?= htmlspecialchars(t('no_active_devices'), ENT_QUOTES, 'UTF-8') ?></td>
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
