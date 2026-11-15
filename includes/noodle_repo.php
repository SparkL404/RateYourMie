<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/tier.php';

/**
 * Mengambil opsi unik untuk dropdown filter (brand, flavor, origin_country).
 */
function get_noodle_filter_options(): array
{
    $pdo = db();
    $brands = $pdo->query('SELECT DISTINCT brand FROM noodles ORDER BY brand ASC')->fetchAll(PDO::FETCH_COLUMN);
    $flavors = $pdo->query('SELECT DISTINCT flavor FROM noodles ORDER BY flavor ASC')->fetchAll(PDO::FETCH_COLUMN);
    $countries = $pdo->query('SELECT DISTINCT origin_country FROM noodles ORDER BY origin_country ASC')->fetchAll(PDO::FETCH_COLUMN);

    return [
        'brands'    => $brands ?: [],
        'flavors'   => $flavors ?: [],
        'countries' => $countries ?: [],
    ];
}

/**
 * Mengambil daftar mie untuk katalog publik atau tabel admin dengan pencarian, filter, dan pagination.
 */
function get_catalog_noodles(array $filters = [], int $page = 1, int $perPage = 9): array
{
    $pdo = db();
    $where = [];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = 'n.name LIKE :q';
        $params[':q'] = '%' . $q . '%';
    }

    $brand = trim((string) ($filters['brand'] ?? ''));
    if ($brand !== '') {
        $where[] = 'n.brand = :brand';
        $params[':brand'] = $brand;
    }

    $flavor = trim((string) ($filters['flavor'] ?? ''));
    if ($flavor !== '') {
        $where[] = 'n.flavor = :flavor';
        $params[':flavor'] = $flavor;
    }

    $country = trim((string) ($filters['country'] ?? ''));
    if ($country !== '') {
        $where[] = 'n.origin_country = :country';
        $params[':country'] = $country;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Hitung total baris
    $countSql = "SELECT COUNT(*) FROM noodles n $whereSql";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    $sql = "
        SELECT
            n.*,
            COUNT(r.id) AS rater_count,
            AVG((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS avg_score,
            AVG(r.taste) AS avg_taste,
            AVG(r.texture) AS avg_texture,
            AVG(r.`portion`) AS avg_portion,
            AVG(r.`value`) AS avg_value
        FROM noodles n
        LEFT JOIN ratings r ON r.noodle_id = n.id
        $whereSql
        GROUP BY n.id
        ORDER BY (COUNT(r.id) > 0) DESC, avg_score DESC, rater_count DESC, n.name ASC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll();

    return [
        'items'       => $items,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
    ];
}

/**
 * Mengambil satu mie beserta rata-rata skor akhir, rata-rata per aspek, dan jumlah penilai.
 */
function get_noodle_by_id(int $id): ?array
{
    $sql = "
        SELECT
            n.*,
            COUNT(r.id) AS rater_count,
            AVG((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS avg_score,
            AVG(r.taste) AS avg_taste,
            AVG(r.texture) AS avg_texture,
            AVG(r.`portion`) AS avg_portion,
            AVG(r.`value`) AS avg_value
        FROM noodles n
        LEFT JOIN ratings r ON r.noodle_id = n.id
        WHERE n.id = :id
        GROUP BY n.id
        LIMIT 1
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Mengambil seluruh daftar ulasan untuk satu mie.
 */
function get_noodle_reviews(int $noodleId): array
{
    $sql = "
        SELECT
            r.*,
            ((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS final_score,
            u.username
        FROM ratings r
        INNER JOIN users u ON u.id = r.user_id
        WHERE r.noodle_id = :noodle_id
        ORDER BY r.created_at DESC, r.id DESC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([':noodle_id' => $noodleId]);
    return $stmt->fetchAll();
}

/**
 * Mengambil rating milik satu user pada satu mie (jika sudah pernah memberi rating).
 */
function get_user_rating_for_noodle(int $userId, int $noodleId): ?array
{
    $stmt = db()->prepare('SELECT * FROM ratings WHERE user_id = :uid AND noodle_id = :nid LIMIT 1');
    $stmt->execute([
        ':uid' => $userId,
        ':nid' => $noodleId,
    ]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Mengambil semua mie untuk penyusunan tierlist pribadi.
 */
function get_all_noodles_with_scores(): array
{
    $sql = "
        SELECT
            n.*,
            COUNT(r.id) AS rater_count,
            AVG((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS avg_score
        FROM noodles n
        LEFT JOIN ratings r ON r.noodle_id = n.id
        GROUP BY n.id
        ORDER BY n.brand ASC, n.name ASC
    ";
    return db()->query($sql)->fetchAll();
}

/**
 * Mengambil data Tierlist Komunitas (mie dengan rater_count >= MIN_RATERS)
 * serta daftar mie yang belum memenuhi syarat minimal penilai.
 */
function get_community_tierlist_data(): array
{
    $settings = get_app_settings();
    $minRaters = (int) ($settings['min_raters'] ?? MIN_RATERS);

    $sqlQualified = "
        SELECT
            n.*,
            COUNT(r.id) AS rater_count,
            AVG((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS avg_score,
            AVG(r.taste) AS avg_taste,
            AVG(r.texture) AS avg_texture,
            AVG(r.`portion`) AS avg_portion,
            AVG(r.`value`) AS avg_value
        FROM noodles n
        INNER JOIN ratings r ON r.noodle_id = n.id
        GROUP BY n.id
        HAVING COUNT(r.id) >= :min_raters
        ORDER BY avg_score DESC, rater_count DESC, n.name ASC
    ";
    $stmt = db()->prepare($sqlQualified);
    $stmt->bindValue(':min_raters', $minRaters, PDO::PARAM_INT);
    $stmt->execute();
    $qualified = $stmt->fetchAll();

    $tiers = [
        'S' => [],
        'A' => [],
        'B' => [],
        'C' => [],
        'D' => [],
        'F' => [],
    ];

    foreach ($qualified as $row) {
        $tier = score_to_tier((float) $row['avg_score']);
        $row['computed_tier'] = $tier;
        $tiers[$tier][] = $row;
    }

    // Ambil juga mie yang belum mencapai MIN_RATERS untuk informasi transparan di bawah
    $sqlPending = "
        SELECT
            n.*,
            COUNT(r.id) AS rater_count,
            AVG((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS avg_score
        FROM noodles n
        LEFT JOIN ratings r ON r.noodle_id = n.id
        GROUP BY n.id
        HAVING COUNT(r.id) < :min_raters
        ORDER BY rater_count DESC, n.name ASC
    ";
    $stmtPending = db()->prepare($sqlPending);
    $stmtPending->bindValue(':min_raters', $minRaters, PDO::PARAM_INT);
    $stmtPending->execute();

    return [
        'min_raters'      => $minRaters,
        'qualified_count' => count($qualified),
        'tiers'           => $tiers,
        'pending'         => $stmtPending->fetchAll(),
    ];
}

/**
 * Mengambil Top 10 mie berdasarkan skor akhir tertinggi dengan syarat minimal MIN_RATERS penilai (FR-06).
 * Urutan seri diputuskan oleh jumlah penilai terbanyak lalu nama mie secara alfabetis.
 */
function get_top10_noodles(): array
{
    $settings = get_app_settings();
    $minRaters = (int) ($settings['min_raters'] ?? MIN_RATERS);

    $sql = "
        SELECT
            n.*,
            COUNT(r.id) AS rater_count,
            AVG((r.taste + r.texture + r.`portion` + r.`value`) / 4) AS avg_score,
            AVG(r.taste) AS avg_taste,
            AVG(r.texture) AS avg_texture,
            AVG(r.`portion`) AS avg_portion,
            AVG(r.`value`) AS avg_value
        FROM noodles n
        INNER JOIN ratings r ON r.noodle_id = n.id
        GROUP BY n.id
        HAVING COUNT(r.id) >= :min_raters
        ORDER BY avg_score DESC, rater_count DESC, n.name ASC
        LIMIT 10
    ";
    $stmt = db()->prepare($sql);
    $stmt->bindValue(':min_raters', $minRaters, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
