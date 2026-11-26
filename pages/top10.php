<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/noodle_repo.php';

$top10 = get_top10_noodles();
$minRaters = (int) (get_app_settings()['min_raters'] ?? MIN_RATERS);

$pageTitle = 'Peringkat Top 10 Mie Instan';
include __DIR__ . '/../includes/header.php';
?>

<main id="main-content" class="container section-block">
    <section class="page-head">
        <div class="page-head-copy">
            <h1>Peringkat Top 10 Mie Instan</h1>
            <p>10 varian dengan skor rata-rata tertinggi (syarat minimal <strong><?= $minRaters ?> penilai</strong>; seri diputuskan oleh penilai terbanyak).</p>
        </div>
        <div class="page-head-actions">
            <a href="<?= e(base_url('pages/community.php')) ?>" class="btn btn-secondary btn-sm">
                <?= svg_icon('layers', 15) ?> Tierlist Komunitas
            </a>
        </div>
    </section>

    <?php if (empty($top10)): ?>
        <div class="empty-state">
            <h2>Belum Ada Mie yang Memenuhi Syarat Top 10</h2>
            <p>Dibutuhkan minimal <?= $minRaters ?> penilai pada satu varian mie agar dapat masuk peringkat Top 10.</p>
            <a href="<?= e(base_url('index.php')) ?>" class="btn btn-primary">Mulai Beri Rating di Katalog</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Varian Mie</th>
                        <th>Negara</th>
                        <th>Harga</th>
                        <th>Rincian 4 Aspek</th>
                        <th>Tier</th>
                        <th>Skor Akhir</th>
                        <th>Penilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top10 as $idx => $noodle): ?>
                        <?php
                        $rank = $idx + 1;
                        $avgScore = (float) $noodle['avg_score'];
                        $tier = score_to_tier($avgScore);
                        $tMeta = tier_meta($tier);
                        $rankClass = $rank <= 3 ? 'rank-' . $rank : '';
                        ?>
                        <tr>
                            <td>
                                <span class="rank-medal <?= e($rankClass) ?>"><?= $rank ?></span>
                            </td>
                            <td class="cell-sans">
                                <div class="table-product-cell">
                                    <?= render_noodle_swatch($noodle, 'swatch-sm') ?>
                                    <div>
                                        <a href="<?= e(base_url('pages/noodle.php?id=' . (int) $noodle['id'])) ?>" class="table-item-title">
                                            <?= e($noodle['name']) ?>
                                        </a>
                                        <div class="table-item-sub">
                                            <?= e($noodle['brand']) ?> · <?= e($noodle['flavor']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-sans"><?= e($noodle['origin_country']) ?></td>
                            <td><?= e(format_rupiah((int) $noodle['price'])) ?></td>
                            <td>
                                <div class="table-aspects-pills tabular">
                                    <span class="aspect-chip">RAS <?= e(format_score($noodle['avg_taste'], 1)) ?></span>
                                    <span class="aspect-chip">TEK <?= e(format_score($noodle['avg_texture'], 1)) ?></span>
                                    <span class="aspect-chip">POR <?= e(format_score($noodle['avg_portion'], 1)) ?></span>
                                    <span class="aspect-chip">HRG <?= e(format_score($noodle['avg_value'], 1)) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="tier-badge tier-badge-sm <?= e($tMeta['class']) ?>">
                                    <?= e($tier) ?>
                                </span>
                            </td>
                            <td>
                                <strong class="table-score-accent">
                                    <?= svg_icon('star', 13) ?>
                                    <?= e(format_score($avgScore, 2)) ?>
                                </strong>
                            </td>
                            <td><?= (int) $noodle['rater_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
