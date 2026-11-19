<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/noodle_repo.php';

$communityData = get_community_tierlist_data();
$minRaters = (int) $communityData['min_raters'];
$tiers = $communityData['tiers'];
$pending = $communityData['pending'];
$qualifiedCount = (int) $communityData['qualified_count'];
$totalNoodles = $qualifiedCount + count($pending);

$topTierCount = count($tiers['S'] ?? []) + count($tiers['A'] ?? []);
$topTierPct = $qualifiedCount > 0 ? (int) round(($topTierCount / $qualifiedCount) * 100) : 0;
$totalReviewsCount = (int) db()->query('SELECT COUNT(*) FROM ratings')->fetchColumn();
$top10List = get_top10_noodles();
$championNoodle = !empty($top10List) ? $top10List[0] : null;
$championTier = $championNoodle ? score_to_tier((float) $championNoodle['avg_score']) : null;

$totalTicks = 36;
$activeTicks = (int) round(($topTierPct / 100) * $totalTicks);

$tierCounts = [
    'S'   => count($tiers['S'] ?? []),
    'A'   => count($tiers['A'] ?? []),
    'B'   => count($tiers['B'] ?? []),
    'C'   => count($tiers['C'] ?? []),
    'D–F' => count($tiers['D'] ?? []) + count($tiers['F'] ?? []),
];
$maxTierBucket = max(1, max($tierCounts), 4);

