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
        flash_set('success', current_language() === 'en' ? 'Media uploaded successfully' : 'อัปโหลดสื่อสำเร็จ');
        redirect_to('media.php');
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}

$pageTitle = t('upload_media');
require app_path('views/layout/header.php');
?>
<section class="content-panel form-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars(t('upload_media'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('upload_intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a class="button-secondary" href="media.php"><?= htmlspecialchars(t('back'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="media-upload.php" enctype="multipart/form-data" class="admin-form">
        <?= csrf_field() ?>
        <label>
            <?= htmlspecialchars(t('media_file'), ENT_QUOTES, 'UTF-8') ?>
            <input type="file" name="media_file" accept=".jpg,.jpeg,.png,.mp4,image/jpeg,image/png,video/mp4" required>
        </label>

        <p class="form-help"><?= htmlspecialchars(t('upload_help'), ENT_QUOTES, 'UTF-8') ?></p>

        <div class="form-actions">
            <button type="submit" class="button-primary"><?= htmlspecialchars(t('upload'), ENT_QUOTES, 'UTF-8') ?></button>
            <a class="button-secondary" href="media.php"><?= htmlspecialchars(t('cancel'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </form>
</section>
<?php
require app_path('views/layout/footer.php');
