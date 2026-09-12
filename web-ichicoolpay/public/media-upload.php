<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/flash.php';
require_once __DIR__ . '/../app/models/Media.php';
require_once __DIR__ . '/../app/services/MediaUploadService.php';

require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    try {
        $metadata = MediaUploadService::validateAndStore($_FILES['media_file'] ?? []);
        Media::create($metadata);
        flash_set('success', 'อัปโหลดสื่อสำเร็จ');
        redirect_to('media.php');
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}

$pageTitle = 'อัปโหลดสื่อ';
require app_path('views/layout/header.php');
?>
<section class="content-panel form-panel">
    <div class="panel-heading">
        <div>
            <h2>อัปโหลดสื่อ</h2>
            <p>รองรับ JPG, PNG และ MP4 สำหรับนำไปจัดเพลย์ลิสต์</p>
        </div>
        <a class="button-secondary" href="media.php">กลับ</a>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="media-upload.php" enctype="multipart/form-data" class="admin-form">
        <?= csrf_field() ?>
        <label>
            ไฟล์สื่อ
            <input type="file" name="media_file" accept=".jpg,.jpeg,.png,.mp4,image/jpeg,image/png,video/mp4" required>
        </label>

        <p class="form-help">ไฟล์จะถูกเปลี่ยนชื่ออัตโนมัติและเก็บไว้ในโฟลเดอร์อัปโหลดสาธารณะ เพื่อให้ Android ดาวน์โหลดได้</p>

        <div class="form-actions">
            <button type="submit" class="button-primary">อัปโหลด</button>
            <a class="button-secondary" href="media.php">ยกเลิก</a>
        </div>
    </form>
</section>
<?php
require app_path('views/layout/footer.php');
