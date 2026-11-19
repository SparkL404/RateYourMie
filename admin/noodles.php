<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/noodle_repo.php';

$admin = require_admin();

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$result = get_catalog_noodles(['q' => $q], $page, $perPage);
$noodles = $result['items'];
$minRaters = (int) (get_app_settings()['min_raters'] ?? MIN_RATERS);

$pageTitle = 'Kelola Data Mie (CRUD)';
include __DIR__ . '/../includes/header.php';
?>

<main id="main-content" class="container section-block">
    <section class="page-head">
        <div class="page-head-copy">
            <h1>Kelola Katalog Mie</h1>
            <p>Tambah, ubah, atau hapus data mie instan beserta foto kemasannya dalam satu tempat.</p>
        </div>
        <div class="page-head-actions">
            <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="btn btn-primary btn-sm">
                <?= svg_icon('plus', 15) ?> Tambah Mie Baru
            </a>
        </div>
    </section>

    <section class="filter-toolbar" aria-label="Pencarian Mie Admin">
        <form action="<?= e(base_url('admin/noodles.php')) ?>" method="get" class="filter-grid filter-grid-search">
            <div class="form-group">
                <label for="admin-search-q">Cari Nama Mie</label>
                <div class="search-field-wrap">
                    <span class="search-field-icon"><?= svg_icon('search', 15) ?></span>
                    <input
                        type="search"
                        id="admin-search-q"
                        name="q"
                        class="form-control has-search-icon"
                        value="<?= e($q) ?>"
                        placeholder="Ketik nama mie, brand, atau negara..."
                    >
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><?= svg_icon('search', 15) ?> Cari</button>
                <?php if ($q !== ''): ?>
                    <a href="<?= e(base_url('admin/noodles.php')) ?>" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="quick-chips-bar" role="toolbar" aria-label="Filter Status Mie">
            <button type="button" class="chip-pill is-active js-admin-nfilter" data-filter="all">Semua</button>
            <button type="button" class="chip-pill js-admin-nfilter" data-filter="qualified">Masuk Tier (&ge; <?= $minRaters ?> penilai)</button>
            <button type="button" class="chip-pill js-admin-nfilter" data-filter="pending">Butuh Penilai</button>
        </div>
    </section>

    <?php if (empty($noodles)): ?>
        <div class="empty-state">
            <h2>Belum Ada Data Mie</h2>
            <p>Tidak ditemukan mie instan yang sesuai dengan kata kunci pencarian.</p>
            <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="btn btn-primary">Tambah Mie Baru</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" id="js-admin-noodles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Varian Mie</th>
                        <th>Negara</th>
                        <th>Harga</th>
                        <th>Skor &amp; Tier</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($noodles as $n): ?>
                        <?php
                        $rc = (int) $n['rater_count'];
                        $avg = $rc > 0 ? (float) $n['avg_score'] : null;
                        $tl = ($avg !== null && $rc >= $minRaters) ? score_to_tier($avg) : null;
                        $isQual = $rc >= $minRaters ? '1' : '0';
                        ?>
                        <tr
                            class="js-admin-noodle-row"
                            data-qualified="<?= $isQual ?>"
                            data-search="<?= e(mb_strtolower($n['name'] . ' ' . $n['brand'] . ' ' . $n['flavor'] . ' ' . $n['origin_country'], 'UTF-8')) ?>"
                        >
                            <td>#<?= (int) $n['id'] ?></td>
                            <td class="cell-sans">
                                <div class="table-product-cell">
                                    <?= render_noodle_swatch($n, 'swatch-sm') ?>
                                    <div>
                                        <a href="<?= e(base_url('noodle.php?id=' . (int) $n['id'])) ?>" class="table-item-title"><?= e($n['name']) ?></a>
                                        <div class="table-item-sub"><?= e($n['brand']) ?> · <?= e($n['flavor']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-sans"><?= e($n['origin_country']) ?></td>
                            <td><?= e(format_rupiah((int) $n['price'])) ?></td>
                            <td>
                                <?php if ($tl !== null): ?>
                                    <span class="tier-badge tier-badge-xs <?= e(tier_meta($tl)['class']) ?>"><?= e($tl) ?></span>
                                    <strong><?= e(format_score($avg)) ?></strong>
                                    <span class="table-sub-inline">(<?= $rc ?>)</span>
                                <?php elseif ($rc > 0): ?>
                                    <span class="tier-badge tier-unranked"><?= $rc ?>/<?= $minRaters ?></span>
                                    <strong><?= e(format_score($avg)) ?></strong>
                                <?php else: ?>
                                    <span class="cell-sans muted-inline">Belum ada rating</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?= e(base_url('admin/noodle_form.php?id=' . (int) $n['id'])) ?>" class="btn btn-secondary btn-sm">
                                        <?= svg_icon('edit', 14) ?> Ubah
                                    </a>
                                    <form
                                        action="<?= e(base_url('admin/noodle_delete.php')) ?>"
                                        method="post"
                                        class="js-inline-confirm-form"
                                    >
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                                        <button type="button" class="btn btn-danger btn-sm js-confirm-trigger">
                                            <?= svg_icon('trash', 14) ?> Hapus
                                        </button>
                                        <span class="inline-confirm-group js-confirm-step" hidden>
                                            <button type="submit" class="btn btn-primary btn-sm">Yakin</button>
                                            <button type="button" class="btn btn-secondary btn-sm js-confirm-cancel">Batal</button>
                                        </span>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['total_pages'] > 1): ?>
            <nav class="pagination-bar" aria-label="Navigasi Halaman Admin">
                <span>Halaman <strong><?= (int) $result['page'] ?></strong> dari <strong><?= (int) $result['total_pages'] ?></strong> · <?= (int) $result['total'] ?> mie</span>
                <div class="pagination-links">
                    <?php for ($p = 1; $p <= $result['total_pages']; $p++): ?>
                        <?php
                        $query = http_build_query(array_filter([
                            'q'    => $q,
                            'page' => $p,
                        ], static fn($v) => $v !== '' && $v !== null));
                        ?>
                        <a
                            href="<?= e(base_url('admin/noodles.php?' . $query)) ?>"
                            class="page-btn <?= $p === $result['page'] ? 'is-current' : '' ?>"
                        ><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<script>
(function () {
    var searchInput = document.getElementById('admin-search-q');
    var chips = document.querySelectorAll('.js-admin-nfilter');
    var rows = document.querySelectorAll('.js-admin-noodle-row');
    var currentFilter = 'all';

    function filterRows() {
        var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        rows.forEach(function (row) {
            var hay = row.getAttribute('data-search') || '';
            var isQual = row.getAttribute('data-qualified') === '1';
            var matchQ = !q || hay.indexOf(q) !== -1;
            var matchChip = (currentFilter === 'all') ||
                (currentFilter === 'qualified' && isQual) ||
                (currentFilter === 'pending' && !isQual);
            row.hidden = !(matchQ && matchChip);
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterRows);
    }

    chips.forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentFilter = btn.getAttribute('data-filter') || 'all';
            chips.forEach(function (c) { c.classList.toggle('is-active', c === btn); });
            filterRows();
        });
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
