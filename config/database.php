<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Mengembalikan instance PDO singleton.
 * Pesan detail koneksi tidak pernah ditampilkan ke pengguna akhir.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        http_response_code(500);
        exit('Terjadi gangguan koneksi ke basis data. Pastikan layanan MySQL/MariaDB aktif dan database "rating_mie" telah dibuat.');
    }

    return $pdo;
}

/**
 * Membaca pengaturan dari tabel settings dengan fallback ke konstanta config.php.
 */
function get_app_settings(): array
{
    static $loaded = null;
    if ($loaded !== null) {
        return $loaded;
    }

    $loaded = [
        'min_raters'      => MIN_RATERS,
        'tier_thresholds' => TIER_THRESHOLDS,
    ];

    try {
        $stmt = db()->query('SELECT key_name, key_value FROM settings');
        $rows = $stmt->fetchAll();
        $customThresholds = $loaded['tier_thresholds'];

        foreach ($rows as $row) {
            $k = (string) $row['key_name'];
            $v = (string) $row['key_value'];
            if ($k === 'min_raters' && is_numeric($v) && (int) $v >= 1) {
                $loaded['min_raters'] = (int) $v;
            } elseif (preg_match('/^tier_([SABCD])$/', $k, $m) && is_numeric($v)) {
                $customThresholds[$m[1]] = (float) $v;
            }
        }
        $loaded['tier_thresholds'] = $customThresholds;
    } catch (Throwable $e) {
        // Fallback ke konstanta jika tabel belum ada atau gagal dibaca
    }

    return $loaded;
}
