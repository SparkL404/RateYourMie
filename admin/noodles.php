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

$pageTitle = 'Kelola Data Mie (CRUD)';
include __DIR__ . '/../includes/header.php';
?>

<main id="main-content" class="container section-block">
    <section class="page-head">
        <div class="page-head-copy">
            <h1>Kelola Katalog Mie</h1>
            <p>Tambah, ubah, atau hapus data mie instan beserta foto kemasannya.</p>
        </div>
        <div class="page-head-actions">
            <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="btn btn-primary btn-sm">
                <?= svg_icon('plus', 15) ?> Tambah Mie Baru
            </a>
        </div>
    </section>

    <nav class="admin-subnav" aria-label="Menu Modul Admin">
        <a href="<?= e(base_url('admin/index.php')) ?>" class="subnav-link">Dashboard</a>
        <a href="<?= e(base_url('admin/noodles.php')) ?>" class="subnav-link is-active">Katalog Mie</a>
        <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="subnav-link">Tambah Mie</a>
        <a href="<?= e(base_url('admin/reviews.php')) ?>" class="subnav-link">Moderasi Ulasan</a>
    </nav>

    <section class="filter-toolbar" aria-label="Pencarian Mie Admin">
        <form action="<?= e(base_url('admin/noodles.php')) ?>" method="get" class="filter-grid filter-grid-search">
            <div class="form-group">
                <label for="admin-search-q">Cari Nama Mie</label>
                <input
                    type="search"
                    id="admin-search-q"
                    name="q"
                    class="form-control"
                    value="<?= e($q) ?>"
                    placeholder="Ketik nama mie instan..."
                >
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><?= svg_icon('search', 15) ?> Cari</button>
                <?php if ($q !== ''): ?>
                    <a href="<?= e(base_url('admin/noodles.php')) ?>" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <?php if (empty($noodles)): ?>
        <div class="empty-state">
            <h2>Belum Ada Data Mie</h2>
            <p>Tidak ditemukan mie instan yang sesuai dengan kata kunci pencarian.</p>
            <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="btn btn-primary">Tambah Mie Baru</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Mie</th>
                        <th>Brand &amp; Rasa</th>
                        <th>Negara</th>
                        <th>Harga</th>
                        <th>Skor Rata-rata</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($noodles as $n): ?>
                        <tr>
                            <td>#<?= (int) $n['id'] ?></td>
                            <td class="cell-sans">
                                <a href="<?= e(base_url('noodle.php?id=' . (int) $n['id'])) ?>" class="table-item-title"><?= e($n['name']) ?></a>
                            </td>
                            <td class="cell-sans"><?= e($n['brand']) ?> · <?= e($n['flavor']) ?></td>
                            <td class="cell-sans"><?= e($n['origin_country']) ?></td>
                            <td><?= e(format_rupiah((int) $n['price'])) ?></td>
                            <td>
                                <?php if ((int) $n['rater_count'] > 0): ?>
                                    <strong><?= e(format_score($n['avg_score'])) ?></strong>
                                    <span class="table-sub-inline">(<?= (int) $n['rater_count'] ?>)</span>
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
                                        class="js-delete-noodle-form"
                                        data-noodle-name="<?= e($n['name']) ?>"
                                    >
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <?= svg_icon('trash', 14) ?> Hapus
                                        </button>
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
document.querySelectorAll('.js-delete-noodle-form').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
        var name = form.getAttribute('data-noodle-name') || 'mie ini';
        if (!window.confirm('Yakin ingin menghapus "' + name + '" beserta seluruh rating dan posisinya di tierlist?')) {
            ev.preventDefault();
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
