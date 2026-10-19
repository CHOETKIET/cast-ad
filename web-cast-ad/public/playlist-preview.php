<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/models/Playlist.php';
require_once __DIR__ . '/../app/models/PlaylistItem.php';

require_admin();

$id = (int) ($_GET['id'] ?? 0);
$playlist = $id > 0 ? Playlist::find($id) : null;
if ($playlist === null) {
    http_response_code(404);
    exit(current_language() === 'en' ? 'Playlist not found' : 'ไม่พบเพลย์ลิสต์');
}

$items = PlaylistItem::forPlaylist($id);
$pageTitle = t('playlist_preview');
require app_path('views/layout/header.php');
?>
<section class="content-panel playlist-preview-panel">
    <div class="panel-heading">
        <div>
            <h2><?= htmlspecialchars($playlist['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('playlist_preview_intro') . ' · ' . t('version') . ' ' . (int) $playlist['version'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="form-actions">
            <a class="button-secondary" href="playlist-edit.php?id=<?= (int) $id ?>"><?= htmlspecialchars(t('edit'), ENT_QUOTES, 'UTF-8') ?></a>
            <a class="button-secondary" href="playlists.php"><?= htmlspecialchars(t('back'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>

    <?php if (count($items) === 0): ?>
        <div class="empty-cell"><?= htmlspecialchars(t('no_playlist_items'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
        <div class="playlist-preview-shell" data-preview-playlist>
            <div class="preview-stage">
                <?php foreach ($items as $index => $item): ?>
                    <?php $isVideo = (string) $item['media_type'] === 'video'; ?>
                    <div
                        class="preview-slide <?= $index === 0 ? 'is-active' : '' ?>"
                        data-preview-slide
                        data-duration="<?= (int) ($isVideo ? 10 : ($item['duration_seconds'] ?? 10)) ?>"
                        data-title="<?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <?php if ($isVideo): ?>
                            <video src="<?= htmlspecialchars($item['public_url'], ENT_QUOTES, 'UTF-8') ?>" muted playsinline controls></video>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($item['public_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="preview-controls">
                <div>
                    <span><?= htmlspecialchars(t('now_playing'), ENT_QUOTES, 'UTF-8') ?></span>
                    <strong data-preview-title><?= htmlspecialchars($items[0]['original_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="preview-buttons">
                    <button type="button" class="button-secondary" data-preview-prev><?= htmlspecialchars(t('previous_item'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="button" class="button-primary" data-preview-toggle><?= htmlspecialchars(t('pause_preview'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="button" class="button-secondary" data-preview-next><?= htmlspecialchars(t('next_item'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </div>

            <div class="preview-timeline">
                <?php foreach ($items as $index => $item): ?>
                    <button type="button" class="<?= $index === 0 ? 'is-active' : '' ?>" data-preview-jump="<?= (int) $index ?>">
                        <span><?= (int) $item['sort_order'] ?></span>
                        <?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
(function () {
    const root = document.querySelector('[data-preview-playlist]');
    if (!root) {
        return;
    }

    const slides = Array.from(root.querySelectorAll('[data-preview-slide]'));
    const title = root.querySelector('[data-preview-title]');
    const toggle = root.querySelector('[data-preview-toggle]');
    const jumps = Array.from(root.querySelectorAll('[data-preview-jump]'));
    let currentIndex = 0;
    let isPlaying = true;
    let timer = null;

    function stopMedia() {
        slides.forEach((slide) => {
            const video = slide.querySelector('video');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
        });
    }

    function scheduleNext() {
        clearTimeout(timer);
        if (!isPlaying || slides.length < 2) {
            return;
        }

        if (slides[currentIndex].querySelector('video')) {
            return;
        }

        const duration = Number(slides[currentIndex].dataset.duration || 10);
        timer = setTimeout(function () {
            showSlide((currentIndex + 1) % slides.length);
        }, Math.max(1, duration) * 1000);
    }

    function showSlide(index) {
        currentIndex = (index + slides.length) % slides.length;
        stopMedia();
        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('is-active', slideIndex === currentIndex);
        });
        jumps.forEach((jump, jumpIndex) => {
            jump.classList.toggle('is-active', jumpIndex === currentIndex);
        });
        if (title) {
            title.textContent = slides[currentIndex].dataset.title || '';
        }
        const video = slides[currentIndex].querySelector('video');
        if (video && isPlaying) {
            video.play().catch(function () {});
        }
        scheduleNext();
    }

    root.querySelector('[data-preview-prev]')?.addEventListener('click', function () {
        showSlide(currentIndex - 1);
    });
    root.querySelector('[data-preview-next]')?.addEventListener('click', function () {
        showSlide(currentIndex + 1);
    });
    toggle?.addEventListener('click', function () {
        isPlaying = !isPlaying;
        toggle.textContent = isPlaying ? <?= json_encode(t('pause_preview'), JSON_UNESCAPED_UNICODE) ?> : <?= json_encode(t('play_preview'), JSON_UNESCAPED_UNICODE) ?>;
        showSlide(currentIndex);
    });
    jumps.forEach((jump) => {
        jump.addEventListener('click', function () {
            showSlide(Number(jump.dataset.previewJump || 0));
        });
    });
    slides.forEach((slide, slideIndex) => {
        const video = slide.querySelector('video');
        if (video) {
            video.addEventListener('ended', function () {
                if (isPlaying && slideIndex === currentIndex) {
                    showSlide((currentIndex + 1) % slides.length);
                }
            });
        }
    });
    showSlide(0);
})();
</script>
<?php
require app_path('views/layout/footer.php');
