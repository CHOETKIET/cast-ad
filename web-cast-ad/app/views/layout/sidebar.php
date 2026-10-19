<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$navItems = [
    [
        'href' => 'dashboard.php',
        'label' => t('dashboard'),
        'section' => t('overview'),
        'icon' => 'dashboard',
        'active' => $currentPage === 'dashboard.php',
    ],
    [
        'href' => 'devices.php',
        'label' => t('devices'),
        'section' => t('management'),
        'icon' => 'screen',
        'active' => in_array($currentPage, ['devices.php', 'device-form.php', 'assign-playlist.php'], true),
    ],
    [
        'href' => 'media.php',
        'label' => t('media_library'),
        'section' => t('content'),
        'icon' => 'image',
        'active' => in_array($currentPage, ['media.php', 'media-upload.php'], true),
    ],
    [
        'href' => 'playlists.php',
        'label' => t('playlists'),
        'section' => t('content'),
        'icon' => 'playlist',
        'active' => in_array($currentPage, ['playlists.php', 'playlist-edit.php'], true),
    ],
];

function sidebar_icon(string $name): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="8" height="8" rx="1.5"></rect><rect x="13" y="3" width="8" height="5" rx="1.5"></rect><rect x="13" y="10" width="8" height="11" rx="1.5"></rect><rect x="3" y="13" width="8" height="8" rx="1.5"></rect>',
        'screen' => '<rect x="3" y="4" width="18" height="12" rx="2"></rect><path d="M8 20h8"></path><path d="M12 16v4"></path>',
        'image' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m7 15 3-3 2 2 3-4 3 5"></path><circle cx="8.5" cy="9.5" r="1.5"></circle>',
        'playlist' => '<path d="M8 6h13"></path><path d="M8 12h13"></path><path d="M8 18h13"></path><path d="M3 6h.01"></path><path d="M3 12h.01"></path><path d="M3 18h.01"></path>',
    ];

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? $paths['dashboard']) . '</svg>';
}
?>
<aside class="admin-sidebar">
    <div class="brand-block">
        <span class="brand-eyebrow"><?= htmlspecialchars(t('admin_panel'), ENT_QUOTES, 'UTF-8') ?></span>
        <div class="brand">
            <img class="brand-logo" src="assets/images/logo-castad.png" alt="" aria-hidden="true">
            <span class="brand-title"><?= htmlspecialchars(app_config('app_name'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <nav class="admin-nav">
        <?php $lastSection = null; ?>
        <?php foreach ($navItems as $item): ?>
            <?php if ($item['section'] !== $lastSection): ?>
                <span class="nav-section"><?= htmlspecialchars($item['section'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php $lastSection = $item['section']; ?>
            <?php endif; ?>
            <a class="<?= $item['active'] ? 'is-active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="nav-icon"><?= sidebar_icon($item['icon']) ?></span>
                <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
