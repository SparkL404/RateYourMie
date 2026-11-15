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

$pageTitle = 'Katalog & Rating Mie Instan';
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container section-block">
    <section class="page-head">
        <div class="page-head-copy">
            <h1>Katalog &amp; Rating Mie Instan</h1>
            <p>Indeks terstruktur berdasarkan Rasa, Tekstur, Porsi, dan Harga dari ulasan komunitas.</p>
        </div>
        <div class="page-head-meta tabular">
            <span><strong><?= (int) $catalog['total'] ?></strong> varian</span>
            <span class="meta-dot" aria-hidden="true">·</span>
            <span>Ambang tier min. <strong><?= $minRaters ?></strong> penilai</span>
        </div>
    </section>

    <section class="filter-toolbar" aria-label="Pencarian dan Filter Mie">
        <form action="<?= e(base_url('index.php')) ?>" method="get" class="filter-grid">
            <div class="form-group">
                <label for="filter-q">Cari Mie</label>
                <input
                    type="search"
                    id="filter-q"
                    name="q"
                    class="form-control"
                    value="<?= e($filters['q']) ?>"
                    placeholder="Indomie, Soto, Buldak..."
                >
            </div>

            <div class="form-group">
                <label for="filter-brand">Brand</label>
                <select id="filter-brand" name="brand" class="form-control">
                    <option value="">Semua Brand</option>
                    <?php foreach ($options['brands'] as $b): ?>
                        <option value="<?= e($b) ?>" <?= $filters['brand'] === $b ? 'selected' : '' ?>>
                            <?= e($b) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filter-flavor">Varian Rasa</label>
                <select id="filter-flavor" name="flavor" class="form-control">
                    <option value="">Semua Rasa</option>
                    <?php foreach ($options['flavors'] as $fl): ?>
                        <option value="<?= e($fl) ?>" <?= $filters['flavor'] === $fl ? 'selected' : '' ?>>
                            <?= e($fl) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filter-country">Negara</label>
                <select id="filter-country" name="country" class="form-control">
                    <option value="">Semua Negara</option>
                    <?php foreach ($options['countries'] as $c): ?>
                        <option value="<?= e($c) ?>" <?= $filters['country'] === $c ? 'selected' : '' ?>>
                            <?= e($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <?= svg_icon('search', 15) ?> Saring
                </button>
                <?php if ($hasActiveFilter): ?>
                    <a href="<?= e(base_url('index.php')) ?>" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <?php if (empty($noodles)): ?>
        <div class="empty-state">
            <h2>Belum Ada Mie yang Cocok</h2>
            <p>Tidak ditemukan varian mie instan dengan kriteria pencarian atau filter tersebut.</p>
            <?php if ($hasActiveFilter): ?>
                <a href="<?= e(base_url('index.php')) ?>" class="btn btn-primary">Tampilkan Semua Mie</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="catalog-grid">
            <?php foreach ($noodles as $noodle): ?>
                <?php
                $raterCount = (int) $noodle['rater_count'];
                $avgScore = $raterCount > 0 ? (float) $noodle['avg_score'] : null;
                $tierLetter = ($avgScore !== null && $raterCount >= $minRaters) ? score_to_tier($avgScore) : null;
                ?>
                <article class="noodle-card">
                    <a href="<?= e(base_url('noodle.php?id=' . (int) $noodle['id'])) ?>" class="noodle-card-media" aria-label="Buka detail <?= e($noodle['name']) ?>">
                        <?= render_noodle_visual($noodle, 'pack-visual-md') ?>
                        <div class="card-tier-corner">
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
                    </a>

                    <div class="noodle-card-body">
                        <div class="noodle-card-meta">
                            <span><?= e($noodle['brand']) ?> · <?= e($noodle['origin_country']) ?></span>
                            <span class="price-tag"><?= e(format_rupiah((int) $noodle['price'])) ?></span>
                        </div>

                        <h2 class="noodle-card-title">
                            <a href="<?= e(base_url('noodle.php?id=' . (int) $noodle['id'])) ?>">
                                <?= e($noodle['name']) ?>
                            </a>
                        </h2>

                        <p class="noodle-card-flavor"><?= e($noodle['flavor']) ?></p>

                        <dl class="aspect-inline-grid" aria-label="Rata-rata 4 Aspek">
                            <div class="aspect-inline-item">
                                <dt>RAS</dt>
                                <dd><?= $raterCount > 0 ? e(format_score($noodle['avg_taste'], 1)) : '–' ?></dd>
                            </div>
                            <div class="aspect-inline-item">
                                <dt>TEK</dt>
                                <dd><?= $raterCount > 0 ? e(format_score($noodle['avg_texture'], 1)) : '–' ?></dd>
                            </div>
                            <div class="aspect-inline-item">
                                <dt>POR</dt>
                                <dd><?= $raterCount > 0 ? e(format_score($noodle['avg_portion'], 1)) : '–' ?></dd>
                            </div>
                            <div class="aspect-inline-item">
                                <dt>HRG</dt>
                                <dd><?= $raterCount > 0 ? e(format_score($noodle['avg_value'], 1)) : '–' ?></dd>
                            </div>
                        </dl>

                        <div class="noodle-card-footer">
                            <?php if ($raterCount > 0 && $avgScore !== null): ?>
                                <span class="score-pill">
                                    <?= svg_icon('star', 14) ?>
                                    <strong><?= e(format_score($avgScore, 2)) ?></strong>
                                    <span class="score-max">/ 5.00</span>
                                </span>
                                <span class="rater-count-label tabular"><?= $raterCount ?> penilai</span>
                            <?php else: ?>
                                <span class="score-pill no-rating">Belum ada rating</span>
                                <a href="<?= e(base_url('noodle.php?id=' . (int) $noodle['id'])) ?>" class="card-action-link">Nilai pertama</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
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
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
