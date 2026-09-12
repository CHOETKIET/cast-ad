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
        $message = 'ปิดใช้งานสื่อแล้ว';
        if (count($affectedPlaylistIds) > 0) {
            $message .= ' และอัปเดตเวอร์ชันเพลย์ลิสต์ที่เกี่ยวข้อง ' . count($affectedPlaylistIds) . ' รายการ';
        }
        flash_set('success', $message);
    }

    redirect_to('media.php');
}

$mediaItems = Media::all();
$success = flash_get('success');
$pageTitle = 'คลังสื่อ';
require app_path('views/layout/header.php');
?>
<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2>คลังสื่อ</h2>
            <p>จัดการไฟล์รูปภาพและวิดีโอที่ Android Player จะดาวน์โหลดไปใช้งาน</p>
        </div>
        <a class="button-primary" href="media-upload.php">อัปโหลดสื่อ</a>
    </div>

    <?php if ($success !== null): ?>
        <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ตัวอย่าง</th>
                    <th>ชื่อไฟล์</th>
                    <th>ประเภท</th>
                    <th>ขนาด</th>
                    <th>มิติภาพ</th>
                    <th>URL</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mediaItems as $item): ?>
                    <tr>
                        <td data-label="ตัวอย่าง">
                            <?php if ($item['media_type'] === 'image'): ?>
                                <img class="media-preview" src="<?= htmlspecialchars($item['public_url'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                            <?php else: ?>
                                <span class="media-video-preview">MP4</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="ชื่อไฟล์"><?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="ประเภท"><?= htmlspecialchars(media_type_label($item['media_type']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="ขนาด"><?= htmlspecialchars(format_bytes((int) $item['file_size']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="มิติภาพ"><?= $item['width'] && $item['height'] ? (int) $item['width'] . ' x ' . (int) $item['height'] : '-' ?></td>
                        <td data-label="URL"><a href="<?= htmlspecialchars($item['public_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">เปิดดู</a></td>
                        <td data-label="สถานะ"><span class="status-badge <?= (int) $item['is_active'] === 1 ? 'status-online' : 'status-inactive' ?>"><?= (int) $item['is_active'] === 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน' ?></span></td>
                        <td class="table-actions" data-label="จัดการ">
                            <?php if ((int) $item['is_active'] === 1): ?>
                                <form method="post" action="media.php" onsubmit="return confirm('ปิดใช้งาน media นี้หรือไม่?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit">ปิดใช้งาน</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($mediaItems) === 0): ?>
                    <tr>
                        <td colspan="8" class="empty-cell">ยังไม่มีสื่อที่อัปโหลด</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require app_path('views/layout/footer.php');

function format_bytes(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

function media_type_label(string $mediaType): string
{
    return $mediaType === 'image' ? 'รูปภาพ' : 'วิดีโอ';
}
