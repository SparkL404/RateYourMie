<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/noodle_repo.php';

$admin = require_admin();
$pdo = db();

$totalNoodles = (int) $pdo->query('SELECT COUNT(*) FROM noodles')->fetchColumn();
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalRatings = (int) $pdo->query('SELECT COUNT(*) FROM ratings')->fetchColumn();
$totalTierlists = (int) $pdo->query('SELECT COUNT(*) FROM tierlists')->fetchColumn();

$communityData = get_community_tierlist_data();
$minRaters = (int) $communityData['min_raters'];
$qualifiedCount = (int) $communityData['qualified_count'];
$tierBuckets = $communityData['tiers'];

$qualifiedPct = $totalNoodles > 0 ? (int) round(($qualifiedCount / $totalNoodles) * 100) : 0;
$totalTicks = 36;
$activeTicks = (int) round(($qualifiedPct / 100) * $totalTicks);

$tierCounts = [
    'S'   => count($tierBuckets['S'] ?? []),
    'A'   => count($tierBuckets['A'] ?? []),
    'B'   => count($tierBuckets['B'] ?? []),
    'C'   => count($tierBuckets['C'] ?? []),
    'D–F' => count($tierBuckets['D'] ?? []) + count($tierBuckets['F'] ?? []),
];
$maxTierBucket = max(1, max($tierCounts), (int) ceil($totalNoodles / 3));

