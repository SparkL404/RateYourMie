<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/noodle_repo.php';

$allowedChips = ['goreng', 'kuah', 'pedas', 'budget', 'import'];
$chipLabels = [
    'all'    => 'Semua',
    'goreng' => 'Mi Goreng',
    'kuah'   => 'Mi Kuah',
    'pedas'  => 'Pedas',
    'budget' => 'Hemat ≤ Rp 5.000',
    'import' => 'Impor',
];
$sortOptions = [
    'score'      => 'Skor Tertinggi',
    'raters'     => 'Penilai Terbanyak',
    'price_asc'  => 'Harga Termurah',
    'price_desc' => 'Harga Termahal',
    'name'       => 'Nama A–Z',
];

$rawChip = trim((string) ($_GET['chip'] ?? ''));
$activeChip = in_array($rawChip, $allowedChips, true) ? $rawChip : '';

$rawSort = trim((string) ($_GET['sort'] ?? 'score'));
$activeSort = array_key_exists($rawSort, $sortOptions) ? $rawSort : 'score';

$filters = [
    'q'       => trim((string) ($_GET['q'] ?? '')),
    'brand'   => trim((string) ($_GET['brand'] ?? '')),
    'flavor'  => trim((string) ($_GET['flavor'] ?? '')),
    'country' => trim((string) ($_GET['country'] ?? '')),
    'chip'    => $activeChip,
    'sort'    => $activeSort,
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

$catalogUrl = static function (array $overrides = []) use ($filters, $page): string {
    $state = array_merge($filters, ['page' => $page], $overrides);
    $query = [];
    foreach (['q', 'brand', 'flavor', 'country', 'chip'] as $key) {
        $val = trim((string) ($state[$key] ?? ''));
        if ($val !== '') {
            $query[$key] = $val;
        }
    }
    $sortVal = trim((string) ($state['sort'] ?? 'score'));
    if ($sortVal !== '' && $sortVal !== 'score') {
        $query['sort'] = $sortVal;
    }
    $pageVal = max(1, (int) ($state['page'] ?? 1));
    if ($pageVal > 1) {
        $query['page'] = $pageVal;
    }
    $qs = http_build_query($query);
    return base_url('index.php' . ($qs !== '' ? '?' . $qs : ''));
};

$options = get_noodle_filter_options();
$catalog = get_catalog_noodles($filters, $page, $perPage);
$noodles = $catalog['items'];
$noodleCount = count($noodles);
$minRaters = (int) (get_app_settings()['min_raters'] ?? MIN_RATERS);

$hasActiveFilter = (
    $filters['q'] !== ''
    || $filters['brand'] !== ''
    || $filters['flavor'] !== ''
    || $filters['country'] !== ''
    || $activeChip !== ''
);

// Analitik 4 aspek nyata untuk seluruh hasil cocok (lintas halaman) dan patokan pembanding aktual
$analytics = get_catalog_analytics($filters);
$benchAnalytics = $hasActiveFilter
    ? get_catalog_analytics([])
    : get_top_tier_aspect_averages($minRaters);

$benchLegendLabel = $hasActiveFilter ? 'Semua Katalog' : 'Rata-rata Tier S–A';
$actualLegendLabel = $hasActiveFilter ? 'Hasil Filter' : 'Semua Katalog';

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
    ['code' => 'RAS', 'label' => 'Rasa',    'val' => $analytics['avg_taste'],   'bench' => $benchAnalytics['avg_taste']],
    ['code' => 'TEK', 'label' => 'Tekstur', 'val' => $analytics['avg_texture'], 'bench' => $benchAnalytics['avg_texture']],
    ['code' => 'POR', 'label' => 'Porsi',   'val' => $analytics['avg_portion'], 'bench' => $benchAnalytics['avg_portion']],
    ['code' => 'HRG', 'label' => 'Harga',   'val' => $analytics['avg_value'],   'bench' => $benchAnalytics['avg_value']],
    ['code' => 'AVG', 'label' => 'Skor',    'val' => $analytics['avg_score'],   'bench' => $benchAnalytics['avg_score']],
];

$totalTicks = 36;
$activeTicks = (int) round(($topTierPct / 100) * $totalTicks);

