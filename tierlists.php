<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = require_login();
$pdo = db();

$stmt = $pdo->prepare('
    SELECT
        t.*,
        COUNT(ti.id) AS item_count
    FROM tierlists t
    LEFT JOIN tierlist_items ti ON ti.tierlist_id = t.id
    WHERE t.user_id = :uid
    GROUP BY t.id
    ORDER BY t.updated_at DESC, t.id DESC
');
$stmt->execute([':uid' => (int) $user['id']]);
$tierlists = $stmt->fetchAll();

$totalCount = count($tierlists);
$canCreateMore = $totalCount < MAX_TIERLISTS;

$pageTitle = 'Tierlist Saya';
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container section-block">
    <section class="page-head">
        <div class="page-head-copy">
            <h1>Tierlist Pribadi Saya</h1>
            <p>Susun hingga <?= (int) MAX_TIERLISTS ?> tierlist dengan tema berbeda seperti Mie Goreng, Mie Pedas Korea, atau Favorit Anak Kos.</p>
        </div>
        <div class="page-head-meta tabular">
            <span>Kuota <strong id="js-quota-count"><?= $totalCount ?> / <?= (int) MAX_TIERLISTS ?></strong></span>
        </div>
    </section>

    <div id="tierlists-feedback" class="flash-stack" style="margin-bottom:1.25rem;display:none;" role="status" aria-live="polite"></div>

    <!-- Form Buat Tierlist Baru -->
    <section class="filter-toolbar" aria-label="Buat Tierlist Baru">
        <?php if (!$canCreateMore): ?>
            <p class="limit-warning">
                Anda telah mencapai batas maksimal <?= (int) MAX_TIERLISTS ?> tierlist. Hapus salah satu tierlist di bawah jika ingin membuat yang baru.
            </p>
        <?php else: ?>
            <form id="js-create-tierlist-form" class="filter-grid filter-grid-create">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="new-tierlist-title">Judul Tierlist Baru *</label>
                    <input
                        type="text"
                        id="new-tierlist-title"
                        name="title"
                        class="form-control"
                        placeholder="Contoh: Tierlist Mie Kuah Terbaik"
                        maxlength="100"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="new-tierlist-category">Kategori / Tema (Opsional)</label>
                    <input
                        type="text"
                        id="new-tierlist-category"
                        name="category"
                        class="form-control"
                        placeholder="Contoh: Mie Kuah"
                        maxlength="50"
                    >
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary" id="btn-create-tierlist">
                        <?= svg_icon('plus', 16) ?> Buat &amp; Susun
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <?php if (empty($tierlists)): ?>
        <div class="empty-state">
            <h2>Belum Ada Tierlist Pribadi</h2>
            <p>Gunakan formulir di atas untuk membuat tierlist pertama Anda dan mulai mengelompokkan mie ke baris S–F.</p>
        </div>
    <?php else: ?>
        <div class="catalog-grid" id="js-tierlists-grid">
            <?php foreach ($tierlists as $tl): ?>
                <article class="panel-card tierlist-summary-card" data-tierlist-card="<?= (int) $tl['id'] ?>">
                    <div>
                        <div class="noodle-card-meta">
                            <span><?= e($tl['category'] ?: 'Semua Varian') ?></span>
                            <span class="tabular"><?= e(substr((string) $tl['updated_at'], 0, 10)) ?></span>
                        </div>

                        <h2 class="tierlist-card-title">
                            <a href="<?= e(base_url('tierlist.php?id=' . (int) $tl['id'])) ?>" class="js-tierlist-title-link">
                                <?= e($tl['title']) ?>
                            </a>
                        </h2>
                        <p class="muted-copy">
                            <strong class="tabular"><?= (int) $tl['item_count'] ?></strong> mie dikelompokkan
                        </p>

                        <!-- Form Ubah Judul Inline -->
                        <form class="js-rename-tierlist-form rename-inline-form" data-tierlist-id="<?= (int) $tl['id'] ?>">
                            <label for="rename-input-<?= (int) $tl['id'] ?>" class="skip-link">Ubah judul tierlist</label>
                            <input
                                type="text"
                                id="rename-input-<?= (int) $tl['id'] ?>"
                                name="title"
                                class="form-control form-control-sm"
                                value="<?= e($tl['title']) ?>"
                                maxlength="100"
                                required
                            >
                            <button type="submit" class="btn btn-secondary btn-sm">Ubah</button>
                        </form>
                    </div>

                    <div class="tierlist-card-actions">
                        <a href="<?= e(base_url('tierlist.php?id=' . (int) $tl['id'])) ?>" class="btn btn-primary btn-sm">
                            <?= svg_icon('layers', 14) ?> Buka Papan
                        </a>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm js-delete-tierlist-btn"
                            data-tierlist-id="<?= (int) $tl['id'] ?>"
                            data-tierlist-title="<?= e($tl['title']) ?>"
                        >
                            <?= svg_icon('trash', 14) ?> Hapus
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script src="<?= e(base_url('assets/js/tierlist.js')) ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
