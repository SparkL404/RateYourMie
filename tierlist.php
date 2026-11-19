<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/noodle_repo.php';

$user = require_login();
$pdo = db();

$tierlistId = (int) ($_GET['id'] ?? 0);
if ($tierlistId <= 0) {
    redirect('tierlists.php');
}

$stmt = $pdo->prepare('SELECT * FROM tierlists WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $tierlistId]);
$tierlist = $stmt->fetch();

if (!$tierlist) {
    http_response_code(404);
    $pageTitle = 'Tierlist Tidak Ditemukan';
    include __DIR__ . '/includes/header.php';
    echo '<main id="main-content" class="container section-block"><div class="empty-state">'
        . '<h1>404 — Tierlist Tidak Ditemukan</h1>'
        . '<p>Tierlist yang Anda cari tidak tersedia atau telah dihapus.</p>'
        . '<a href="' . e(base_url('tierlists.php')) . '" class="btn btn-primary">Kembali ke Tierlist Saya</a>'
        . '</div></main>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

if ((int) $tierlist['user_id'] !== (int) $user['id']) {
    http_response_code(403);
    $pageTitle = 'Akses Ditolak';
    include __DIR__ . '/includes/header.php';
    echo '<main id="main-content" class="container section-block"><div class="empty-state">'
        . '<h1>403 — Akses Tierlist Ditolak</h1>'
        . '<p>Anda hanya dapat membuka dan menyusun tierlist pribadi milik Anda sendiri.</p>'
        . '<a href="' . e(base_url('tierlists.php')) . '" class="btn btn-primary">Buka Tierlist Saya</a>'
        . '</div></main>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$allNoodles = get_all_noodles_with_scores();
$noodleMap = [];
foreach ($allNoodles as $n) {
    $noodleMap[(int) $n['id']] = $n;
}

$itemStmt = $pdo->prepare('
    SELECT noodle_id, tier, position
    FROM tierlist_items
    WHERE tierlist_id = :tid
    ORDER BY FIELD(tier, "S","A","B","C","D","F"), position ASC, id ASC
');
$itemStmt->execute([':tid' => $tierlistId]);
$savedItems = $itemStmt->fetchAll();

$tiersBuckets = [
    'S' => [],
    'A' => [],
    'B' => [],
    'C' => [],
    'D' => [],
    'F' => [],
];
$placedIds = [];

foreach ($savedItems as $item) {
    $nid = (int) $item['noodle_id'];
    $t = strtoupper((string) $item['tier']);
    if (isset($noodleMap[$nid], $tiersBuckets[$t]) && !isset($placedIds[$nid])) {
        $noodle = $noodleMap[$nid];
        $noodle['current_tier'] = $t;
        $tiersBuckets[$t][] = $noodle;
        $placedIds[$nid] = true;
    }
}

$unrankedNoodles = [];
foreach ($allNoodles as $n) {
    $nid = (int) $n['id'];
    if (!isset($placedIds[$nid])) {
        $n['current_tier'] = 'UNRANKED';
        $unrankedNoodles[] = $n;
    }
}

/**
 * Helper lokal merender kartu mie interaktif untuk papan tierlist.
 */
function render_tierlist_card_item(array $noodle): string
{
    $nid = (int) $noodle['id'];
    $curTier = (string) ($noodle['current_tier'] ?? 'UNRANKED');
    $raterCount = (int) ($noodle['rater_count'] ?? 0);
    $scoreHtml = $raterCount > 0
        ? '<strong class="tier-item-score">' . svg_icon('star', 11) . ' ' . e(format_score($noodle['avg_score'], 2)) . '</strong>'
        : '<span>Baru</span>';

    $quickButtons = '';
    foreach (all_tiers() as $tl) {
        $activeCls = ($curTier === $tl) ? ' is-active ' . tier_meta($tl)['class'] : '';
        $quickButtons .= sprintf(
            '<button type="button" class="tier-quick-btn js-tier-quick-btn%s" data-target-tier="%s" title="Pindah ke Tier %s">%s</button>',
            e($activeCls),
            e($tl),
            e($tl),
            e($tl)
        );
    }
    $unrankedActive = ($curTier === 'UNRANKED') ? ' is-active' : '';
    $quickButtons .= sprintf(
        '<button type="button" class="tier-quick-btn tier-quick-reset js-tier-quick-btn%s" data-target-tier="UNRANKED" title="Keluarkan dari papan">–</button>',
        e($unrankedActive)
    );

    $optionsHtml = '<option value="UNRANKED"' . ($curTier === 'UNRANKED' ? ' selected' : '') . '>Belum Dikelompokkan</option>';
    foreach (all_tiers() as $tl) {
        $sel = ($curTier === $tl) ? ' selected' : '';
        $optionsHtml .= '<option value="' . e($tl) . '"' . $sel . '>Pindah ke Tier ' . e($tl) . '</option>';
    }

    return sprintf(
        '<article class="tier-item-card" draggable="true" data-noodle-id="%d">'
        . '<div class="tier-card-top">'
        . '<span class="tier-item-swatch">%s</span>'
        . '<a href="%s" class="tier-item-name" title="%s">%s</a>'
        . '</div>'
        . '<div class="tier-item-meta tabular"><span>%s</span>%s</div>'
        . '<div class="tier-quick-bar" role="group" aria-label="Pilih tier cepat untuk %s">%s</div>'
        . '<label class="skip-link" for="tier-select-%d">Pilih tier untuk %s</label>'
        . '<select id="tier-select-%d" class="tier-select-control js-tier-select skip-link" aria-label="Pilih tier untuk %s">%s</select>'
        . '</article>',
        $nid,
        render_noodle_swatch($noodle, 'swatch-xs'),
        e(base_url('noodle.php?id=' . $nid)),
        e($noodle['name']),
        e($noodle['name']),
        e($noodle['brand']),
        $scoreHtml,
        e($noodle['name']),
        $quickButtons,
        $nid,
        e($noodle['name']),
        $nid,
        e($noodle['name']),
        $optionsHtml
    );
}

$pageTitle = 'Susun Tierlist: ' . $tierlist['title'];
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container section-block" data-tierlist-id="<?= (int) $tierlist['id'] ?>">
    <div class="breadcrumb-bar">
        <a href="<?= e(base_url('tierlists.php')) ?>" class="back-link">
            <?= svg_icon('arrow-left', 15) ?> Kembali ke Tierlist Saya
        </a>
        <span class="muted-hint">
            Seret kartu ke baris S–F atau ketuk tombol S–F langsung pada kartu.
        </span>
    </div>

    <section class="page-head">
        <div class="page-head-copy">
            <h1><?= e($tierlist['title']) ?></h1>
            <p>Kategori: <strong><?= e($tierlist['category'] ?: 'Semua Varian') ?></strong> · Satu mie hanya muncul sekali dalam satu tierlist.</p>
        </div>
        <div class="page-head-actions">
            <span id="js-save-status" class="save-status-badge status-saved" role="status" aria-live="polite">Tersimpan</span>
            <button type="button" id="js-btn-save-tierlist" class="btn btn-primary btn-sm">
                <?= svg_icon('check', 15) ?> Simpan Susunan
            </button>
        </div>
    </section>

    <section class="tierboard-wrapper" id="js-tierboard" data-tierlist-id="<?= (int) $tierlist['id'] ?>" aria-label="Papan Tierlist S sampai F">
        <?php foreach (all_tiers() as $tierLetter): ?>
            <?php $meta = tier_meta($tierLetter); ?>
            <div class="tier-row">
                <div class="tier-row-header <?= e($meta['class']) ?>">
                    <span class="tier-row-letter"><?= e($tierLetter) ?></span>
                    <span class="tier-row-range"><?= e($meta['name']) ?></span>
                </div>
                <div
                    class="tier-dropzone js-tier-dropzone"
                    data-tier="<?= e($tierLetter) ?>"
                    aria-label="Baris Tier <?= e($tierLetter) ?>"
                >
                    <?php foreach ($tiersBuckets[$tierLetter] as $noodle): ?>
                        <?= render_tierlist_card_item($noodle) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="unranked-pool-box" aria-label="Mie Belum Dikelompokkan">
        <div class="unranked-pool-head">
            <h2>Belum Dikelompokkan (<span id="js-unranked-count" class="tabular"><?= count($unrankedNoodles) ?></span>)</h2>
            <p>Seret mie dari area ini ke baris S–F di atas atau ketuk tombol S–F pada kartu.</p>
        </div>
        <div
            class="tier-dropzone tier-unranked-zone js-tier-dropzone"
            data-tier="UNRANKED"
            id="js-unranked-zone"
        >
            <?php foreach ($unrankedNoodles as $noodle): ?>
                <?= render_tierlist_card_item($noodle) ?>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="tierlist-SaveBar">
        <div class="savebar-status">
            <span>Status:</span>
            <span id="js-save-status-bottom" class="save-status-badge status-saved">Tersimpan</span>
        </div>
        <button type="button" id="js-btn-save-tierlist-bottom" class="btn btn-primary btn-sm">
            <?= svg_icon('check', 15) ?> Simpan ke Database
        </button>
    </div>
</main>

<script src="<?= e(base_url('assets/js/tierlist.js')) ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
