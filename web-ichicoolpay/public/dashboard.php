<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/models/Device.php';
require_once __DIR__ . '/../app/services/DeviceStatusService.php';

require_admin();

$devices = Device::allWithPlaylist();
$summary = DeviceStatusService::summary($devices);
$recentDevices = Device::activeRecent(8);

$pageTitle = 'แดชบอร์ด';
require app_path('views/layout/header.php');
?>
<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2>สถานะอุปกรณ์</h2>
        </div>
        <a class="button-primary" href="devices.php">จัดการอุปกรณ์</a>
    </div>

    <div class="status-grid dashboard-stats">
        <div>
            <span>อุปกรณ์ทั้งหมด</span>
            <strong><?= (int) $summary['total'] ?></strong>
        </div>
        <div>
            <span>ออนไลน์</span>
            <strong><?= (int) $summary['online'] ?></strong>
        </div>
        <div>
            <span>ออฟไลน์</span>
            <strong><?= (int) $summary['offline'] ?></strong>
        </div>
        <div>
            <span>ปิดใช้งาน</span>
            <strong><?= (int) $summary['inactive'] ?></strong>
        </div>
    </div>

    <h3>อุปกรณ์ล่าสุด</h3>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>รหัสอุปกรณ์</th>
                    <th>ชื่ออุปกรณ์</th>
                    <th>สถานะ</th>
                    <th>เห็นล่าสุด</th>
                    <th>เวอร์ชันแอป</th>
                    <th>เพลย์ลิสต์ปัจจุบัน</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentDevices as $device): ?>
                    <?php $status = DeviceStatusService::statusFor($device['last_seen'], (bool) $device['is_active']); ?>
                    <tr>
                        <td data-label="รหัสอุปกรณ์"><?= htmlspecialchars($device['device_id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="ชื่ออุปกรณ์"><?= htmlspecialchars($device['device_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="สถานะ"><span class="status-badge status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(DeviceStatusService::label($status), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td data-label="เห็นล่าสุด"><?= htmlspecialchars($device['last_seen'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="เวอร์ชันแอป"><?= htmlspecialchars($device['app_version'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="เพลย์ลิสต์ปัจจุบัน"><?= htmlspecialchars($device['playlist_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($recentDevices) === 0): ?>
                    <tr>
                        <td colspan="6" class="empty-cell">ยังไม่มีอุปกรณ์ที่เปิดใช้งาน</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require app_path('views/layout/footer.php');
