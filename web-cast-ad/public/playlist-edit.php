<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/flash.php';
require_once __DIR__ . '/../app/core/validation.php';
require_once __DIR__ . '/../app/models/Media.php';
require_once __DIR__ . '/../app/models/Playlist.php';
require_once __DIR__ . '/../app/models/PlaylistItem.php';
require_once __DIR__ . '/../app/services/PlaylistVersionService.php';

require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$playlist = $id > 0 ? Playlist::find($id) : null;
if ($id > 0 && $playlist === null) {
    http_response_code(404);
    exit(current_language() === 'en' ? 'Playlist not found' : 'ไม่พบเพลย์ลิสต์');
}

$errors = [];
$form = [
    'name' => $playlist['name'] ?? '',
    'description' => $playlist['description'] ?? '',
    'is_active' => isset($playlist['is_active']) ? (int) $playlist['is_active'] : 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = $_POST['action'] ?? 'save_playlist';

    try {
        if ($action === 'save_playlist') {
            $form['name'] = required_string($_POST, 'name');
            $form['description'] = nullable_string($_POST, 'description');
            $form['is_active'] = isset($_POST['is_active']) ? 1 : 0;

            if ($id > 0) {
                if ((int) $playlist['is_active'] !== (int) $form['is_active']) {
                    Playlist::updateInfoAndVersion($id, $form);
                } else {
                    Playlist::updateInfo($id, $form);
                }
                flash_set('success', current_language() === 'en' ? 'Playlist saved' : 'บันทึกเพลย์ลิสต์แล้ว');
            } else {
                $id = Playlist::create($form);
                flash_set('success', current_language() === 'en' ? 'Playlist created' : 'สร้างเพลย์ลิสต์แล้ว');
            }
            redirect_to('playlist-edit.php?id=' . $id);
        }

        if ($id <= 0) {
            throw new InvalidArgumentException(current_language() === 'en' ? 'Please save the playlist before editing items' : 'กรุณาบันทึกเพลย์ลิสต์ก่อนแก้ไขรายการ');
        }

        if ($action === 'add_item') {
            $mediaId = (int) ($_POST['media_id'] ?? 0);
            $duration = normalize_duration($_POST['duration_seconds'] ?? null);
            if ($mediaId <= 0) {
                throw new InvalidArgumentException(current_language() === 'en' ? 'Please select media' : 'กรุณาเลือกสื่อ');
            }
            PlaylistItem::add($id, $mediaId, $duration);
            PlaylistVersionService::markContentChanged($id);
            flash_set('success', current_language() === 'en' ? 'Media added to playlist' : 'เพิ่มสื่อเข้าเพลย์ลิสต์แล้ว');
            redirect_to('playlist-edit.php?id=' . $id);
        }

        if ($action === 'update_items') {
            foreach ($_POST['items'] ?? [] as $itemId => $itemData) {
                PlaylistItem::updateItem(
                    (int) $itemId,
                    $id,
                    max(1, (int) ($itemData['sort_order'] ?? 1)),
                    normalize_duration($itemData['duration_seconds'] ?? null)
                );
            }
            PlaylistVersionService::markContentChanged($id);
            flash_set('success', current_language() === 'en' ? 'Order and duration updated' : 'อัปเดตลำดับและระยะเวลาแล้ว');
            redirect_to('playlist-edit.php?id=' . $id);
        }

        if ($action === 'delete_item') {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            if ($itemId > 0) {
                PlaylistItem::deleteItem($itemId, $id);
                PlaylistVersionService::markContentChanged($id);
            flash_set('success', current_language() === 'en' ? 'Item removed from playlist' : 'ลบรายการออกจากเพลย์ลิสต์แล้ว');
            }
            redirect_to('playlist-edit.php?id=' . $id);
        }
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}

$playlist = $id > 0 ? Playlist::find($id) : null;
$items = $id > 0 ? PlaylistItem::forPlaylist($id) : [];
$activeMedia = Media::active();
$success = flash_get('success');
$pageTitle = $id > 0 ? t('edit_playlist') : t('create_playlist');
require app_path('views/layout/header.php');
?>
<section class="content-panel form-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($playlist ? t('version') . ' ' . (int) $playlist['version'] : (current_language() === 'en' ? 'Save playlist details before adding media' : 'บันทึกข้อมูลเพลย์ลิสต์ก่อนเพิ่มสื่อ'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="form-actions">
            <?php if ($id > 0): ?>
                <a class="button-secondary" href="playlist-preview.php?id=<?= (int) $id ?>"><?= htmlspecialchars(t('preview_playlist'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
            <a class="button-secondary" href="playlists.php"><?= htmlspecialchars(t('back'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>

    <?php if ($success !== null): ?>
        <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <form method="post" action="playlist-edit.php<?= $id > 0 ? '?id=' . (int) $id : '' ?>" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_playlist">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <label>
            <?= htmlspecialchars(t('playlist_name'), ENT_QUOTES, 'UTF-8') ?>
            <input type="text" name="name" value="<?= htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>
            <?= htmlspecialchars(t('description'), ENT_QUOTES, 'UTF-8') ?>
            <textarea name="description" rows="3"><?= htmlspecialchars((string) $form['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
        <label class="checkbox-label">
            <input type="checkbox" name="is_active" value="1" <?= (int) $form['is_active'] === 1 ? 'checked' : '' ?>>
            <?= htmlspecialchars(t('active'), ENT_QUOTES, 'UTF-8') ?>
        </label>
        <div class="form-actions">
            <button type="submit" class="button-primary"><?= htmlspecialchars(t('save_playlist'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </form>
</section>

<?php if ($id > 0): ?>
<section class="content-panel playlist-items-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars(t('playlist_items'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('playlist_items_intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <form method="post" action="playlist-edit.php?id=<?= (int) $id ?>" class="inline-add-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_item">
        <select name="media_id" required>
            <option value=""><?= htmlspecialchars(t('select_media'), ENT_QUOTES, 'UTF-8') ?></option>
            <?php foreach ($activeMedia as $media): ?>
                <option value="<?= (int) $media['id'] ?>"><?= htmlspecialchars(media_type_label($media['media_type']) . ' - ' . $media['original_name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="duration_seconds" min="1" placeholder="<?= htmlspecialchars(t('image_duration_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="button-primary"><?= htmlspecialchars(t('add_item'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>

    <form method="post" action="playlist-edit.php?id=<?= (int) $id ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_items">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(t('sort_order'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('media'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('type'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('duration'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th>URL</th>
                        <th><?= htmlspecialchars(t('actions'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td data-label="<?= htmlspecialchars(t('sort_order'), ENT_QUOTES, 'UTF-8') ?>"><input class="small-input" type="number" name="items[<?= (int) $item['id'] ?>][sort_order]" min="1" value="<?= (int) $item['sort_order'] ?>"></td>
                            <td data-label="<?= htmlspecialchars(t('media'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="<?= htmlspecialchars(t('type'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(media_type_label($item['media_type']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="<?= htmlspecialchars(t('duration'), ENT_QUOTES, 'UTF-8') ?>"><input class="small-input" type="number" name="items[<?= (int) $item['id'] ?>][duration_seconds]" min="1" value="<?= htmlspecialchars((string) ($item['duration_seconds'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="-"></td>
                            <td data-label="URL"><a href="<?= htmlspecialchars($item['public_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars(t('open'), ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td class="table-actions" data-label="<?= htmlspecialchars(t('actions'), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit"><?= htmlspecialchars(t('update'), ENT_QUOTES, 'UTF-8') ?></button>
                                <button type="submit" form="delete-item-<?= (int) $item['id'] ?>"><?= htmlspecialchars(t('delete'), ENT_QUOTES, 'UTF-8') ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($items) === 0): ?>
                        <tr>
                            <td colspan="6" class="empty-cell"><?= htmlspecialchars(t('no_playlist_items'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($items) > 0): ?>
            <div class="form-actions table-save-actions">
                <button type="submit" class="button-primary"><?= htmlspecialchars(t('save_item_changes'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        <?php endif; ?>
    </form>

    <?php foreach ($items as $item): ?>
        <form id="delete-item-<?= (int) $item['id'] ?>" method="post" action="playlist-edit.php?id=<?= (int) $id ?>" onsubmit="return confirm('ลบ item นี้ออกจาก playlist หรือไม่?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_item">
            <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
        </form>
    <?php endforeach; ?>
</section>
<?php endif; ?>
<?php
require app_path('views/layout/footer.php');

function normalize_duration($value): ?int
{
    if ($value === null || trim((string) $value) === '') {
        return null;
    }

    $duration = (int) $value;
    if ($duration < 1) {
        throw new InvalidArgumentException(current_language() === 'en' ? 'Duration must be at least 1 second' : 'ระยะเวลาต้องไม่น้อยกว่า 1 วินาที');
    }

    return $duration;
}

function media_type_label(string $mediaType): string
{
    return $mediaType === 'image' ? t('image') : t('video');
}
