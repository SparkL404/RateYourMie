<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/tier.php';

$currentUser = current_user();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$isInAdmin = str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/admin/');
$pageTitle = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' · ' . SITE_NAME : SITE_NAME . ' — ' . SITE_TAGLINE;
$flashes = get_flashes();

$sidebarNoodleCount = 0;
$sidebarTierlistCount = 0;
$sidebarCurators = [];
try {
    $pdoHeader = db();
    $sidebarNoodleCount = (int) $pdoHeader->query('SELECT COUNT(*) FROM noodles')->fetchColumn();
    if ($currentUser !== null) {
        $tlStmt = $pdoHeader->prepare('SELECT COUNT(*) FROM tierlists WHERE user_id = :uid');
        $tlStmt->execute([':uid' => (int) $currentUser['id']]);
        $sidebarTierlistCount = (int) $tlStmt->fetchColumn();
    }
    $curStmt = $pdoHeader->query("SELECT username, role FROM users WHERE role = 'user' ORDER BY id ASC LIMIT 3");
    $sidebarCurators = $curStmt ? $curStmt->fetchAll() : [];
} catch (Throwable $ignored) {
    $sidebarNoodleCount = 0;
    $sidebarTierlistCount = 0;
    $sidebarCurators = [];
}

$curatorAvatars = ['#D92D20', '#0F766E', '#D97706', '#2563EB'];
$curatorRoles = ['Kurator Rasa', 'Spesialis Kuah', 'Penilai Komunitas'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?></title>
    <script>
    (function () {
        try {
            if (localStorage.getItem('rym_theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&family=JetBrains+Mono:wght@500;600;700&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body data-base-url="<?= e(base_url()) ?>" class="<?= $isInAdmin ? 'is-admin-body' : 'is-public-body' ?>">
    <a href="#main-content" class="skip-link">Langsung ke konten utama</a>

    <div class="app-shell">
        <aside class="app-sidebar" aria-label="<?= $isInAdmin ? 'Navigasi Panel Pengelola' : 'Navigasi Utama RateYourMie' ?>">
            <div class="sidebar-top-brand">
                <a href="<?= e(base_url($isInAdmin ? 'admin/index.php' : 'index.php')) ?>" class="brand-logo">
                    <span class="brand-icon-badge"><?= svg_icon($isInAdmin ? 'shield' : 'bowl', 18) ?></span>
                    <span class="brand-copy">
                        <strong class="brand-title"><?= e(SITE_NAME) ?></strong>
                        <span class="brand-subtitle"><?= $isInAdmin ? 'Panel Pengelola' : 'Rating Mie Instan Favorit U!' ?></span>
                    </span>
                </a>
                <?php if ($isInAdmin): ?>
                    <span class="role-pill">Admin</span>
                <?php endif; ?>
            </div>

            <?php if ($isInAdmin): ?>
                <div class="sidebar-primary-cta">
                    <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="btn btn-obsidian btn-block">
                        <?= svg_icon('plus', 15) ?> Tambah Mie Baru
                    </a>
                </div>
            <?php endif; ?>

            <nav class="sidebar-nav-group" aria-label="Menu Utama">
                <?php if ($isInAdmin): ?>
                    <a href="<?= e(base_url('admin/index.php')) ?>" class="sidebar-link <?= $currentScript === 'index.php' ? 'is-active' : '' ?>">
                        <span class="sidebar-link-main"><?= svg_icon('grid', 16) ?> <span>Ringkasan</span></span>
                    </a>
                    <a href="<?= e(base_url('admin/noodles.php')) ?>" class="sidebar-link <?= in_array($currentScript, ['noodles.php', 'noodle_form.php'], true) ? 'is-active' : '' ?>">
                        <span class="sidebar-link-main"><?= svg_icon('bowl', 16) ?> <span>Katalog Mie</span></span>
                        <?php if ($sidebarNoodleCount > 0): ?>
                            <span class="sidebar-count tabular"><?= $sidebarNoodleCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= e(base_url('admin/reviews.php')) ?>" class="sidebar-link <?= $currentScript === 'reviews.php' ? 'is-active' : '' ?>">
                        <span class="sidebar-link-main"><?= svg_icon('message', 16) ?> <span>Moderasi Ulasan</span></span>
                    </a>
                    <a href="<?= e(base_url('index.php')) ?>" class="sidebar-link">
                        <span class="sidebar-link-main"><?= svg_icon('arrow-left', 16) ?> <span>Katalog Publik</span></span>
                    </a>
                <?php else: ?>
                    <a href="<?= e(base_url('index.php')) ?>" class="sidebar-link <?= in_array($currentScript, ['index.php', 'noodle.php'], true) ? 'is-active' : '' ?>">
                        <span class="sidebar-link-main"><?= svg_icon('grid', 16) ?> <span>Katalog Mie</span></span>
                        <?php if ($sidebarNoodleCount > 0): ?>
                            <span class="sidebar-count tabular"><?= $sidebarNoodleCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= e(base_url('pages/community.php')) ?>" class="sidebar-link <?= $currentScript === 'community.php' ? 'is-active' : '' ?>">
                        <span class="sidebar-link-main"><?= svg_icon('layers', 16) ?> <span>Tierlist Komunitas</span></span>
                    </a>
                    <a href="<?= e(base_url('pages/top10.php')) ?>" class="sidebar-link <?= $currentScript === 'top10.php' ? 'is-active' : '' ?>">
                        <span class="sidebar-link-main"><?= svg_icon('trophy', 16) ?> <span>Top 10</span></span>
                    </a>
                    <?php if ($currentUser !== null): ?>
                        <a href="<?= e(base_url('pages/tierlists.php')) ?>" class="sidebar-link <?= in_array($currentScript, ['tierlists.php', 'tierlist.php'], true) ? 'is-active' : '' ?>">
                            <span class="sidebar-link-main"><?= svg_icon('star', 16) ?> <span>Tierlist Saya</span></span>
                            <span class="sidebar-count tabular"><?= $sidebarTierlistCount ?></span>
                        </a>
                        <a href="<?= e(base_url('pages/profile.php')) ?>" class="sidebar-link <?= $currentScript === 'profile.php' ? 'is-active' : '' ?>">
                            <span class="sidebar-link-main"><?= svg_icon('user', 16) ?> <span>Profil Saya</span></span>
                        </a>
                    <?php endif; ?>
                    <?php if ($currentUser !== null && ($currentUser['role'] ?? '') === 'admin'): ?>
                        <a href="<?= e(base_url('admin/index.php')) ?>" class="sidebar-link sidebar-link-admin">
                            <span class="sidebar-link-main"><?= svg_icon('shield', 16) ?> <span>Dashboard Admin</span></span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="sidebar-subsection">
                <div class="sidebar-subhead">
                    <span><?= $isInAdmin ? 'Navigasi Cepat' : 'Kategori Cepat' ?></span>
                </div>
                <div class="sidebar-nav-group">
                    <a href="<?= e(base_url('index.php?q=Goreng')) ?>" class="sidebar-link sidebar-link-sub">
                        <span class="sidebar-link-main"><?= svg_icon('flame', 15) ?> <span>Mi Goreng</span></span>
                    </a>
                    <a href="<?= e(base_url('index.php?q=Soto')) ?>" class="sidebar-link sidebar-link-sub">
                        <span class="sidebar-link-main"><?= svg_icon('bowl', 15) ?> <span>Mi Kuah &amp; Soto</span></span>
                    </a>
                    <a href="<?= e(base_url('index.php?q=Pedas')) ?>" class="sidebar-link sidebar-link-sub">
                        <span class="sidebar-link-main"><?= svg_icon('tag', 15) ?> <span>Varian Pedas</span></span>
                    </a>
                    <a href="<?= e(base_url('pages/community.php')) ?>" class="sidebar-link sidebar-link-sub">
                        <span class="sidebar-link-main"><?= svg_icon('award', 15) ?> <span>Papan Tier S–F</span></span>
                    </a>
                </div>
            </div>

            <?php if (!empty($sidebarCurators)): ?>
                <div class="sidebar-subsection">
                    <div class="sidebar-subhead">
                        <span>Kurator Komunitas</span>
                        <?php if ($currentUser === null): ?>
                            <a href="<?= e(base_url('auth/register.php')) ?>" class="sidebar-plus-link" title="Gabung Komunitas"><?= svg_icon('plus', 13) ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="sidebar-curators">
                        <?php foreach ($sidebarCurators as $idx => $cur): ?>
                            <?php
                            $cName = (string) ($cur['username'] ?? 'kurator');
                            $cInit = mb_strtoupper(mb_substr($cName, 0, 2, 'UTF-8'), 'UTF-8');
                            $cBg = $curatorAvatars[$idx % count($curatorAvatars)];
                            $cRole = $curatorRoles[$idx % count($curatorRoles)];
                            ?>
                            <div class="sidebar-curator-row">
                                <span class="curator-avatar" style="background-color:<?= e($cBg) ?>;" aria-hidden="true">
                                    <?= e($cInit) ?>
                                    <span class="curator-dot"></span>
                                </span>
                                <div class="curator-info">
                                    <strong>@<?= e($cName) ?></strong>
                                    <span><?= e($cRole) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="sidebar-footer">
                <?php if ($currentUser !== null): ?>
                    <div class="sidebar-user-card">
                        <a href="<?= e(base_url($isInAdmin ? 'admin/index.php' : 'pages/profile.php')) ?>" class="sidebar-user-ident">
                            <span class="curator-avatar curator-avatar-dark" aria-hidden="true">
                                <?= e(mb_strtoupper(mb_substr((string) ($currentUser['username'] ?? 'U'), 0, 2, 'UTF-8'), 'UTF-8')) ?>
                            </span>
                            <div class="curator-info">
                                <strong>@<?= e($currentUser['username'] ?? 'user') ?></strong>
                                <span><?= ($currentUser['role'] ?? '') === 'admin' ? 'Administrator' : 'Anggota' ?></span>
                            </div>
                        </a>
                        <div class="sidebar-footer-actions">
                            <button type="button" class="theme-toggle-btn js-theme-toggle" aria-label="Ganti tema tampilan" title="Ganti tema Terang / Gelap">
                                <span class="theme-icon-light"><?= svg_icon('moon', 15) ?></span>
                                <span class="theme-icon-dark"><?= svg_icon('sun', 15) ?></span>
                            </button>
                            <form action="<?= e(base_url('auth/logout.php')) ?>" method="post" class="inline-logout-form">
                                <?= csrf_field() ?>
                                <button type="submit" class="icon-action-btn" aria-label="Logout" title="Keluar dari akun">
                                    <?= svg_icon('logout', 15) ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="sidebar-auth-bar">
                        <button type="button" class="theme-toggle-btn js-theme-toggle" aria-label="Ganti tema tampilan" title="Ganti tema Terang / Gelap">
                            <span class="theme-icon-light"><?= svg_icon('moon', 15) ?></span>
                            <span class="theme-icon-dark"><?= svg_icon('sun', 15) ?></span>
                        </button>
                        <a href="<?= e(base_url('auth/login.php')) ?>" class="btn btn-secondary btn-sm">Masuk</a>
                        <a href="<?= e(base_url('auth/register.php')) ?>" class="btn btn-obsidian btn-sm">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <div class="app-main-area">
            <header class="mobile-shell-header">
                <div class="mobile-shell-top">
                    <a href="<?= e(base_url($isInAdmin ? 'admin/index.php' : 'index.php')) ?>" class="brand-logo">
                        <span class="brand-icon-badge"><?= svg_icon($isInAdmin ? 'shield' : 'bowl', 16) ?></span>
                        <strong class="brand-title"><?= e(SITE_NAME) ?></strong>
                    </a>
                    <div class="mobile-shell-actions">
                        <button type="button" class="theme-toggle-btn js-theme-toggle" aria-label="Ganti tema tampilan">
                            <span class="theme-icon-light"><?= svg_icon('moon', 15) ?></span>
                            <span class="theme-icon-dark"><?= svg_icon('sun', 15) ?></span>
                        </button>
                        <?php if ($currentUser !== null): ?>
                            <a href="<?= e(base_url($isInAdmin ? 'admin/noodle_form.php' : 'pages/tierlists.php')) ?>" class="btn btn-obsidian btn-sm">
                                <?= $isInAdmin ? '+ Mie' : 'Tierlist' ?>
                            </a>
                        <?php else: ?>
                            <a href="<?= e(base_url('auth/login.php')) ?>" class="btn btn-obsidian btn-sm">Masuk</a>
                        <?php endif; ?>
                    </div>
                </div>
                <nav class="mobile-shell-nav" aria-label="Navigasi Mobile">
                    <?php if ($isInAdmin): ?>
                        <a href="<?= e(base_url('admin/index.php')) ?>" class="mobile-pill-link <?= $currentScript === 'index.php' ? 'is-active' : '' ?>">Ringkasan</a>
                        <a href="<?= e(base_url('admin/noodles.php')) ?>" class="mobile-pill-link <?= in_array($currentScript, ['noodles.php', 'noodle_form.php'], true) ? 'is-active' : '' ?>">Katalog Mie</a>
                        <a href="<?= e(base_url('admin/reviews.php')) ?>" class="mobile-pill-link <?= $currentScript === 'reviews.php' ? 'is-active' : '' ?>">Ulasan</a>
                        <a href="<?= e(base_url('index.php')) ?>" class="mobile-pill-link">Publik</a>
                    <?php else: ?>
                        <a href="<?= e(base_url('index.php')) ?>" class="mobile-pill-link <?= in_array($currentScript, ['index.php', 'noodle.php'], true) ? 'is-active' : '' ?>">Katalog</a>
                        <a href="<?= e(base_url('pages/community.php')) ?>" class="mobile-pill-link <?= $currentScript === 'community.php' ? 'is-active' : '' ?>">Tierlist</a>
                        <a href="<?= e(base_url('pages/top10.php')) ?>" class="mobile-pill-link <?= $currentScript === 'top10.php' ? 'is-active' : '' ?>">Top 10</a>
                        <?php if ($currentUser !== null): ?>
                            <a href="<?= e(base_url('pages/tierlists.php')) ?>" class="mobile-pill-link <?= in_array($currentScript, ['tierlists.php', 'tierlist.php'], true) ? 'is-active' : '' ?>">Milik Saya</a>
                            <a href="<?= e(base_url('pages/profile.php')) ?>" class="mobile-pill-link <?= $currentScript === 'profile.php' ? 'is-active' : '' ?>">Profil</a>
                            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                                <a href="<?= e(base_url('admin/index.php')) ?>" class="mobile-pill-link">Admin</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </nav>
            </header>

            <?php if (!empty($flashes)): ?>
                <div class="container flash-stack" role="status" aria-live="polite">
                    <?php foreach ($flashes as $f): ?>
                        <div class="flash-item flash-<?= e($f['type']) ?>">
                            <span class="flash-icon"><?= svg_icon($f['type'] === 'success' ? 'check' : 'alert', 17) ?></span>
                            <span><?= e($f['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
