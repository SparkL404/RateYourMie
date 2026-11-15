<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$admin = require_admin();
$pdo = db();

// Aksi hapus ulasan (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $deleteId = (int) ($_POST['delete_id'] ?? 0);

    if ($deleteId > 0) {
        $stmtDel = $pdo->prepare('DELETE FROM ratings WHERE id = :id');
        $stmtDel->execute([':id' => $deleteId]);
        flash('success', 'Data rating dan ulasan berhasil dihapus.');
    }
    redirect('admin/reviews.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$whereSql = '';
$params = [];
if ($q !== '') {
    $whereSql = 'WHERE (r.review LIKE :q1 OR u.username LIKE :q2 OR n.name LIKE :q3)';
    $params[':q1'] = '%' . $q . '%';
    $params[':q2'] = '%' . $q . '%';
    $params[':q3'] = '%' . $q . '%';
}

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM ratings r
    INNER JOIN users u ON u.id = r.user_id
    INNER JOIN noodles n ON n.id = r.noodle_id
    $whereSql
");
foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v, PDO::PARAM_STR);
}
$countStmt->execute();
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT
        r.*,
        ((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS final_score,
        u.username,
        n.name AS noodle_name
    FROM ratings r
    INNER JOIN users u ON u.id = r.user_id
    INNER JOIN noodles n ON n.id = r.noodle_id
    $whereSql
    ORDER BY r.created_at DESC, r.id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

$pageTitle = 'Moderasi Ulasan Pengguna';
include __DIR__ . '/../includes/header.php';
?>

<main id="main-content" class="container section-block">
    <section class="page-head">
        <div class="page-head-copy">
            <h1>Moderasi Rating &amp; Ulasan</h1>
            <p>Pantau dan hapus ulasan yang mengandung spam atau melanggar ketentuan komunitas.</p>
        </div>
    </section>

    <nav class="admin-subnav" aria-label="Menu Modul Admin">
        <a href="<?= e(base_url('admin/index.php')) ?>" class="subnav-link">Dashboard</a>
        <a href="<?= e(base_url('admin/noodles.php')) ?>" class="subnav-link">Katalog Mie</a>
        <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="subnav-link">Tambah Mie</a>
        <a href="<?= e(base_url('admin/reviews.php')) ?>" class="subnav-link is-active">Moderasi Ulasan</a>
    </nav>

    <section class="filter-toolbar" aria-label="Pencarian Ulasan">
        <form action="<?= e(base_url('admin/reviews.php')) ?>" method="get" class="filter-grid filter-grid-search">
            <div class="form-group">
                <label for="review-q">Cari Ulasan, Username, atau Nama Mie</label>
                <input
                    type="search"
                    id="review-q"
                    name="q"
                    class="form-control"
                    value="<?= e($q) ?>"
                    placeholder="Ketik kata kunci..."
                >
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><?= svg_icon('search', 15) ?> Cari</button>
                <?php if ($q !== ''): ?>
                    <a href="<?= e(base_url('admin/reviews.php')) ?>" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <?php if (empty($reviews)): ?>
        <div class="empty-state">
            <h2>Tidak Ada Ulasan</h2>
            <p>Belum ada data ulasan yang sesuai dengan filter pencarian.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pengguna</th>
                        <th>Mie Instan</th>
                        <th>R / T / P / H</th>
                        <th>Skor Akhir</th>
                        <th>Ulasan</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $rv): ?>
                        <tr>
                            <td>#<?= (int) $rv['id'] ?></td>
                            <td class="cell-sans"><strong><?= e($rv['username']) ?></strong></td>
                            <td class="cell-sans">
                                <a href="<?= e(base_url('noodle.php?id=' . (int) $rv['noodle_id'])) ?>"><?= e($rv['noodle_name']) ?></a>
                            </td>
                            <td>
                                <?= (int) $rv['taste'] ?> / <?= (int) $rv['texture'] ?> / <?= (int) $rv['portion'] ?> / <?= (int) $rv['value'] ?>
                            </td>
                            <td><strong><?= e(format_score($rv['final_score'])) ?></strong></td>
                            <td class="cell-sans cell-review">
                                <?= $rv['review'] !== null && $rv['review'] !== '' ? e($rv['review']) : '<em class="muted-italic">Tanpa teks ulasan</em>' ?>
                            </td>
                            <td><?= e(substr((string) $rv['created_at'], 0, 16)) ?></td>
                            <td>
                                <form
                                    action="<?= e(base_url('admin/reviews.php')) ?>"
                                    method="post"
                                    class="js-delete-review-form"
                                    data-review-user="<?= e($rv['username']) ?>"
                                >
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="delete_id" value="<?= (int) $rv['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <?= svg_icon('trash', 14) ?> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination-bar" aria-label="Navigasi Halaman Ulasan">
                <span>Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> · <?= $total ?> ulasan</span>
                <div class="pagination-links">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php
                        $query = http_build_query(array_filter([
                            'q'    => $q,
                            'page' => $p,
                        ], static fn($v) => $v !== '' && $v !== null));
                        ?>
                        <a
                            href="<?= e(base_url('admin/reviews.php?' . $query)) ?>"
                            class="page-btn <?= $p === $page ? 'is-current' : '' ?>"
                        ><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('.js-delete-review-form').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
        var user = form.getAttribute('data-review-user') || 'pengguna ini';
        if (!window.confirm('Hapus ulasan dan rating dari "' + user + '" secara permanen?')) {
            ev.preventDefault();
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