$recentNoodles = $pdo->query('
    SELECT
        n.*,
        COUNT(r.id) AS rater_count,
        AVG((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS avg_score
    FROM noodles n
    LEFT JOIN ratings r ON r.noodle_id = n.id
    GROUP BY n.id
    ORDER BY n.created_at DESC, n.id DESC
    LIMIT 6
')->fetchAll();

$recentReviews = $pdo->query('
    SELECT
        r.*,
        ((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS final_score,
        u.username,
        n.name AS noodle_name
    FROM ratings r
    INNER JOIN users u ON u.id = r.user_id
    INNER JOIN noodles n ON n.id = r.noodle_id
    ORDER BY r.created_at DESC, r.id DESC
    LIMIT 6
')->fetchAll();

$pageTitle = 'Dashboard Admin';
include __DIR__ . '/../includes/header.php';
?>

<main id="main-content" class="workspace-main">
    <section class="alabaster-hero-band" aria-label="Ringkasan Analitik Admin">
        <form action="<?= e(base_url('admin/noodles.php')) ?>" method="get" class="command-bar">
            <div class="command-search">
                <span class="command-search-icon"><?= svg_icon('search', 16) ?></span>
                <label for="admin-quick-search" class="skip-link">Cari mie di panel admin</label>
                <input
                    type="search"
                    id="admin-quick-search"
                    name="q"
                    class="command-search-input"
                    placeholder="Cari nama mie, brand, atau negara untuk dikelola..."
                >
            </div>

            <div class="command-controls">
                <a href="<?= e(base_url('admin/noodles.php')) ?>" class="btn btn-ghost btn-sm command-apply-btn">
                    <?= svg_icon('bowl', 14) ?> Semua Mie
                </a>
                <a href="<?= e(base_url('admin/reviews.php')) ?>" class="btn btn-ghost btn-sm command-apply-btn">
                    <?= svg_icon('message', 14) ?> Moderasi Ulasan
                </a>
                <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="btn btn-obsidian">
                    <?= svg_icon('plus', 15) ?>
                    <span>Tambah Mie Baru</span>
                </a>
            </div>
        </form>

        <div class="analytics-4col">
            <div class="analytics-chart-col">
                <h2 class="analytics-heading">Distribusi Tier Komunitas</h2>
                <div class="dual-bar-chart" aria-label="Sebaran jumlah varian pada Tier S hingga F">
                    <div class="dual-y-axis tabular" aria-hidden="true">
                        <span><?= $maxTierBucket ?></span>
                        <span><?= (int) ceil($maxTierBucket / 2) ?></span>
                        <span>0</span>
                    </div>
                    <div class="dual-bar-groups">
                        <?php foreach ($tierCounts as $tLabel => $tCount): ?>
                            <?php
                            $actualH = $tCount > 0 ? (int) round(min(100, max(10, ($tCount / $maxTierBucket) * 100))) : 6;
                            $capH = min(100, max(24, $actualH + 22));
                            ?>
                            <div class="dual-bar-col" title="Tier <?= e($tLabel) ?>: <?= $tCount ?> varian">
                                <div class="dual-bar-pair">
                                    <span class="bar-hatched" style="height:<?= $capH ?>%"></span>
                                    <span class="bar-solid" style="height:<?= $actualH ?>%"></span>
                                </div>
                                <span class="dual-bar-label tabular"><?= e($tLabel) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="analytics-gauge-col">
                <svg class="radial-gauge-svg" viewBox="0 0 210 118" role="img" aria-label="<?= $qualifiedPct ?>% dari <?= $totalNoodles ?> varian memenuhi syarat min. <?= $minRaters ?> penilai">
                    <g transform="translate(105, 100)">
                        <?php for ($i = 0; $i < $totalTicks; $i++): ?>
                            <?php
                            $deg = -180.0 + ($i * (180.0 / ($totalTicks - 1)));
                            $rad = deg2rad($deg);
                            $x1 = number_format(cos($rad) * 70, 2, '.', '');
                            $y1 = number_format(sin($rad) * 70, 2, '.', '');
                            $x2 = number_format(cos($rad) * 86, 2, '.', '');
                            $y2 = number_format(sin($rad) * 86, 2, '.', '');
                            $tickClass = $i < $activeTicks ? 'gauge-tick is-filled' : 'gauge-tick';
                            ?>
                            <line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" class="<?= $tickClass ?>" />
                        <?php endfor; ?>
                    </g>
                    <text x="105" y="76" text-anchor="middle" class="gauge-value-text tabular"><?= $qualifiedPct ?>%</text>
                    <text x="105" y="98" text-anchor="middle" class="gauge-caption-text">Memenuhi Syarat Tier</text>
                </svg>
            </div>

            <div class="fluid-kpi-col">
                <div class="fluid-kpi-num tabular"><?= $totalRatings ?></div>
                <div class="fluid-kpi-foot">
                    <span class="fluid-kpi-label">Rating &amp; ulasan<br>tercatat</span>
                    <a href="<?= e(base_url('admin/reviews.php')) ?>" class="fluid-kpi-arrow" aria-label="Buka Moderasi Ulasan">
                        <?= svg_icon('arrow-right', 16) ?>
                    </a>
                </div>
            </div>

            <div class="fluid-kpi-col">
                <div class="fluid-kpi-num tabular"><?= $totalUsers ?></div>
                <div class="fluid-kpi-foot">
                    <span class="fluid-kpi-label">Pengguna &amp; admin<br>terdaftar</span>
                    <a href="<?= e(base_url('admin/noodles.php')) ?>" class="fluid-kpi-arrow" aria-label="Kelola Katalog Mie">
                        <?= svg_icon('arrow-right', 16) ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="workspace-canvas">
        <div class="board-toolbar">
            <div class="board-title-group">
                <h1>Ringkasan Operasional</h1>
                <span class="count-sort-badge tabular">Min. <?= $minRaters ?> penilai</span>
            </div>
        </div>

        <section class="kpi-grid" aria-label="Statistik Utama">
            <div class="kpi-card">
                <span class="kpi-label">Varian Mie</span>
                <div class="kpi-row">
                    <strong class="kpi-value tabular"><?= $totalNoodles ?></strong>
                    <a href="<?= e(base_url('admin/noodles.php')) ?>" class="kpi-link">
                        <span>Kelola</span>
                        <?= svg_icon('arrow-right', 13) ?>
                    </a>
                </div>
            </div>

            <div class="kpi-card">
                <span class="kpi-label">Lolos Ambang Tier</span>
                <div class="kpi-row">
                    <strong class="kpi-value tabular"><?= $qualifiedCount ?></strong>
                    <span class="kpi-sub tabular">dari <?= $totalNoodles ?> varian</span>
                </div>
            </div>

            <div class="kpi-card">
                <span class="kpi-label">Total Rating &amp; Ulasan</span>
                <div class="kpi-row">
                    <strong class="kpi-value tabular"><?= $totalRatings ?></strong>
                    <a href="<?= e(base_url('admin/reviews.php')) ?>" class="kpi-link">
                        <span>Moderasi</span>
                        <?= svg_icon('arrow-right', 13) ?>
                    </a>
                </div>
            </div>

            <div class="kpi-card">
                <span class="kpi-label">Tierlist Tersimpan</span>
                <div class="kpi-row">
                    <strong class="kpi-value tabular"><?= $totalTierlists ?></strong>
                    <a href="<?= e(base_url('pages/community.php')) ?>" class="kpi-link">
                        <span>Tierlist</span>
                        <?= svg_icon('arrow-right', 13) ?>
                    </a>
                </div>
            </div>
        </section>

        <section class="panel-card tier-dist-panel" aria-label="Sebaran Tier Komunitas">
            <div class="tier-dist-head">
                <div>
                    <h2>Sebaran Tier Komunitas Saat Ini</h2>
                    <p class="muted-copy">Proporsi varian yang sudah memenuhi syarat minimal <?= $minRaters ?> penilai.</p>
                </div>
                <span class="tabular tier-dist-summary"><?= $qualifiedCount ?> dari <?= $totalNoodles ?> varian memenuhi syarat</span>
            </div>

            <div class="tier-dist-bar" aria-hidden="true">
                <?php foreach (all_tiers() as $tl): ?>
                    <?php
                    $c = count($tierBuckets[$tl] ?? []);
                    if ($c <= 0) {
                        continue;
                    }
                    $pct = ($c / max(1, $qualifiedCount)) * 100;
                    $tm = tier_meta($tl);
                    ?>
                    <span class="tier-dist-segment <?= e($tm['class']) ?>" style="width:<?= round($pct, 2) ?>%" title="Tier <?= e($tl) ?>: <?= $c ?> varian"></span>
                <?php endforeach; ?>
            </div>

            <div class="tier-dist-legend tabular">
                <?php foreach (all_tiers() as $tl): ?>
                    <?php
                    $c = count($tierBuckets[$tl] ?? []);
                    $tm = tier_meta($tl);
                    ?>
                    <span class="tier-legend-item">
                        <span class="tier-legend-dot <?= e($tm['class']) ?>"></span>
                        <strong>Tier <?= e($tl) ?></strong>
                        <span class="muted-inline">(<?= $c ?>)</span>
                    </span>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="admin-split-grid">
            <section class="panel-card admin-list-panel">
                <div class="admin-panel-head">
                    <h2>Katalog Mie Terbaru</h2>
                    <a href="<?= e(base_url('admin/noodles.php')) ?>" class="kpi-link">
                        <span>Semua mie</span>
                        <?= svg_icon('arrow-right', 13) ?>
                    </a>
                </div>

                <?php if (empty($recentNoodles)): ?>
                    <div class="empty-state">
                        <p>Belum ada data mie instan di katalog.</p>
                        <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="btn btn-obsidian btn-sm">Tambah Mie Baru</a>
                    </div>
                <?php else: ?>
                    <div class="admin-compact-list">
                        <?php foreach ($recentNoodles as $n): ?>
                            <?php
                            $rc = (int) $n['rater_count'];
                            $avg = $rc > 0 ? (float) $n['avg_score'] : null;
                            $tl = ($avg !== null && $rc >= $minRaters) ? score_to_tier($avg) : null;
                            ?>
                            <div class="admin-compact-row">
                                <div class="admin-row-main">
                                    <?= render_noodle_swatch($n, 'swatch-sm') ?>
                                    <div class="admin-row-copy">
                                        <a href="<?= e(base_url('pages/noodle.php?id=' . (int) $n['id'])) ?>" class="table-item-title"><?= e($n['name']) ?></a>
                                        <span class="table-item-sub tabular"><?= e($n['brand']) ?> · <?= e(format_rupiah((int) $n['price'])) ?></span>
                                    </div>
                                </div>
                                <div class="admin-row-actions">
                                    <?php if ($tl !== null): ?>
                                        <span class="tier-badge tier-badge-xs <?= e(tier_meta($tl)['class']) ?>"><?= e($tl) ?></span>
                                        <strong class="tabular text-sm"><?= e(format_score($avg, 2)) ?></strong>
                                    <?php else: ?>
                                        <span class="tier-badge tier-unranked"><?= $rc ?>/<?= $minRaters ?></span>
                                    <?php endif; ?>
                                    <a href="<?= e(base_url('admin/noodle_form.php?id=' . (int) $n['id'])) ?>" class="btn btn-secondary btn-sm">Ubah</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel-card admin-list-panel">
                <div class="admin-panel-head">
                    <h2>Ulasan Masuk Terbaru</h2>
                    <a href="<?= e(base_url('admin/reviews.php')) ?>" class="kpi-link">
                        <span>Semua ulasan</span>
                        <?= svg_icon('arrow-right', 13) ?>
                    </a>
                </div>

                <?php if (empty($recentReviews)): ?>
                    <div class="empty-state">
                        <p>Belum ada ulasan yang masuk.</p>
                    </div>
                <?php else: ?>
                    <div class="admin-compact-list">
                        <?php foreach ($recentReviews as $rv): ?>
                            <div class="admin-review-row">
                                <div class="admin-review-top">
                                    <div class="admin-review-meta">
                                        <strong>@<?= e($rv['username']) ?></strong>
                                        <span class="muted-inline">di</span>
                                        <a href="<?= e(base_url('pages/noodle.php?id=' . (int) $rv['noodle_id'])) ?>" class="table-item-title"><?= e($rv['noodle_name']) ?></a>
                                    </div>
                                    <span class="score-pill">
                                        <?= svg_icon('star', 13) ?>
                                        <strong><?= e(format_score($rv['final_score'], 2)) ?></strong>
                                    </span>
                                </div>
                                <div class="admin-review-bottom">
                                    <p class="admin-review-snippet">
                                        <?= $rv['review'] !== null && $rv['review'] !== '' ? e($rv['review']) : '<em class="muted-italic">Tanpa teks ulasan</em>' ?>
                                    </p>
                                    <form action="<?= e(base_url('admin/reviews.php')) ?>" method="post" class="js-inline-confirm-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delete_id" value="<?= (int) $rv['id'] ?>">
                                        <input type="hidden" name="return_to" value="index">
                                        <button type="button" class="btn btn-danger btn-sm js-confirm-trigger">Hapus</button>
                                        <span class="inline-confirm-group js-confirm-step" hidden>
                                            <button type="submit" class="btn btn-primary btn-sm">Yakin</button>
                                            <button type="button" class="btn btn-secondary btn-sm js-confirm-cancel">Batal</button>
                                        </span>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
