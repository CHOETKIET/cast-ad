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
        flash_set('success', 'ปิดใช้งานอุปกรณ์แล้ว');
    }

    if ($action === 'force_resync' && $id > 0) {
        Device::requestForceResync($id);
        flash_set('success', 'ส่งคำสั่ง Re-sync Now แล้ว Android จะรับคำสั่งในรอบ sync ถัดไป');
    }

    redirect_to('devices.php');
}

$devices = Device::allWithPlaylist();
$success = flash_get('success');
$pageTitle = 'อุปกรณ์';
require app_path('views/layout/header.php');
?>
<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2>จัดการอุปกรณ์</h2>
            <p>จัดการ Android Box และตรวจสอบสถานะล่าสุด</p>
        </div>
        <a class="button-primary" href="device-form.php">เพิ่มอุปกรณ์</a>
    </div>

    <?php if ($success !== null): ?>
        <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

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
                    <th>รีซิงก์</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devices as $device): ?>
                    <?php $status = DeviceStatusService::statusFor($device['last_seen'], (bool) $device['is_active']); ?>
                    <tr>
                        <td data-label="รหัสอุปกรณ์"><?= htmlspecialchars($device['device_id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="ชื่ออุปกรณ์"><?= htmlspecialchars($device['device_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="สถานะ"><span class="status-badge status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(DeviceStatusService::label($status), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td data-label="เห็นล่าสุด"><?= htmlspecialchars($device['last_seen'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="เวอร์ชันแอป"><?= htmlspecialchars($device['app_version'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="เพลย์ลิสต์ปัจจุบัน">
                            <?= htmlspecialchars($device['playlist_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($device['assigned_playlist_version'] !== null): ?>
                                <span class="muted-text">v<?= (int) $device['assigned_playlist_version'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="รีซิงก์">
                            <div class="muted-text">ขอเมื่อ: <?= htmlspecialchars($device['force_resync_requested_at'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="muted-text">สำเร็จเมื่อ: <?= htmlspecialchars($device['force_resync_completed_at'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td class="table-actions" data-label="จัดการ">
                            <a href="device-form.php?id=<?= (int) $device['id'] ?>">แก้ไข</a>
                            <?php if ((bool) $device['is_active']): ?>
                                <a href="assign-playlist.php?device_id=<?= (int) $device['id'] ?>">กำหนดเพลย์ลิสต์</a>
                                <form method="post" action="devices.php" onsubmit="return confirm('บังคับให้ device นี้ re-sync ข้อมูลใหม่ทั้งหมดหรือไม่?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="force_resync">
                                    <input type="hidden" name="id" value="<?= (int) $device['id'] ?>">
                                    <button type="submit">รีซิงก์ทันที</button>
                                </form>
                                <form method="post" action="devices.php" onsubmit="return confirm('ปิดใช้งาน device นี้หรือไม่?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="id" value="<?= (int) $device['id'] ?>">
                                    <button type="submit">ปิดใช้งาน</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($devices) === 0): ?>
                    <tr>
                        <td colspan="8" class="empty-cell">ยังไม่มีอุปกรณ์</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require app_path('views/layout/footer.php');
