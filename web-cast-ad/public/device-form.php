<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/flash.php';
require_once __DIR__ . '/../app/core/validation.php';
require_once __DIR__ . '/../app/models/Device.php';

require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$device = $id > 0 ? Device::find($id) : null;
if ($id > 0 && $device === null) {
    http_response_code(404);
    exit(current_language() === 'en' ? 'Device not found' : 'ไม่พบอุปกรณ์');
}

$errors = [];
$form = [
    'device_id' => $device['device_id'] ?? '',
    'device_name' => $device['device_name'] ?? '',
    'is_active' => isset($device['is_active']) ? (int) $device['is_active'] : 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $form['device_id'] = strtoupper(required_string($_POST, 'device_id'));
    $form['device_name'] = required_string($_POST, 'device_name');
    $form['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if (!preg_match('/^[A-Z0-9_-]{3,100}$/', $form['device_id'])) {
        $errors[] = current_language() === 'en' ? 'Device ID may only use A-Z, 0-9, _ and -, with at least 3 characters' : 'รหัสอุปกรณ์ใช้ได้เฉพาะ A-Z, 0-9, _ และ - ความยาวอย่างน้อย 3 ตัวอักษร';
    }

    $existing = Device::findByDeviceId($form['device_id']);
    if ($existing !== null && (int) $existing['id'] !== $id) {
        $errors[] = current_language() === 'en' ? 'This device ID is already in use' : 'รหัสอุปกรณ์นี้ถูกใช้งานแล้ว';
    }

    if (count($errors) === 0) {
        if ($id > 0) {
            Device::update($id, $form);
            flash_set('success', current_language() === 'en' ? 'Device saved' : 'บันทึกอุปกรณ์แล้ว');
        } else {
            Device::create($form);
            flash_set('success', current_language() === 'en' ? 'Device added' : 'เพิ่มอุปกรณ์แล้ว');
        }
        redirect_to('devices.php');
    }
}

$pageTitle = $id > 0 ? t('edit_device') : t('add_device');
require app_path('views/layout/header.php');
?>
<section class="content-panel form-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('device_help'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a class="button-secondary" href="devices.php"><?= htmlspecialchars(t('back'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="device-form.php<?= $id > 0 ? '?id=' . (int) $id : '' ?>" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <label>
            <?= htmlspecialchars(t('device_id'), ENT_QUOTES, 'UTF-8') ?>
            <input type="text" name="device_id" value="<?= htmlspecialchars($form['device_id'], ENT_QUOTES, 'UTF-8') ?>" placeholder="SCREEN001" required>
        </label>

        <label>
            <?= htmlspecialchars(t('device_name'), ENT_QUOTES, 'UTF-8') ?>
            <input type="text" name="device_name" value="<?= htmlspecialchars($form['device_name'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= current_language() === 'en' ? 'Front screen' : 'จอหน้าร้าน' ?>" required>
        </label>

        <label class="checkbox-label">
            <input type="checkbox" name="is_active" value="1" <?= (int) $form['is_active'] === 1 ? 'checked' : '' ?>>
            <?= htmlspecialchars(t('active'), ENT_QUOTES, 'UTF-8') ?>
        </label>

        <div class="form-actions">
            <button type="submit" class="button-primary"><?= htmlspecialchars(t('save'), ENT_QUOTES, 'UTF-8') ?></button>
            <a class="button-secondary" href="devices.php"><?= htmlspecialchars(t('cancel'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </form>
</section>
<?php
require app_path('views/layout/footer.php');