$activeFilterPills = [];
if ($filters['q'] !== '') {
    $activeFilterPills[] = [
        'label' => 'Cari: "' . $filters['q'] . '"',
        'url'   => $catalogUrl(['q' => '', 'page' => 1]),
    ];
}
if ($filters['brand'] !== '') {
    $activeFilterPills[] = [
        'label' => 'Brand: ' . $filters['brand'],
        'url'   => $catalogUrl(['brand' => '', 'page' => 1]),
    ];
}
if ($filters['flavor'] !== '') {
    $activeFilterPills[] = [
        'label' => 'Rasa: ' . $filters['flavor'],
        'url'   => $catalogUrl(['flavor' => '', 'page' => 1]),
    ];
}
if ($filters['country'] !== '') {
    $activeFilterPills[] = [
        'label' => 'Negara: ' . $filters['country'],
        'url'   => $catalogUrl(['country' => '', 'page' => 1]),
    ];
}
if ($activeChip !== '') {
    $activeFilterPills[] = [
        'label' => 'Kategori: ' . ($chipLabels[$activeChip] ?? $activeChip),
        'url'   => $catalogUrl(['chip' => '', 'page' => 1]),
    ];
}

$pageTitle = 'Katalog & Rating Mie Instan';
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="workspace-main">
    <section class="alabaster-hero-band" aria-label="Pencarian dan Analitik Katalog">
        <form action="<?= e(base_url('index.php')) ?>" method="get" class="command-bar">
            <?php if ($activeChip !== ''): ?>
                <input type="hidden" name="chip" value="<?= e($activeChip) ?>">
            <?php endif; ?>
            <?php if ($activeSort !== 'score'): ?>
                <input type="hidden" name="sort" value="<?= e($activeSort) ?>">
            <?php endif; ?>

            <div class="command-search <?= $filters['q'] !== '' ? 'has-value' : '' ?>" id="js-command-search">
                <span class="command-search-icon"><?= svg_icon('search', 16) ?></span>
                <label for="filter-q" class="sr-only">Cari varian mie</label>
                <input
                    type="search"
                    id="filter-q"
                    name="q"
                    class="command-search-input"
                    value="<?= e($filters['q']) ?>"
                    placeholder="Cari mie, brand, rasa, negara..."
                >
                <kbd class="kbd-shortcut" aria-hidden="true">/</kbd>
            </div>

            <div class="command-filters">
                <div class="command-select-wrap">
                    <label for="filter-brand" class="sr-only">Filter Brand</label>
                    <select id="filter-brand" name="brand" class="command-select <?= $filters['brand'] !== '' ? 'is-filtered' : '' ?>">
                        <option value="">Semua Brand</option>
                        <?php foreach ($options['brands'] as $b): ?>
                            <option value="<?= e($b) ?>" <?= $filters['brand'] === $b ? 'selected' : '' ?>>
                                <?= e($b) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="command-select-wrap">
                    <label for="filter-flavor" class="sr-only">Filter Varian Rasa</label>
                    <select id="filter-flavor" name="flavor" class="command-select <?= $filters['flavor'] !== '' ? 'is-filtered' : '' ?>">
                        <option value="">Semua Rasa</option>
                        <?php foreach ($options['flavors'] as $fl): ?>
                            <option value="<?= e($fl) ?>" <?= $filters['flavor'] === $fl ? 'selected' : '' ?>>
                                <?= e($fl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="command-select-wrap">
                    <label for="filter-country" class="sr-only">Filter Negara</label>
                    <select id="filter-country" name="country" class="command-select <?= $filters['country'] !== '' ? 'is-filtered' : '' ?>">
                        <option value="">Semua Negara</option>
                        <?php foreach ($options['countries'] as $c): ?>
                            <option value="<?= e($c) ?>" <?= $filters['country'] === $c ? 'selected' : '' ?>>
                                <?= e($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="command-filter-actions">
                    <button type="submit" class="btn btn-command-submit btn-sm">
                        <?= svg_icon('sliders', 14) ?> Saring
                    </button>

                    <?php if ($hasActiveFilter): ?>
                        <a href="<?= e($catalogUrl(['q' => '', 'brand' => '', 'flavor' => '', 'country' => '', 'chip' => '', 'page' => 1])) ?>" class="btn btn-ghost btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="command-cta">
                <a href="<?= e(base_url(current_user() !== null ? 'pages/tierlists.php' : 'auth/login.php')) ?>" class="btn btn-obsidian">
                    <?= svg_icon('plus', 15) ?>
                    <span>Tierlist Baru</span>
                </a>
            </div>
        </form>

        <div class="analytics-4col">
            <div class="analytics-chart-col">
                <div class="analytics-heading-row">
                    <p class="analytics-heading">Profil 4 Aspek Katalog</p>
                    <div class="dual-chart-legend" aria-hidden="true">
                        <span class="legend-item">
                            <span class="legend-swatch is-hatched"></span>
                            <span><?= e($benchLegendLabel) ?></span>
                        </span>
                        <span class="legend-item">
                            <span class="legend-swatch is-solid"></span>
                            <span><?= e($actualLegendLabel) ?></span>
                        </span>
                    </div>
                </div>
                <div class="dual-bar-chart" role="img" aria-label="Perbandingan rata-rata skor 4 aspek: <?= e($actualLegendLabel) ?> terhadap <?= e($benchLegendLabel) ?>">
                    <div class="dual-y-axis tabular" aria-hidden="true">
                        <span>5.0</span>
                        <span>2.5</span>
                        <span>0</span>
                    </div>
                    <div class="dual-bar-groups">
                        <?php foreach ($chartMetrics as $cm): ?>
                            <?php
                            $hasVal = $cm['val'] > 0.0;
                            $hasBench = $cm['bench'] > 0.0;
                            $actualH = $hasVal ? (int) round(min(100, max(6, ($cm['val'] / 5.0) * 100))) : 0;
                            $benchH = $hasBench ? (int) round(min(100, max(6, ($cm['bench'] / 5.0) * 100))) : 0;
                            $valText = $hasVal ? format_score($cm['val'], 2) : '–';
                            $benchText = $hasBench ? format_score($cm['bench'], 2) : '–';
                            ?>
                            <div
                                class="dual-bar-col"
                                tabindex="0"
                                aria-label="<?= e($cm['label']) ?>: <?= e($valText) ?> dari 5.00 (<?= e($benchLegendLabel) ?> <?= e($benchText) ?>)"
                                title="<?= e($cm['label']) ?>: <?= e($valText) ?> / 5.00 · <?= e($benchLegendLabel) ?>: <?= e($benchText) ?>"
                            >
                                <div class="dual-bar-pair">
                                    <span class="dual-bar-val tabular" aria-hidden="true"><?= e($valText) ?></span>
                                    <span class="bar-hatched <?= $hasBench ? '' : 'is-empty' ?>" style="height:<?= $benchH ?>%"></span>
                                    <span class="bar-solid <?= $hasVal ? '' : 'is-empty' ?>" style="height:<?= $actualH ?>%"></span>
                                </div>
                                <span class="dual-bar-label tabular"><?= e($cm['code']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="analytics-gauge-col">
                <svg class="radial-gauge-svg" viewBox="0 0 210 118" role="img" aria-label="<?= $topTierCount ?> dari <?= $totalQualified ?> mie yang lolos syarat berada di Tier S dan Tier A (<?= $topTierPct ?>%)">
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
                    <text x="105" y="96" text-anchor="middle" class="gauge-caption-text">Tier S–A (<?= $topTierCount ?>/<?= $totalQualified ?> lolos)</text>
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
                            <span class="kpi-champion-name" title="#1 <?= e($championNoodle['name']) ?>">#1 <?= e($championNoodle['name']) ?></span>
                            <span class="kpi-champion-sub"><?= $championTier ? 'Juara Tier ' . e($championTier) . ' saat ini' : 'Kandidat teratas' ?></span>
                        <?php else: ?>
                            <span>Belum ada<br>peringkat juara</span>
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
        <div class="board-header-stack">
            <div class="board-toolbar">
                <div class="board-title-group">
                    <h1>Katalog &amp; Indeks Rasa</h1>
                    <span class="count-badge tabular" title="Menampilkan <?= (int) $noodleCount ?> dari <?= (int) $catalog['total'] ?> varian">
                        <?= (int) $catalog['total'] ?> varian
                    </span>
                    <form action="<?= e(base_url('index.php')) ?>" method="get" class="sort-control-form">
                        <?php foreach (['q', 'brand', 'flavor', 'country', 'chip'] as $paramKey): ?>
                            <?php if (($filters[$paramKey] ?? '') !== ''): ?>
                                <input type="hidden" name="<?= e($paramKey) ?>" value="<?= e($filters[$paramKey]) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <label for="filter-sort" class="sr-only">Urutkan katalog</label>
                        <div class="sort-select-wrap">
                            <span class="sort-select-icon" aria-hidden="true"><?= svg_icon('sort', 13) ?></span>
                            <select id="filter-sort" name="sort" class="sort-select js-auto-sort">
                                <?php foreach ($sortOptions as $sKey => $sLabel): ?>
                                    <option value="<?= e($sKey) ?>" <?= $activeSort === $sKey ? 'selected' : '' ?>>
                                        <?= e($sLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <noscript>
                            <button type="submit" class="btn btn-secondary btn-sm">Urutkan</button>
                        </noscript>
                    </form>
                </div>

                <nav class="quick-chips-bar" aria-label="Saring Cepat Kategori">
                    <?php foreach ($chipLabels as $cKey => $cLabel): ?>
                        <?php
                        $chipParam = $cKey === 'all' ? '' : $cKey;
                        $isChipActive = ($activeChip === $chipParam);
                        ?>
                        <a
                            href="<?= e($catalogUrl(['chip' => $chipParam, 'page' => 1])) ?>"
                            class="chip-pill <?= $isChipActive ? 'is-active' : '' ?>"
                            <?= $isChipActive ? 'aria-current="true"' : '' ?>
                        ><?= e($cLabel) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <?php if (!empty($activeFilterPills)): ?>
                <div class="active-filters-strip" aria-label="Filter yang sedang aktif">
                    <span class="active-filters-label">Filter aktif:</span>
                    <?php foreach ($activeFilterPills as $pill): ?>
                        <a href="<?= e($pill['url']) ?>" class="active-filter-pill" title="Hapus <?= e($pill['label']) ?>">
                            <span><?= e($pill['label']) ?></span>
                            <?= svg_icon('x', 12) ?>
                        </a>
                    <?php endforeach; ?>
                    <a href="<?= e($catalogUrl(['q' => '', 'brand' => '', 'flavor' => '', 'country' => '', 'chip' => '', 'page' => 1])) ?>" class="active-filter-clear">
                        Reset Semua
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($noodles)): ?>
            <div class="empty-state">
                <span class="empty-state-icon" aria-hidden="true"><?= svg_icon('search', 22) ?></span>
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

                    $aspects = [
                        ['code' => 'RAS', 'name' => 'Rasa',    'val' => $raterCount > 0 ? (float) $noodle['avg_taste'] : null],
                        ['code' => 'TEK', 'name' => 'Tekstur', 'val' => $raterCount > 0 ? (float) $noodle['avg_texture'] : null],
                        ['code' => 'POR', 'name' => 'Porsi',   'val' => $raterCount > 0 ? (float) $noodle['avg_portion'] : null],
                        ['code' => 'HRG', 'name' => 'Harga',   'val' => $raterCount > 0 ? (float) $noodle['avg_value'] : null],
                    ];
                    $isSpotlightDefault = ($idx === 0);
                    $tierStatusText = $tierLetter !== null
                        ? 'Tier ' . $tierLetter . ' · ' . $raterCount . ' penilai'
                        : ($raterCount > 0
                            ? 'Butuh ' . max(1, $minRaters - $raterCount) . ' penilai lagi'
                            : 'Belum ada penilai');
                    ?>
                    <article class="noodle-card js-catalog-card <?= $isSpotlightDefault ? 'is-spotlight' : '' ?>">
                        <div class="noodle-card-body">
                            <button
                                type="button"
                                class="spotlight-pin-btn js-spotlight-btn"
                                aria-pressed="<?= $isSpotlightDefault ? 'true' : 'false' ?>"
                                aria-label="Sorot <?= e($noodle['name']) ?>"
                            ><?= $isSpotlightDefault ? 'Disorot' : 'Sorot' ?></button>

                            <div class="noodle-card-head">
                                <a href="<?= e(base_url('pages/noodle.php?id=' . (int) $noodle['id'])) ?>" class="noodle-swatch-link" aria-label="Buka detail <?= e($noodle['name']) ?>">
                                    <?= render_noodle_swatch($noodle, 'swatch-catalog', $idx < 3) ?>
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
                                <div class="spotlight-detail-cell">
                                    <span class="spotlight-detail-label">Varian Rasa</span>
                                    <strong title="<?= e($noodle['flavor']) ?>"><?= e($noodle['flavor']) ?></strong>
                                </div>
                                <div class="spotlight-detail-cell">
                                    <span class="spotlight-detail-label">Status Tier</span>
                                    <strong title="<?= e($tierStatusText) ?>"><?= e($tierStatusText) ?></strong>
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
                                        <dt class="tabular"><abbr title="<?= e($asp['name']) ?>"><?= e($asp['code']) ?></abbr></dt>
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

            <?php if ($catalog['total_pages'] > 1): ?>
                <?php
                $curPage = (int) $catalog['page'];
                $totalPages = (int) $catalog['total_pages'];
                ?>
                <nav class="pagination-bar" aria-label="Navigasi Halaman Katalog">
                    <span>Halaman <strong><?= $curPage ?></strong> dari <strong><?= $totalPages ?></strong> · <?= (int) $catalog['total'] ?> mie</span>
                    <div class="pagination-links">
                        <?php if ($curPage > 1): ?>
                            <a href="<?= e($catalogUrl(['page' => $curPage - 1])) ?>" class="page-btn page-btn-nav">
                                <?= svg_icon('arrow-left', 13) ?>
                                <span>Sebelumnya</span>
                            </a>
                        <?php else: ?>
                            <span class="page-btn page-btn-nav is-disabled" aria-disabled="true">
                                <?= svg_icon('arrow-left', 13) ?>
                                <span>Sebelumnya</span>
                            </span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a
                                href="<?= e($catalogUrl(['page' => $p])) ?>"
                                class="page-btn <?= $p === $curPage ? 'is-current' : '' ?>"
                                <?= $p === $curPage ? 'aria-current="page"' : '' ?>
                            ><?= $p ?></a>
                        <?php endfor; ?>

                        <?php if ($curPage < $totalPages): ?>
                            <a href="<?= e($catalogUrl(['page' => $curPage + 1])) ?>" class="page-btn page-btn-nav">
                                <span>Berikutnya</span>
                                <?= svg_icon('arrow-right', 13) ?>
                            </a>
                        <?php else: ?>
                            <span class="page-btn page-btn-nav is-disabled" aria-disabled="true">
                                <span>Berikutnya</span>
                                <?= svg_icon('arrow-right', 13) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<script>
(function () {
    var searchWrap = document.getElementById('js-command-search');
    var searchInput = document.getElementById('filter-q');
    var sortSelect = document.querySelector('.js-auto-sort');
    var cards = document.querySelectorAll('.js-catalog-card');

    if (searchInput && searchWrap) {
        var syncSearchHasValue = function () {
            searchWrap.classList.toggle('has-value', searchInput.value.trim() !== '');
        };
        searchInput.addEventListener('input', syncSearchHasValue);
        syncSearchHasValue();
    }

    document.addEventListener('keydown', function (ev) {
        if (ev.key === '/' && searchInput && document.activeElement !== searchInput && !['INPUT', 'TEXTAREA', 'SELECT'].includes((document.activeElement || {}).tagName)) {
            ev.preventDefault();
            searchInput.focus();
        }
    });

    if (sortSelect && sortSelect.form) {
        sortSelect.addEventListener('change', function () {
            sortSelect.form.submit();
        });
    }

    function setSpotlight(targetCard) {
        cards.forEach(function (card) {
            var active = (card === targetCard);
            card.classList.toggle('is-spotlight', active);
            var btn = card.querySelector('.js-spotlight-btn');
            if (btn) {
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                btn.textContent = active ? 'Disorot' : 'Sorot';
            }
        });
    }

    cards.forEach(function (card) {
        var spotBtn = card.querySelector('.js-spotlight-btn');
        if (spotBtn) {
            spotBtn.addEventListener('click', function () {
                setSpotlight(card);
            });
        }
        card.addEventListener('click', function (ev) {
            if (ev.target.closest('a, button')) return;
            setSpotlight(card);
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
