<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/noodle_repo.php';

$communityData = get_community_tierlist_data();
$minRaters = (int) $communityData['min_raters'];
$tiers = $communityData['tiers'];
$pending = $communityData['pending'];

$pageTitle = 'Tierlist Komunitas Otomatis';
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container section-block">
    <section class="page-head">
        <div class="page-head-copy">
            <h1>Tierlist Komunitas</h1>
            <p>Disusun otomatis dari rata-rata skor pengguna untuk varian dengan minimal <strong><?= $minRaters ?> penilai</strong>.</p>
        </div>
        <div class="page-head-meta tabular">
            <span><strong><?= (int) $communityData['qualified_count'] ?></strong> varian memenuhi syarat</span>
            <span class="meta-dot" aria-hidden="true">·</span>
            <span>Min. <strong><?= $minRaters ?></strong> penilai</span>
        </div>
    </section>

    <section class="tierboard-wrapper" aria-label="Papan Tierlist Komunitas S sampai F">
        <?php foreach (all_tiers() as $tierLetter): ?>
            <?php
            $meta = tier_meta($tierLetter);
            $itemsInTier = $tiers[$tierLetter] ?? [];
            ?>
            <div class="tier-row">
                <div class="tier-row-header <?= e($meta['class']) ?>">
                    <span class="tier-row-letter"><?= e($tierLetter) ?></span>
                    <span class="tier-row-range"><?= e($meta['range']) ?></span>
                </div>
                <div class="tier-dropzone tier-dropzone-readonly">
                    <?php if (empty($itemsInTier)): ?>
                        <span class="tier-empty-note">
                            Belum ada varian di Tier <?= e($tierLetter) ?> (<?= e($meta['range']) ?>).
                        </span>
                    <?php else: ?>
                        <?php foreach ($itemsInTier as $noodle): ?>
                            <article class="tier-item-card is-static">
                                <a href="<?= e(base_url('noodle.php?id=' . (int) $noodle['id'])) ?>" class="tier-item-visual">
                                    <?= render_noodle_visual($noodle, 'pack-visual-sm') ?>
                                </a>
                                <a href="<?= e(base_url('noodle.php?id=' . (int) $noodle['id'])) ?>" class="tier-item-name" title="<?= e($noodle['name']) ?>">
                                    <?= e($noodle['name']) ?>
                                </a>
                                <div class="tier-item-meta tabular">
                                    <strong class="tier-item-score">★ <?= e(format_score($noodle['avg_score'], 2)) ?></strong>
                                    <span><?= (int) $noodle['rater_count'] ?> penilai</span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <?php if (!empty($pending)): ?>
        <section class="unranked-pool-box" aria-label="Mie Belum Cukup Penilai">
            <div class="unranked-pool-head">
                <h2>Belum Cukup Penilai (&lt; <?= $minRaters ?> Penilai)</h2>
                <p>Klik varian di bawah untuk menambahkan penilaian agar masuk perhitungan Tierlist Komunitas dan Top 10.</p>
            </div>
            <div class="pending-list">
                <?php foreach ($pending as $pNoodle): ?>
                    <a href="<?= e(base_url('noodle.php?id=' . (int) $pNoodle['id'])) ?>" class="pending-chip">
                        <strong><?= e($pNoodle['name']) ?></strong>
                        <span class="tabular">
                            <?= (int) $pNoodle['rater_count'] ?>/<?= $minRaters ?> penilai
                            <?php if ((int) $pNoodle['rater_count'] > 0): ?>
                                · ★ <?= e(format_score($pNoodle['avg_score'], 2)) ?>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
