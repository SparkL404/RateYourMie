<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$admin = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $deleteId = (int) ($_POST['delete_id'] ?? 0);

    if ($deleteId > 0) {
        $stmtDel = $pdo->prepare('DELETE FROM ratings WHERE id = :id');
        $stmtDel->execute([':id' => $deleteId]);
        if ($stmtDel->rowCount() > 0) {
            flash('success', 'Data rating dan ulasan berhasil dihapus.');
        } else {
            flash('warning', 'Data ulasan tidak ditemukan atau sudah dihapus.');
        }
    }

    if (($_POST['return_to'] ?? '') === 'index') {
        redirect('admin/index.php');
    }

    $returnQuery = http_build_query(array_filter([
        'q'    => trim((string) ($_POST['q'] ?? '')),
        'page' => max(1, (int) ($_POST['page'] ?? 1)) > 1 ? (int) $_POST['page'] : null,
    ], static fn($v) => $v !== '' && $v !== null));
    redirect('admin/reviews.php' . ($returnQuery !== '' ? '?' . $returnQuery : ''));
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
            <p>Pantau ulasan komunitas dan hapus ulasan yang mengandung spam dengan konfirmasi dua langkah.</p>
        </div>
        <div class="page-head-stats">
            <div class="stat-pill-box">
                <span class="stat-pill-label">Total Ulasan</span>
                <strong class="tabular"><?= $total ?></strong>
            </div>
        </div>
    </section>

    <section class="filter-toolbar" aria-label="Pencarian Ulasan">
        <form action="<?= e(base_url('admin/reviews.php')) ?>" method="get" class="filter-grid filter-grid-search">
            <div class="form-group">
                <label for="review-q">Cari Ulasan, Username, atau Nama Mie</label>
                <div class="search-field-wrap">
                    <span class="search-field-icon"><?= svg_icon('search', 15) ?></span>
                    <input
                        type="search"
                        id="review-q"
                        name="q"
                        class="form-control has-search-icon"
                        value="<?= e($q) ?>"
                        placeholder="Ketik username, nama mie, atau isi ulasan..."
                    >
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><?= svg_icon('search', 15) ?> Cari</button>
                <?php if ($q !== ''): ?>
                    <a href="<?= e(base_url('admin/reviews.php')) ?>" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="quick-chips-bar" role="toolbar" aria-label="Filter Cepat Ulasan">
            <button type="button" class="chip-pill is-active js-admin-rfilter" data-filter="all">Semua</button>
            <button type="button" class="chip-pill js-admin-rfilter" data-filter="low">Skor &le; 3.50</button>
            <button type="button" class="chip-pill js-admin-rfilter" data-filter="empty">Tanpa Teks Ulasan</button>
        </div>
    </section>

    <?php if (empty($reviews)): ?>
        <div class="empty-state">
            <h2>Tidak Ada Ulasan</h2>
            <p>Belum ada data ulasan yang sesuai dengan filter pencarian.</p>
        </div>
    <?php else: ?>
        <div class="moderation-feed" id="js-moderation-feed">
            <?php foreach ($reviews as $rv): ?>
                <?php
                $fScore = (float) $rv['final_score'];
                $hasText = ($rv['review'] !== null && trim((string) $rv['review']) !== '');
                ?>
                <article
                    class="panel-card moderation-card js-moderation-item"
                    data-low="<?= $fScore <= 3.50 ? '1' : '0' ?>"
                    data-empty="<?= !$hasText ? '1' : '0' ?>"
                    data-search="<?= e(mb_strtolower($rv['username'] . ' ' . $rv['noodle_name'] . ' ' . ($rv['review'] ?? ''), 'UTF-8')) ?>"
                >
                    <div class="moderation-main">
                        <div class="moderation-head">
                            <span class="tabular muted-inline">#<?= (int) $rv['id'] ?></span>
                            <strong>@<?= e($rv['username']) ?></strong>
                            <span class="muted-inline">menilai</span>
                            <a href="<?= e(base_url('noodle.php?id=' . (int) $rv['noodle_id'])) ?>" class="table-item-title"><?= e($rv['noodle_name']) ?></a>
                            <span class="tabular muted-inline">· <?= e(substr((string) $rv['created_at'], 0, 16)) ?></span>
                        </div>

                        <p class="moderation-body">
                            <?= $hasText ? e($rv['review']) : '<em class="muted-italic">Tanpa teks ulasan</em>' ?>
                        </p>

                        <div class="table-aspects-pills tabular">
                            <span class="score-pill">
                                <?= svg_icon('star', 13) ?>
                                <strong><?= e(format_score($fScore, 2)) ?></strong>
                            </span>
                            <span class="aspect-chip">RAS <?= (int) $rv['taste'] ?></span>
                            <span class="aspect-chip">TEK <?= (int) $rv['texture'] ?></span>
                            <span class="aspect-chip">POR <?= (int) $rv['portion'] ?></span>
                            <span class="aspect-chip">HRG <?= (int) $rv['value'] ?></span>
                        </div>
                    </div>

                    <div class="moderation-actions">
                        <form
                            action="<?= e(base_url('admin/reviews.php')) ?>"
                            method="post"
                            class="js-inline-confirm-form"
                        >
                            <?= csrf_field() ?>
                            <input type="hidden" name="delete_id" value="<?= (int) $rv['id'] ?>">
                            <?php if ($q !== ''): ?>
                                <input type="hidden" name="q" value="<?= e($q) ?>">
                            <?php endif; ?>
                            <?php if ($page > 1): ?>
                                <input type="hidden" name="page" value="<?= $page ?>">
                            <?php endif; ?>
                            <button type="button" class="btn btn-danger btn-sm js-confirm-trigger">
                                <?= svg_icon('trash', 14) ?> Hapus Ulasan
                            </button>
                            <span class="inline-confirm-group js-confirm-step" hidden>
                                <button type="submit" class="btn btn-primary btn-sm">Konfirmasi Hapus</button>
                                <button type="button" class="btn btn-secondary btn-sm js-confirm-cancel">Batal</button>
                            </span>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
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
(function () {
    var searchInput = document.getElementById('review-q');
    var chips = document.querySelectorAll('.js-admin-rfilter');
    var items = document.querySelectorAll('.js-moderation-item');
    var currentFilter = 'all';

    function filterReviews() {
        var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        items.forEach(function (item) {
            var hay = item.getAttribute('data-search') || '';
            var isLow = item.getAttribute('data-low') === '1';
            var isEmpty = item.getAttribute('data-empty') === '1';
            var matchQ = !q || hay.indexOf(q) !== -1;
            var matchChip = (currentFilter === 'all') ||
                (currentFilter === 'low' && isLow) ||
                (currentFilter === 'empty' && isEmpty);
            item.hidden = !(matchQ && matchChip);
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterReviews);
    }

    chips.forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentFilter = btn.getAttribute('data-filter') || 'all';
            chips.forEach(function (c) { c.classList.toggle('is-active', c === btn); });
            filterReviews();
        });
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
