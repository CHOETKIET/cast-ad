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
        flash_set('success', 'ปิดใช้งานเพลย์ลิสต์แล้ว');
    }

    redirect_to('playlists.php');
}

$playlists = Playlist::allWithCounts();
$success = flash_get('success');
$pageTitle = 'เพลย์ลิสต์';
require app_path('views/layout/header.php');
?>
<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2>จัดการเพลย์ลิสต์</h2>
            <p>สร้างและจัดลำดับรูปภาพ/วิดีโอสำหรับ Android Player</p>
        </div>
        <a class="button-primary" href="playlist-edit.php">สร้างเพลย์ลิสต์</a>
    </div>

    <?php if ($success !== null): ?>
        <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ชื่อเพลย์ลิสต์</th>
                    <th>เวอร์ชัน</th>
                    <th>จำนวนรายการ</th>
                    <th>สถานะ</th>
                    <th>อัปเดตล่าสุด</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($playlists as $playlist): ?>
                    <tr>
                        <td data-label="ชื่อเพลย์ลิสต์"><?= htmlspecialchars($playlist['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="เวอร์ชัน"><?= (int) $playlist['version'] ?></td>
                        <td data-label="จำนวนรายการ"><?= (int) $playlist['item_count'] ?></td>
                        <td data-label="สถานะ"><span class="status-badge <?= (int) $playlist['is_active'] === 1 ? 'status-online' : 'status-inactive' ?>"><?= (int) $playlist['is_active'] === 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน' ?></span></td>
                        <td data-label="อัปเดตล่าสุด"><?= htmlspecialchars($playlist['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="table-actions" data-label="จัดการ">
                            <a href="playlist-edit.php?id=<?= (int) $playlist['id'] ?>">แก้ไข</a>
                            <?php if ((int) $playlist['is_active'] === 1): ?>
                                <form method="post" action="playlists.php" onsubmit="return confirm('ปิดใช้งาน playlist นี้หรือไม่?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="id" value="<?= (int) $playlist['id'] ?>">
                                    <button type="submit">ปิดใช้งาน</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($playlists) === 0): ?>
                    <tr>
                        <td colspan="6" class="empty-cell">ยังไม่มีเพลย์ลิสต์</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require app_path('views/layout/footer.php');
