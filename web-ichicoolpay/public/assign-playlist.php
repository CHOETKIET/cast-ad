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
    exit('ไม่พบอุปกรณ์');
}

if ((int) $device['is_active'] !== 1) {
    http_response_code(400);
    exit('ไม่สามารถกำหนดเพลย์ลิสต์ให้อุปกรณ์ที่ปิดใช้งานได้');
}

$assignment = DevicePlaylist::findForDevice($deviceId);
$playlists = Playlist::active();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $playlistId = (int) ($_POST['playlist_id'] ?? 0);
    $playlist = $playlistId > 0 ? Playlist::find($playlistId) : null;

    if ($playlist === null || (int) $playlist['is_active'] !== 1) {
        $errors[] = 'กรุณาเลือกเพลย์ลิสต์ที่เปิดใช้งาน';
    }

    if (count($errors) === 0) {
        $admin = current_admin();
        DevicePlaylist::assign($deviceId, $playlistId, isset($admin['id']) ? (int) $admin['id'] : null);
        flash_set('success', 'กำหนดเพลย์ลิสต์ให้อุปกรณ์แล้ว');
        redirect_to('devices.php');
    }
}

$pageTitle = 'กำหนดเพลย์ลิสต์';
require app_path('views/layout/header.php');
?>
<section class="content-panel form-panel">
    <div class="panel-heading">
        <div>
            <h2>กำหนดเพลย์ลิสต์</h2>
            <p><?= htmlspecialchars($device['device_id'] . ' - ' . $device['device_name'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a class="button-secondary" href="devices.php">กลับ</a>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="assign-playlist.php?device_id=<?= (int) $deviceId ?>" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="device_id" value="<?= (int) $deviceId ?>">
        <label>
            เพลย์ลิสต์
            <select name="playlist_id" required>
                <option value="">เลือกเพลย์ลิสต์</option>
                <?php foreach ($playlists as $playlist): ?>
                    <option value="<?= (int) $playlist['id'] ?>" <?= $assignment && (int) $assignment['playlist_id'] === (int) $playlist['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($playlist['name'] . ' (เวอร์ชัน ' . $playlist['version'] . ')', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if (count($playlists) === 0): ?>
            <div class="alert-error">ยังไม่มีเพลย์ลิสต์ที่เปิดใช้งานให้เลือก</div>
        <?php endif; ?>
        <div class="form-actions">
            <button type="submit" class="button-primary">บันทึกการกำหนด</button>
            <a class="button-secondary" href="devices.php">ยกเลิก</a>
        </div>
    </form>
</section>
<?php
require app_path('views/layout/footer.php');