$pageTitle = 'Tierlist Komunitas Otomatis';
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="workspace-main">
    <section class="alabaster-hero-band" aria-label="Ringkasan Tierlist Komunitas">
        <div class="command-bar">
            <div class="command-search">
                <span class="command-search-icon"><?= svg_icon('layers', 16) ?></span>
                <span class="command-title-inline">Tierlist Komunitas Otomatis — Dihitung langsung dari skor 4 aspek</span>
            </div>

            <div class="command-controls">
                <a href="<?= e(base_url('top10.php')) ?>" class="btn btn-ghost btn-sm command-apply-btn">
                    <?= svg_icon('trophy', 14) ?> Top 10
                </a>
                <a href="<?= e(base_url(current_user() !== null ? 'tierlists.php' : 'login.php')) ?>" class="btn btn-obsidian">
                    <?= svg_icon('plus', 15) ?>
                    <span>Susun Tierlist Pribadi</span>
                </a>
            </div>
        </div>

        <div class="analytics-4col">
            <div class="analytics-chart-col">
                <h2 class="analytics-heading">Sebaran Tier S–F</h2>
                <div class="dual-bar-chart" aria-label="Jumlah varian mie per tier">
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
                <svg class="radial-gauge-svg" viewBox="0 0 210 118" role="img" aria-label="<?= $topTierCount ?> mie berada di Tier S dan Tier A">
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
                    <text x="105" y="74" text-anchor="middle" class="gauge-value-text tabular"><?= $topTierCount ?> Mie</text>
                    <text x="105" y="96" text-anchor="middle" class="gauge-caption-text">Di Tier S &amp; Tier A</text>
                </svg>
            </div>

            <div class="fluid-kpi-col">
                <div class="fluid-kpi-num tabular"><?= $totalReviewsCount ?></div>
                <div class="fluid-kpi-foot">
                    <span class="fluid-kpi-label">Total ulasan<br>komunitas</span>
                    <a href="<?= e(base_url('index.php')) ?>" class="fluid-kpi-arrow" aria-label="Buka Katalog">
                        <?= svg_icon('arrow-right', 16) ?>
                    </a>
                </div>
            </div>

            <div class="fluid-kpi-col">
                <div class="fluid-kpi-num fluid-kpi-score tabular">
                    <?php if ($championNoodle): ?>
                        <?= svg_icon('star', 22) ?>
                        <span><?= e(format_score($championNoodle['avg_score'], 2)) ?></span>
                    <?php else: ?>
                        <span>–</span>
                    <?php endif; ?>
                </div>
                <div class="fluid-kpi-foot">
                    <span class="fluid-kpi-label">
                        <?php if ($championNoodle): ?>
                            #1 <?= e($championNoodle['name']) ?><br><?= $championTier ? 'Juara Tier ' . e($championTier) . ' saat ini' : 'Kandidat teratas' ?>
                        <?php else: ?>
                            Syarat minimal<br><?= $minRaters ?> penilai
                        <?php endif; ?>
                    </span>
                    <a href="<?= e(base_url($championNoodle ? 'noodle.php?id=' . (int) $championNoodle['id'] : 'top10.php')) ?>" class="fluid-kpi-arrow" aria-label="Buka Top 10">
                        <?= svg_icon('arrow-right', 16) ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="workspace-canvas">
        <div class="board-toolbar">
            <div class="board-title-group">
                <h1>Tierlist Komunitas</h1>
                <span class="count-sort-badge tabular"><?= $qualifiedCount ?> varian</span>
            </div>
            <p class="board-subtitle">Disusun otomatis dari rata-rata skor pengguna untuk varian dengan minimal <strong><?= $minRaters ?> penilai</strong>.</p>
        </div>

        <section class="tierboard-wrapper" aria-label="Papan Tierlist Komunitas S sampai F">
            <?php $spotlightPlaced = false; ?>
            <?php foreach (all_tiers() as $tierLetter): ?>
                <?php
                $meta = tier_meta($tierLetter);
                $itemsInTier = $tiers[$tierLetter] ?? [];
                ?>
                <div class="tier-row">
                    <div class="tier-row-header <?= e($meta['class']) ?>">
                        <span class="tier-row-letter"><?= e($tierLetter) ?></span>
                        <span class="tier-row-range tabular"><?= e($meta['range']) ?></span>
                    </div>
                    <div class="tier-dropzone tier-dropzone-readonly">
                        <?php if (empty($itemsInTier)): ?>
                            <span class="tier-empty-note">
                                Belum ada varian di Tier <?= e($tierLetter) ?> (<?= e($meta['range']) ?>).
                            </span>
                        <?php else: ?>
                            <?php foreach ($itemsInTier as $noodle): ?>
                                <?php
                                $isSpot = !$spotlightPlaced;
                                if ($isSpot) {
                                    $spotlightPlaced = true;
                                }
                                ?>
                                <article class="tier-item-card is-static js-community-card <?= $isSpot ? 'is-spotlight' : '' ?>">
                                    <div class="tier-card-top">
                                        <a href="<?= e(base_url('noodle.php?id=' . (int) $noodle['id'])) ?>" class="tier-item-swatch">
                                            <?= render_noodle_swatch($noodle, 'swatch-xs') ?>
                                        </a>
                                        <a href="<?= e(base_url('noodle.php?id=' . (int) $noodle['id'])) ?>" class="tier-item-name" title="<?= e($noodle['name']) ?>">
                                            <?= e($noodle['name']) ?>
                                        </a>
                                    </div>
                                    <div class="tier-item-meta tabular">
                                        <strong class="tier-item-score">
                                            <?= svg_icon('star', 12) ?>
                                            <?= e(format_score($noodle['avg_score'], 2)) ?>
                                        </strong>
                                        <span><?= (int) $noodle['rater_count'] ?> penilai</span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <?php if (!empty($pending)): ?>
            <section class="unranked-pool-box" aria-label="Mie Belum Cukup Penilai">
                <div class="unranked-pool-head">
                    <h2>Belum Cukup Penilai (&lt; <?= $minRaters ?> Penilai)</h2>
                    <p>Pilih varian di bawah untuk menambahkan penilaian agar masuk perhitungan Tierlist Komunitas dan Top 10.</p>
                </div>
                <div class="pending-list">
                    <?php foreach ($pending as $pNoodle): ?>
                        <a href="<?= e(base_url('noodle.php?id=' . (int) $pNoodle['id'])) ?>" class="pending-chip">
                            <strong><?= e($pNoodle['name']) ?></strong>
                            <span class="tabular">
                                <?= (int) $pNoodle['rater_count'] ?>/<?= $minRaters ?> penilai
                                <?php if ((int) $pNoodle['rater_count'] > 0): ?>
                                    · <?= e(format_score($pNoodle['avg_score'], 2)) ?>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<script>
(function () {
    var cards = document.querySelectorAll('.js-community-card');
    cards.forEach(function (card) {
        card.addEventListener('click', function (ev) {
            if (ev.target.closest('a')) return;
            cards.forEach(function (c) { c.classList.remove('is-spotlight'); });
            card.classList.add('is-spotlight');
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
