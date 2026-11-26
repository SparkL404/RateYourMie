<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/noodle_repo.php';

$filters = [
    'q'       => trim((string) ($_GET['q'] ?? '')),
    'brand'   => trim((string) ($_GET['brand'] ?? '')),
    'flavor'  => trim((string) ($_GET['flavor'] ?? '')),
    'country' => trim((string) ($_GET['country'] ?? '')),
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

$options = get_noodle_filter_options();
$catalog = get_catalog_noodles($filters, $page, $perPage);
$noodles = $catalog['items'];
$minRaters = (int) (get_app_settings()['min_raters'] ?? MIN_RATERS);

$hasActiveFilter = ($filters['q'] !== '' || $filters['brand'] !== '' || $filters['flavor'] !== '' || $filters['country'] !== '');

// Hitung ringkasan analitik pita alabaster dari data katalog aktual
$sumTaste = 0.0;
$sumTexture = 0.0;
$sumPortion = 0.0;
$sumValue = 0.0;
$sumScore = 0.0;
$ratedCount = 0;
$qualifiedCount = 0;
$sumPrice = 0;

foreach ($noodles as $item) {
    $sumPrice += (int) $item['price'];
    $rc = (int) $item['rater_count'];
    if ($rc > 0) {
        $ratedCount++;
        $sumTaste += (float) $item['avg_taste'];
        $sumTexture += (float) $item['avg_texture'];
        $sumPortion += (float) $item['avg_portion'];
        $sumValue += (float) $item['avg_value'];
        $sumScore += (float) $item['avg_score'];
    }
    if ($rc >= $minRaters) {
        $qualifiedCount++;
    }
}

$noodleCount = count($noodles);
$avgTaste = $ratedCount > 0 ? $sumTaste / $ratedCount : 0.0;
$avgTexture = $ratedCount > 0 ? $sumTexture / $ratedCount : 0.0;
$avgPortion = $ratedCount > 0 ? $sumPortion / $ratedCount : 0.0;
$avgValue = $ratedCount > 0 ? $sumValue / $ratedCount : 0.0;
$avgOverall = $ratedCount > 0 ? $sumScore / $ratedCount : 0.0;

$communitySummary = get_community_tierlist_data();
$topTierCount = count($communitySummary['tiers']['S'] ?? []) + count($communitySummary['tiers']['A'] ?? []);
$totalQualified = (int) ($communitySummary['qualified_count'] ?? 0);
$topTierPct = $totalQualified > 0 ? (int) round(($topTierCount / $totalQualified) * 100) : 0;

$totalReviewsCount = (int) db()->query('SELECT COUNT(*) FROM ratings')->fetchColumn();
$top10List = get_top10_noodles();
$championNoodle = !empty($top10List) ? $top10List[0] : ($noodles[0] ?? null);
$championTier = ($championNoodle && (int) ($championNoodle['rater_count'] ?? 0) > 0)
    ? score_to_tier((float) $championNoodle['avg_score'])
    : null;

$chartMetrics = [
    ['code' => 'RAS', 'label' => 'Rasa',    'val' => $avgTaste,   'bench' => 4.7],
    ['code' => 'TEK', 'label' => 'Tekstur', 'val' => $avgTexture, 'bench' => 4.6],
    ['code' => 'POR', 'label' => 'Porsi',   'val' => $avgPortion, 'bench' => 4.4],
    ['code' => 'HRG', 'label' => 'Harga',   'val' => $avgValue,   'bench' => 4.6],
    ['code' => 'AVG', 'label' => 'Skor',    'val' => $avgOverall, 'bench' => 4.5],
];

$totalTicks = 36;
$activeTicks = (int) round(($topTierPct / 100) * $totalTicks);

$pageTitle = 'Katalog & Rating Mie Instan';
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="workspace-main">
    <section class="alabaster-hero-band" aria-label="Pencarian dan Analitik Katalog">
        <form action="<?= e(base_url('index.php')) ?>" method="get" class="command-bar">
            <div class="command-search">
                <span class="command-search-icon"><?= svg_icon('search', 16) ?></span>
                <label for="filter-q" class="skip-link">Cari varian mie</label>
                <input
                    type="search"
                    id="filter-q"
                    name="q"
                    class="command-search-input"
                    value="<?= e($filters['q']) ?>"
                    placeholder="Cari varian mie, brand, atau rasa..."
                >
                <kbd class="kbd-shortcut" aria-hidden="true">/</kbd>
            </div>

            <div class="command-controls">
                <div class="command-select-wrap">
                    <label for="filter-brand" class="skip-link">Filter Brand</label>
                    <select id="filter-brand" name="brand" class="command-select">
                        <option value="">Semua Brand</option>
                        <?php foreach ($options['brands'] as $b): ?>
                            <option value="<?= e($b) ?>" <?= $filters['brand'] === $b ? 'selected' : '' ?>>
                                <?= e($b) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="command-select-wrap">
                    <label for="filter-flavor" class="skip-link">Filter Varian Rasa</label>
                    <select id="filter-flavor" name="flavor" class="command-select">
                        <option value="">Semua Rasa</option>
                        <?php foreach ($options['flavors'] as $fl): ?>
                            <option value="<?= e($fl) ?>" <?= $filters['flavor'] === $fl ? 'selected' : '' ?>>
                                <?= e($fl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="command-select-wrap">
                    <label for="filter-country" class="skip-link">Filter Negara</label>
                    <select id="filter-country" name="country" class="command-select">
                        <option value="">Semua Negara</option>
                        <?php foreach ($options['countries'] as $c): ?>
                            <option value="<?= e($c) ?>" <?= $filters['country'] === $c ? 'selected' : '' ?>>
                                <?= e($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-ghost btn-sm command-apply-btn">
                    <?= svg_icon('sliders', 14) ?> Saring
                </button>

                <?php if ($hasActiveFilter): ?>
                    <a href="<?= e(base_url('index.php')) ?>" class="btn btn-ghost btn-sm">Reset</a>
                <?php endif; ?>

                <a href="<?= e(base_url(current_user() !== null ? 'pages/tierlists.php' : 'auth/login.php')) ?>" class="btn btn-obsidian">
                    <?= svg_icon('plus', 15) ?>
                    <span>Tierlist Baru</span>
                </a>
            </div>
        </form>

        <div class="analytics-4col">
            <div class="analytics-chart-col">
                <h2 class="analytics-heading">Profil 4 Aspek Katalog</h2>
                <div class="dual-bar-chart" aria-label="Perbandingan rata-rata skor 4 aspek terhadap standar ideal">
                    <div class="dual-y-axis tabular" aria-hidden="true">
                        <span>5.0</span>
                        <span>2.5</span>
                        <span>0</span>
                    </div>
                    <div class="dual-bar-groups">
                        <?php foreach ($chartMetrics as $cm): ?>
                            <?php
                            $actualH = (int) round(min(100, max(8, ($cm['val'] / 5.0) * 100)));
                            $benchH = (int) round(min(100, max(12, ($cm['bench'] / 5.0) * 100)));
                            ?>
                            <div class="dual-bar-col" title="<?= e($cm['label']) ?>: <?= e(format_score($cm['val'], 2)) ?> / 5.00">
                                <div class="dual-bar-pair">
                                    <span class="bar-hatched" style="height:<?= $benchH ?>%"></span>
                                    <span class="bar-solid" style="height:<?= $actualH ?>%"></span>
                                </div>
                                <span class="dual-bar-label tabular"><?= e($cm['code']) ?></span>
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
                    <a href="<?= e(base_url('pages/community.php')) ?>" class="fluid-kpi-arrow" aria-label="Buka Tierlist Komunitas">
                        <?= svg_icon('arrow-right', 16) ?>
                    </a>
                </div>
            </div>

            <div class="fluid-kpi-col">
                <div class="fluid-kpi-num fluid-kpi-score tabular">
                    <?php if ($championNoodle && (int) ($championNoodle['rater_count'] ?? 0) > 0): ?>
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
                            Belum ada<br>peringkat juara
                        <?php endif; ?>
                    </span>
                    <a href="<?= e(base_url($championNoodle ? 'pages/noodle.php?id=' . (int) $championNoodle['id'] : 'pages/top10.php')) ?>" class="fluid-kpi-arrow" aria-label="Lihat Juara #1">
                        <?= svg_icon('arrow-right', 16) ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="workspace-canvas">
        <div class="board-toolbar">
            <div class="board-title-group">
                <h1>Katalog &amp; Indeks Rasa</h1>
                <span class="count-sort-badge tabular">
                    <span id="js-visible-count"><?= (int) $noodleCount ?></span>
                    <?= svg_icon('sort', 13) ?>
                </span>
            </div>

            <div class="quick-chips-bar" role="toolbar" aria-label="Saring Cepat Halaman Ini">
                <button type="button" class="chip-pill is-active js-quick-chip" data-chip="all">Semua</button>
                <button type="button" class="chip-pill js-quick-chip" data-chip="goreng">Mi Goreng</button>
                <button type="button" class="chip-pill js-quick-chip" data-chip="kuah">Mi Kuah</button>
                <button type="button" class="chip-pill js-quick-chip" data-chip="pedas">Pedas</button>
                <button type="button" class="chip-pill js-quick-chip" data-chip="budget">Hemat &le; Rp 5.000</button>
                <button type="button" class="chip-pill js-quick-chip" data-chip="import">Impor</button>
            </div>
        </div>

        <?php if (empty($noodles)): ?>
            <div class="empty-state">
                <h2>Belum Ada Mie yang Cocok</h2>
                <p>Tidak ditemukan varian mie instan dengan kriteria pencarian atau filter tersebut.</p>
                <?php if ($hasActiveFilter): ?>
                    <a href="<?= e(base_url('index.php')) ?>" class="btn btn-obsidian">Tampilkan Semua Mie</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="catalog-grid" id="js-catalog-grid">
                <?php foreach ($noodles as $idx => $noodle): ?>
                    <?php
                    $raterCount = (int) $noodle['rater_count'];
                    $avgScore = $raterCount > 0 ? (float) $noodle['avg_score'] : null;
                    $tierLetter = ($avgScore !== null && $raterCount >= $minRaters) ? score_to_tier($avgScore) : null;
                    $priceInt = (int) $noodle['price'];
                    $flavorLower = mb_strtolower($noodle['flavor'] . ' ' . $noodle['name'], 'UTF-8');
                    $isGoreng = str_contains($flavorLower, 'goreng') ? '1' : '0';
                    $isKuah = (str_contains($flavorLower, 'kuah') || str_contains($flavorLower, 'soto') || str_contains($flavorLower, 'ramyun') || str_contains($flavorLower, 'tonkotsu') || str_contains($flavorLower, 'laksa') || str_contains($flavorLower, 'tom yum') || str_contains($flavorLower, 'cup')) ? '1' : '0';
                    $isPedas = (str_contains($flavorLower, 'pedas') || str_contains($flavorLower, 'spicy') || str_contains($flavorLower, 'buldak') || str_contains($flavorLower, 'shin') || str_contains($flavorLower, 'aceh')) ? '1' : '0';
                    $isImport = strcasecmp((string) $noodle['origin_country'], 'Indonesia') !== 0 ? '1' : '0';

                    $aspects = [
                        ['code' => 'RAS', 'val' => $raterCount > 0 ? (float) $noodle['avg_taste'] : null],
                        ['code' => 'TEK', 'val' => $raterCount > 0 ? (float) $noodle['avg_texture'] : null],
                        ['code' => 'POR', 'val' => $raterCount > 0 ? (float) $noodle['avg_portion'] : null],
                        ['code' => 'HRG', 'val' => $raterCount > 0 ? (float) $noodle['avg_value'] : null],
                    ];
                    $isSpotlightDefault = ($idx === 0);
                    ?>
                    <article
                        class="noodle-card js-catalog-card <?= $isSpotlightDefault ? 'is-spotlight' : '' ?>"
                        data-goreng="<?= $isGoreng ?>"
                        data-kuah="<?= $isKuah ?>"
                        data-pedas="<?= $isPedas ?>"
                        data-budget="<?= $priceInt <= 5000 ? '1' : '0' ?>"
                        data-import="<?= $isImport ?>"
                        data-search="<?= e(mb_strtolower($noodle['name'] . ' ' . $noodle['brand'] . ' ' . $noodle['flavor'] . ' ' . $noodle['origin_country'], 'UTF-8')) ?>"
                    >
                        <div class="noodle-card-body">
                            <div class="noodle-card-head">
                                <a href="<?= e(base_url('pages/noodle.php?id=' . (int) $noodle['id'])) ?>" class="noodle-swatch-link" aria-label="Buka detail <?= e($noodle['name']) ?>">
                                    <?= render_noodle_swatch($noodle, 'swatch-catalog') ?>
                                </a>
                                <div class="noodle-head-copy">
                                    <div class="noodle-card-meta">
                                        <span><?= e($noodle['brand']) ?> · <?= e($noodle['origin_country']) ?></span>
                                    </div>
                                    <h2 class="noodle-card-title">
                                        <a href="<?= e(base_url('pages/noodle.php?id=' . (int) $noodle['id'])) ?>">
                                            <?= e($noodle['name']) ?>
                                        </a>
                                    </h2>
                                </div>
                                <div class="noodle-head-tier">
                                    <?php if ($tierLetter !== null): ?>
                                        <?php $tMeta = tier_meta($tierLetter); ?>
                                        <span class="tier-badge <?= e($tMeta['class']) ?>" title="Tier Komunitas <?= e($tierLetter) ?> (<?= e($tMeta['name']) ?>)">
                                            <?= e($tierLetter) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="tier-badge tier-unranked" title="Membutuhkan minimal <?= $minRaters ?> penilai untuk masuk Tier Komunitas">
                                            <?= $raterCount > 0 ? $raterCount . '/' . $minRaters : 'BARU' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <p class="noodle-card-excerpt">
                                <?= !empty($noodle['description']) ? e((string) $noodle['description']) : e($noodle['brand'] . ' varian ' . $noodle['flavor'] . ' asal ' . $noodle['origin_country'] . '.') ?>
                            </p>

                            <div class="noodle-spotlight-meta">
                                <div class="spotlight-detail-row">
                                    <span class="spotlight-detail-label">Varian &amp; Syarat Tier</span>
                                    <strong><?= e($noodle['flavor']) ?> · Min. <?= $minRaters ?> penilai</strong>
                                </div>
                                <div class="spotlight-detail-row">
                                    <span class="spotlight-detail-label">Asal Produk</span>
                                    <strong><?= e($noodle['origin_country']) ?></strong>
                                </div>
                            </div>

                            <dl class="aspect-bars-grid" aria-label="Rata-rata 4 Aspek">
                                <?php foreach ($aspects as $asp): ?>
                                    <?php
                                    $v = $asp['val'];
                                    $pct = $v !== null ? min(100, max(0, ($v / 5.0) * 100)) : 0;
                                    $barTone = '';
                                    if ($v !== null) {
                                        $barTone = $v >= 4.4 ? 'is-high' : ($v <= 2.5 ? 'is-low' : '');
                                    }
                                    ?>
                                    <div class="aspect-bar-item">
                                        <dt class="tabular"><?= e($asp['code']) ?></dt>
                                        <dd class="aspect-bar-cell">
                                            <span class="aspect-micro-track" aria-hidden="true">
                                                <span class="aspect-micro-fill <?= $barTone ?>" style="width:<?= round($pct, 1) ?>%"></span>
                                            </span>
                                            <span class="aspect-micro-val tabular"><?= $v !== null ? e(format_score($v, 1)) : '–' ?></span>
                                        </dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>

                            <div class="noodle-card-footer">
                                <span class="price-pill-badge tabular"><?= e(format_rupiah($priceInt)) ?></span>
                                <div class="card-footer-right">
                                    <?php if ($raterCount > 0 && $avgScore !== null): ?>
                                        <span class="score-inline-meta tabular">
                                            <?= svg_icon('star', 13) ?>
                                            <strong><?= e(format_score($avgScore, 2)) ?></strong>
                                            <span class="rater-count-label">· <?= $raterCount ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="score-inline-meta">Baru</span>
                                    <?php endif; ?>
                                    <a href="<?= e(base_url('pages/noodle.php?id=' . (int) $noodle['id'])) ?>" class="card-action-link">
                                        <span>Nilai</span>
                                        <?= svg_icon('arrow-right', 13) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="js-chip-empty" class="empty-state" hidden>
                <h2>Tidak Ada Mie pada Filter Cepat Ini</h2>
                <p>Coba pilih chip kategori lain atau tekan Semua.</p>
            </div>

            <?php if ($catalog['total_pages'] > 1): ?>
                <nav class="pagination-bar" aria-label="Navigasi Halaman Katalog">
                    <span>Halaman <strong><?= (int) $catalog['page'] ?></strong> dari <strong><?= (int) $catalog['total_pages'] ?></strong> · <?= (int) $catalog['total'] ?> mie</span>
                    <div class="pagination-links">
                        <?php for ($p = 1; $p <= $catalog['total_pages']; $p++): ?>
                            <?php
                            $query = http_build_query(array_filter([
                                'q'       => $filters['q'],
                                'brand'   => $filters['brand'],
                                'flavor'  => $filters['flavor'],
                                'country' => $filters['country'],
                                'page'    => $p,
                            ], static fn($v) => $v !== '' && $v !== null));
                            ?>
                            <a
                                href="<?= e(base_url('index.php?' . $query)) ?>"
                                class="page-btn <?= $p === $catalog['page'] ? 'is-current' : '' ?>"
                                <?= $p === $catalog['page'] ? 'aria-current="page"' : '' ?>
                            ><?= $p ?></a>
                        <?php endfor; ?>
                    </div>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<script>
(function () {
    var searchInput = document.getElementById('filter-q');
    var chips = document.querySelectorAll('.js-quick-chip');
    var cards = document.querySelectorAll('.js-catalog-card');
    var emptyNotice = document.getElementById('js-chip-empty');
    var countEl = document.getElementById('js-visible-count');
    var activeChip = 'all';

    document.addEventListener('keydown', function (ev) {
        if (ev.key === '/' && document.activeElement !== searchInput && !['INPUT', 'TEXTAREA', 'SELECT'].includes((document.activeElement || {}).tagName)) {
            ev.preventDefault();
            if (searchInput) searchInput.focus();
        }
    });

    function applyClientFilter() {
        if (!cards.length) return;
        var visible = 0;
        cards.forEach(function (card) {
            var matchChip = (activeChip === 'all') || (card.getAttribute('data-' + activeChip) === '1');
            card.hidden = !matchChip;
            if (matchChip) visible++;
        });
        if (countEl) {
            countEl.textContent = String(visible);
        }
        if (emptyNotice) {
            emptyNotice.hidden = visible > 0;
        }
    }

    chips.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeChip = btn.getAttribute('data-chip') || 'all';
            chips.forEach(function (c) {
                c.classList.toggle('is-active', c === btn);
            });
            applyClientFilter();
        });
    });

    cards.forEach(function (card) {
        card.addEventListener('click', function (ev) {
            if (ev.target.closest('a, button')) return;
            cards.forEach(function (c) { c.classList.remove('is-spotlight'); });
            card.classList.add('is-spotlight');
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
