<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tier.php';

$user = require_login();
$pdo = db();

// Ambil daftar tierlist milik pengguna
$stmtTierlists = $pdo->prepare('
    SELECT t.*, COUNT(ti.id) AS item_count
    FROM tierlists t
    LEFT JOIN tierlist_items ti ON ti.tierlist_id = t.id
    WHERE t.user_id = :uid
    GROUP BY t.id
    ORDER BY t.updated_at DESC, t.id DESC
');
$stmtTierlists->execute([':uid' => (int) $user['id']]);
$myTierlists = $stmtTierlists->fetchAll();

// Ambil riwayat rating milik pengguna
$stmtRatings = $pdo->prepare('
    SELECT
        r.*,
        ((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS final_score,
        n.name AS noodle_name,
        n.brand AS noodle_brand,
        n.flavor AS noodle_flavor
    FROM ratings r
    INNER JOIN noodles n ON n.id = r.noodle_id
    WHERE r.user_id = :uid
    ORDER BY r.created_at DESC, r.id DESC
');
$stmtRatings->execute([':uid' => (int) $user['id']]);
$myRatings = $stmtRatings->fetchAll();

$pageTitle = 'Profil ' . $user['username'];
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container section-block">
    <section class="page-head">
        <div class="page-head-copy">
            <h1><?= e($user['username']) ?></h1>
            <p><?= e($user['email']) ?> · Peran <?= e(strtoupper((string) $user['role'])) ?></p>
        </div>
        <div class="page-head-meta tabular">
            <span><strong><?= count($myTierlists) ?> / <?= (int) MAX_TIERLISTS ?></strong> tierlist</span>
            <span class="meta-dot" aria-hidden="true">·</span>
            <span><strong><?= count($myRatings) ?></strong> mie dinilai</span>
        </div>
    </section>

    <section class="profile-section">
        <div class="section-head-row">
            <h2>Tierlist Pribadi</h2>
            <a href="<?= e(base_url('tierlists.php')) ?>" class="btn btn-secondary btn-sm">Kelola Tierlist</a>
        </div>
        <?php if (empty($myTierlists)): ?>
            <div class="empty-state">
                <p>Anda belum membuat tierlist pribadi.</p>
                <a href="<?= e(base_url('tierlists.php')) ?>" class="btn btn-primary">Buat Tierlist</a>
            </div>
        <?php else: ?>
            <div class="catalog-grid">
                <?php foreach ($myTierlists as $tl): ?>
                    <article class="panel-card tierlist-summary-card">
                        <div>
                            <div class="noodle-card-meta">
                                <span><?= e($tl['category'] ?: 'Umum') ?></span>
                                <span class="tabular"><?= (int) $tl['item_count'] ?> mie</span>
                            </div>
                            <h3 class="tierlist-card-title">
                                <a href="<?= e(base_url('tierlist.php?id=' . (int) $tl['id'])) ?>"><?= e($tl['title']) ?></a>
                            </h3>
                        </div>
                        <div class="tierlist-card-actions">
                            <a href="<?= e(base_url('tierlist.php?id=' . (int) $tl['id'])) ?>" class="btn btn-secondary btn-sm">Buka Papan Tierlist</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="profile-section">
        <h2>Riwayat Rating &amp; Ulasan</h2>
        <?php if (empty($myRatings)): ?>
            <div class="empty-state">
                <p>Anda belum memberi rating pada mie instan mana pun.</p>
                <a href="<?= e(base_url('index.php')) ?>" class="btn btn-primary">Telusuri Katalog Mie</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mie Instan</th>
                            <th>R / T / P / H</th>
                            <th>Skor Akhir</th>
                            <th>Ulasan</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myRatings as $r): ?>
                            <?php $tier = score_to_tier((float) $r['final_score']); ?>
                            <tr>
                                <td class="cell-sans">
                                    <a href="<?= e(base_url('noodle.php?id=' . (int) $r['noodle_id'])) ?>" class="table-item-title">
                                        <?= e($r['noodle_name']) ?>
                                    </a>
                                    <div class="table-item-sub"><?= e($r['noodle_brand']) ?> · <?= e($r['noodle_flavor']) ?></div>
                                </td>
                                <td><?= (int) $r['taste'] ?> / <?= (int) $r['texture'] ?> / <?= (int) $r['portion'] ?> / <?= (int) $r['value'] ?></td>
                                <td>
                                    <span class="tier-badge tier-badge-xs <?= e(tier_meta($tier)['class']) ?>"><?= e($tier) ?></span>
                                    <strong class="table-score-accent"><?= e(format_score($r['final_score'])) ?></strong>
                                </td>
                                <td class="cell-sans cell-review"><?= $r['review'] !== null && $r['review'] !== '' ? e($r['review']) : '<em class="muted-italic">Tanpa ulasan teks</em>' ?></td>
                                <td><?= e(substr((string) $r['created_at'], 0, 10)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
