<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tier.php';

$admin = require_admin();
$pdo = db();

// Statistik ringkas (FA-05)
$totalNoodles = (int) $pdo->query('SELECT COUNT(*) FROM noodles')->fetchColumn();
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalRatings = (int) $pdo->query('SELECT COUNT(*) FROM ratings')->fetchColumn();
$totalTierlists = (int) $pdo->query('SELECT COUNT(*) FROM tierlists')->fetchColumn();

// 6 mie terbaru
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

// 6 ulasan terbaru
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

<main id="main-content" class="container section-block">
    <section class="page-head">
        <div class="page-head-copy">
            <h1>Dashboard Pengelola</h1>
            <p>Ringkasan katalog mie instan, pengguna terdaftar, dan moderasi ulasan komunitas.</p>
        </div>
        <div class="page-head-actions">
            <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="btn btn-primary btn-sm">
                <?= svg_icon('plus', 15) ?> Tambah Mie Baru
            </a>
        </div>
    </section>

    <nav class="admin-subnav" aria-label="Menu Modul Admin">
        <a href="<?= e(base_url('admin/index.php')) ?>" class="subnav-link is-active">Dashboard</a>
        <a href="<?= e(base_url('admin/noodles.php')) ?>" class="subnav-link">Katalog Mie</a>
        <a href="<?= e(base_url('admin/noodle_form.php')) ?>" class="subnav-link">Tambah Mie</a>
        <a href="<?= e(base_url('admin/reviews.php')) ?>" class="subnav-link">Moderasi Ulasan</a>
    </nav>

    <section class="stats-strip" aria-label="Statistik Utama">
        <div class="stat-strip-cell">
            <span class="stat-card-label">Varian Mie</span>
            <strong class="stat-card-value tabular"><?= $totalNoodles ?></strong>
            <a href="<?= e(base_url('admin/noodles.php')) ?>" class="stat-strip-link">Kelola katalog</a>
        </div>
        <div class="stat-strip-cell">
            <span class="stat-card-label">Pengguna</span>
            <strong class="stat-card-value tabular"><?= $totalUsers ?></strong>
            <span class="stat-strip-sub">Admin &amp; User</span>
        </div>
        <div class="stat-strip-cell">
            <span class="stat-card-label">Rating &amp; Ulasan</span>
            <strong class="stat-card-value tabular"><?= $totalRatings ?></strong>
            <a href="<?= e(base_url('admin/reviews.php')) ?>" class="stat-strip-link">Moderasi ulasan</a>
        </div>
        <div class="stat-strip-cell">
            <span class="stat-card-label">Tierlist Tersimpan</span>
            <strong class="stat-card-value tabular"><?= $totalTierlists ?></strong>
            <a href="<?= e(base_url('community.php')) ?>" class="stat-strip-link">Tierlist komunitas</a>
        </div>
    </section>

    <section class="admin-section">
        <h2>Katalog Mie Terbaru</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Mie</th>
                        <th>Brand &amp; Rasa</th>
                        <th>Negara</th>
                        <th>Harga</th>
                        <th>Rata-rata Skor</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentNoodles as $n): ?>
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
                                <a href="<?= e(base_url('admin/noodle_form.php?id=' . (int) $n['id'])) ?>" class="btn btn-secondary btn-sm">
                                    <?= svg_icon('edit', 14) ?> Ubah
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2>Ulasan Pengguna Terbaru</h2>
        <?php if (empty($recentReviews)): ?>
            <div class="empty-state">
                <p>Belum ada ulasan yang masuk.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Mie Instan</th>
                            <th>Skor Akhir</th>
                            <th>Isi Ulasan</th>
                            <th>Waktu</th>
                            <th>Moderasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReviews as $rv): ?>
                            <tr>
                                <td class="cell-sans"><strong><?= e($rv['username']) ?></strong></td>
                                <td class="cell-sans">
                                    <a href="<?= e(base_url('noodle.php?id=' . (int) $rv['noodle_id'])) ?>"><?= e($rv['noodle_name']) ?></a>
                                </td>
                                <td><strong><?= e(format_score($rv['final_score'])) ?></strong> / 5.00</td>
                                <td class="cell-sans cell-review"><?= $rv['review'] !== null && $rv['review'] !== '' ? e($rv['review']) : '<em class="muted-italic">Tanpa teks ulasan</em>' ?></td>
                                <td><?= e(substr((string) $rv['created_at'], 0, 16)) ?></td>
                                <td>
                                    <form action="<?= e(base_url('admin/reviews.php')) ?>" method="post" class="js-confirm-delete" data-confirm-msg="Hapus ulasan ini secara permanen?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delete_id" value="<?= (int) $rv['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><?= svg_icon('trash', 14) ?> Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
        var msg = form.getAttribute('data-confirm-msg') || 'Yakin ingin menghapus data ini?';
        if (!window.confirm(msg)) {
            ev.preventDefault();
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
