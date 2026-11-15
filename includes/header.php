<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/tier.php';

$currentUser = current_user();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$isInAdmin = str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/admin/');
$pageTitle = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' · ' . SITE_NAME : SITE_NAME . ' — ' . SITE_TAGLINE;
$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=JetBrains+Mono:wght@500;600;700&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body data-base-url="<?= e(base_url()) ?>">
    <a href="#main-content" class="skip-link">Langsung ke konten utama</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="<?= e(base_url('index.php')) ?>" class="brand-logo">
                <span class="brand-icon"><?= svg_icon('bowl', 20) ?></span>
                <span class="brand-title"><?= e(SITE_NAME) ?></span>
            </a>

            <nav class="site-nav" aria-label="Navigasi Utama">
                <a href="<?= e(base_url('index.php')) ?>" class="nav-link <?= (!$isInAdmin && in_array($currentScript, ['index.php', 'noodle.php'], true)) ? 'is-active' : '' ?>">
                    Katalog
                </a>
                <a href="<?= e(base_url('community.php')) ?>" class="nav-link <?= (!$isInAdmin && $currentScript === 'community.php') ? 'is-active' : '' ?>">
                    Tierlist Komunitas
                </a>
                <a href="<?= e(base_url('top10.php')) ?>" class="nav-link <?= (!$isInAdmin && $currentScript === 'top10.php') ? 'is-active' : '' ?>">
                    Top 10
                </a>

                <?php if ($currentUser !== null): ?>
                    <a href="<?= e(base_url('tierlists.php')) ?>" class="nav-link <?= (!$isInAdmin && in_array($currentScript, ['tierlists.php', 'tierlist.php'], true)) ? 'is-active' : '' ?>">
                        Tierlist Saya
                    </a>
                    <a href="<?= e(base_url('profile.php')) ?>" class="nav-link <?= (!$isInAdmin && $currentScript === 'profile.php') ? 'is-active' : '' ?>">
                        <?= e($currentUser['username']) ?>
                    </a>
                    <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                        <a href="<?= e(base_url('admin/index.php')) ?>" class="nav-link nav-admin <?= $isInAdmin ? 'is-active' : '' ?>">
                            <?= svg_icon('shield', 15) ?> Admin
                        </a>
                    <?php endif; ?>
                    <form action="<?= e(base_url('logout.php')) ?>" method="post" class="inline-logout-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-ghost btn-sm">Logout</button>
                    </form>
                <?php else: ?>
                    <span class="nav-divider" aria-hidden="true"></span>
                    <a href="<?= e(base_url('login.php')) ?>" class="nav-link <?= $currentScript === 'login.php' ? 'is-active' : '' ?>">
                        Masuk
                    </a>
                    <a href="<?= e(base_url('register.php')) ?>" class="btn btn-primary btn-sm <?= $currentScript === 'register.php' ? 'is-active' : '' ?>">
                        Daftar
                    </a>
                <?php endif; ?>
            </nav>
        </div>
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
