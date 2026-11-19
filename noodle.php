<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/noodle_repo.php';

$id = (int) ($_GET['id'] ?? 0);
$noodle = $id > 0 ? get_noodle_by_id($id) : null;

if (!$noodle) {
    http_response_code(404);
    $pageTitle = 'Mie Tidak Ditemukan';
    include __DIR__ . '/includes/header.php';
    echo '<main id="main-content" class="container section-block"><div class="empty-state">'
        . '<h1>404 — Varian Mie Tidak Ditemukan</h1>'
        . '<p>Data mie instan yang Anda cari tidak tersedia atau telah dihapus.</p>'
        . '<a href="' . e(base_url('index.php')) . '" class="btn btn-primary">Kembali ke Katalog</a>'
        . '</div></main>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();
$reviews = get_noodle_reviews($id);
$myRating = $user ? get_user_rating_for_noodle((int) $user['id'], $id) : null;

$raterCount = (int) $noodle['rater_count'];
$avgScore = $raterCount > 0 ? (float) $noodle['avg_score'] : null;
$minRaters = (int) (get_app_settings()['min_raters'] ?? MIN_RATERS);
$tierLetter = ($avgScore !== null && $raterCount >= $minRaters) ? score_to_tier($avgScore) : null;

$aspectsMeta = [
    'taste'   => ['label' => 'Rasa Bumbu & Kuah', 'code' => 'RAS', 'avg' => $noodle['avg_taste']],
    'texture' => ['label' => 'Tekstur & Kekenyalan Mie', 'code' => 'TEK', 'avg' => $noodle['avg_texture']],
    'portion' => ['label' => 'Porsi & Mengenyangkan', 'code' => 'POR', 'avg' => $noodle['avg_portion']],
    'value'   => ['label' => 'Kesesuaian Harga', 'code' => 'HRG', 'avg' => $noodle['avg_value']],
];

$pageTitle = $noodle['name'];
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container section-block">
    <div class="breadcrumb-bar">
        <a href="<?= e(base_url('index.php')) ?>" class="back-link">
            <?= svg_icon('arrow-left', 15) ?> Kembali ke Katalog
        </a>
    </div>

    <div class="detail-layout">
        <div class="detail-main">
            <article class="panel-card">
                <div class="detail-hero">
                    <div class="detail-visual-box">
                        <?= render_noodle_swatch($noodle, 'swatch-hero') ?>
                    </div>
                    <div class="detail-intro">
                        <div class="detail-meta-line">
                            <span><?= e($noodle['brand']) ?></span>
                            <span aria-hidden="true">·</span>
                            <span><?= e($noodle['flavor']) ?></span>
                            <span aria-hidden="true">·</span>
                            <span><?= e($noodle['origin_country']) ?></span>
                        </div>
                        <h1><?= e($noodle['name']) ?></h1>
                        <p class="detail-price price-tag tabular"><?= e(format_rupiah((int) $noodle['price'])) ?></p>
                        <p class="detail-description">
                            <?= $noodle['description'] !== null && $noodle['description'] !== '' ? nl2br(e($noodle['description'])) : 'Belum ada deskripsi tambahan untuk varian mie ini.' ?>
                        </p>

                        <div class="detail-metrics-row" id="detail-summary-box">
                            <?php if ($raterCount > 0 && $avgScore !== null): ?>
                                <div class="metric-item">
                                    <span class="metric-label">Skor Rata-rata</span>
                                    <strong class="metric-value tabular" id="js-avg-score"><?= e(format_score($avgScore, 2)) ?> <small>/ 5.00</small></strong>
                                </div>
                                <div class="metric-item">
                                    <span class="metric-label">Penilai</span>
                                    <strong class="metric-value tabular" id="js-rater-count"><?= $raterCount ?></strong>
                                </div>
                                <div class="metric-item">
                                    <span class="metric-label">Tier Komunitas</span>
                                    <?php if ($tierLetter !== null): ?>
                                        <?php $tm = tier_meta($tierLetter); ?>
                                        <span class="tier-inline">
                                            <span class="tier-badge tier-badge-sm <?= e($tm['class']) ?>"><?= e($tierLetter) ?></span>
                                            <span><?= e($tm['name']) ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="metric-note">Butuh <?= max(0, $minRaters - $raterCount) ?> penilai lagi</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="metric-item">
                                    <span class="metric-label">Status Penilaian</span>
                                    <strong class="metric-value" id="js-avg-score">Belum ada rating</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="detail-breakdown-section">
                    <h2>Rincian Skor 4 Aspek</h2>
                    <div class="aspect-breakdown-list" id="js-aspect-breakdown">
                        <?php foreach ($aspectsMeta as $key => $meta): ?>
                            <?php
                            $val = $raterCount > 0 ? (float) $meta['avg'] : 0.0;
                            $pct = min(100, max(0, ($val / 5.0) * 100));
                            $barTone = $val >= 4.4 ? 'is-high' : ($val > 0 && $val <= 2.5 ? 'is-low' : '');
                            ?>
                            <div class="aspect-row">
                                <span class="aspect-row-label"><?= e($meta['label']) ?></span>
                                <div class="aspect-bar-track" aria-hidden="true">
                                    <div class="aspect-bar-fill <?= $barTone ?>" id="js-bar-<?= e($key) ?>" style="width:<?= round($pct, 1) ?>%"></div>
                                </div>
                                <span class="aspect-num tabular" id="js-val-<?= e($key) ?>">
                                    <?= $raterCount > 0 ? e(format_score($val, 2)) : '–' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>

            <section class="reviews-section" aria-label="Daftar Ulasan Pengguna">
                <h2>Ulasan Komunitas (<?= count($reviews) ?>)</h2>
                <?php if (empty($reviews)): ?>
                    <div class="empty-state">
                        <p>Belum ada ulasan untuk mie ini. Jadilah penilai pertama.</p>
                    </div>
                <?php else: ?>
                    <div class="reviews-list" id="js-reviews-list">
                        <?php foreach ($reviews as $rv): ?>
                            <article class="review-card">
                                <div class="review-header">
                                    <div class="review-author-wrap">
                                        <strong class="review-author">@<?= e($rv['username']) ?></strong>
                                        <?php if ($user && (int) $user['id'] === (int) $rv['user_id']): ?>
                                            <span class="self-tag">Rating Anda</span>
                                        <?php endif; ?>
                                        <span class="review-date tabular"><?= e(substr((string) $rv['created_at'], 0, 10)) ?></span>
                                    </div>
                                    <span class="score-pill">
                                        <?= svg_icon('star', 14) ?>
                                        <strong><?= e(format_score($rv['final_score'], 2)) ?></strong>
                                    </span>
                                </div>
                                <div class="review-aspects-inline tabular">
                                    <span class="aspect-chip">RAS <?= (int) $rv['taste'] ?></span>
                                    <span class="aspect-chip">TEK <?= (int) $rv['texture'] ?></span>
                                    <span class="aspect-chip">POR <?= (int) $rv['portion'] ?></span>
                                    <span class="aspect-chip">HRG <?= (int) $rv['value'] ?></span>
                                </div>
                                <?php if ($rv['review'] !== null && trim((string) $rv['review']) !== ''): ?>
                                    <p class="review-body"><?= nl2br(e($rv['review'])) ?></p>
                                <?php else: ?>
                                    <p class="review-empty">Memberi skor tanpa catatan ulasan.</p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="panel-card detail-sidebar">
            <h2 class="sidebar-title"><?= $myRating ? 'Perbarui Rating Anda' : 'Beri Rating 4 Aspek' ?></h2>

            <?php if (!$user): ?>
                <p class="muted-copy">
                    Masuk ke akun Anda untuk memberikan penilaian 4 aspek dan menulis ulasan untuk <strong><?= e($noodle['name']) ?></strong>.
                </p>
                <div class="form-actions">
                    <a href="<?= e(base_url('login.php')) ?>" class="btn btn-primary">Masuk untuk Menilai</a>
                    <a href="<?= e(base_url('register.php')) ?>" class="btn btn-secondary">Daftar</a>
                </div>
            <?php else: ?>
                <p class="muted-copy">
                    Pilih nilai 1–5 untuk setiap aspek. Skor akhir dihitung otomatis dari rata-rata keempatnya.
                </p>

                <div id="rating-feedback-box" class="flash-stack" style="margin-bottom:1rem;display:none;" role="status" aria-live="polite"></div>

                <form id="rating-form" data-noodle-id="<?= (int) $noodle['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="noodle_id" value="<?= (int) $noodle['id'] ?>">

                    <div class="live-score-readout">
                        <span>Simulasi Skor Akhir</span>
                        <strong id="js-live-user-score" class="tabular">
                            <?php
                            if ($myRating) {
                                $initScore = ((int) $myRating['taste'] + (int) $myRating['texture'] + (int) $myRating['portion'] + (int) $myRating['value']) / 4;
                                echo e(format_score($initScore, 2)) . ' (Tier ' . e(score_to_tier($initScore)) . ')';
                            } else {
                                echo '4.00 (Tier A)';
                            }
                            ?>
                        </strong>
                    </div>

                    <?php
                    $formAspects = [
                        'taste'   => ['title' => 'Rasa (Bumbu & Aroma)', 'default' => (int) ($myRating['taste'] ?? 4)],
                        'texture' => ['title' => 'Tekstur (Kekenyalan Mie)', 'default' => (int) ($myRating['texture'] ?? 4)],
                        'portion' => ['title' => 'Porsi (Kenyang & Isi)', 'default' => (int) ($myRating['portion'] ?? 4)],
                        'value'   => ['title' => 'Harga (Kelayakan Nilai)', 'default' => (int) ($myRating['value'] ?? 4)],
                    ];
                    ?>

                    <?php foreach ($formAspects as $field => $fMeta): ?>
                        <fieldset class="rating-aspect-fieldset">
                            <div class="rating-aspect-head">
                                <legend><?= e($fMeta['title']) ?></legend>
                                <span class="tabular aspect-current" id="js-label-<?= e($field) ?>"><?= (int) $fMeta['default'] ?> / 5</span>
                            </div>
                            <div class="score-radio-group">
                                <?php for ($score = 1; $score <= 5; $score++): ?>
                                    <label class="score-radio-label">
                                        <input
                                            type="radio"
                                            name="<?= e($field) ?>"
                                            value="<?= $score ?>"
                                            <?= (int) $fMeta['default'] === $score ? 'checked' : '' ?>
                                            required
                                        >
                                        <span class="score-radio-box"><?= $score ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>

                    <div class="form-group" style="margin-bottom:1rem;">
                        <label for="review-text">Ulasan Singkat (Opsional, maks. 500 karakter)</label>
                        <textarea
                            id="review-text"
                            name="review"
                            class="form-control"
                            maxlength="500"
                            placeholder="Ceritakan kesan rasa bumbu, tekstur mie, atau tips penyajian..."
                        ><?= e($myRating['review'] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" id="btn-save-rating" class="btn btn-primary">
                            <?= svg_icon('check', 16) ?>
                            <span><?= $myRating ? 'Perbarui Rating' : 'Simpan Rating' ?></span>
                        </button>

                        <?php if ($myRating): ?>
                            <button type="button" id="btn-delete-rating-trigger" class="btn btn-danger">
                                <?= svg_icon('trash', 16) ?> Hapus
                            </button>
                            <span id="js-delete-rating-step" class="inline-confirm-group" hidden>
                                <button type="button" id="btn-delete-rating" class="btn btn-primary btn-sm">Yakin Hapus</button>
                                <button type="button" id="btn-delete-rating-cancel" class="btn btn-secondary btn-sm">Batal</button>
                            </span>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </aside>
    </div>
</main>

<script src="<?= e(base_url('assets/js/rating.js')) ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
