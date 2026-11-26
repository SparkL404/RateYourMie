<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

/**
 * Memulai session secara aman dengan cookie HttpOnly dan SameSite=Lax.
 */
function init_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('RATEYOURMIESESSID');
    session_start();
}

init_session();

/**
 * Mengambil data pengguna yang sedang login, atau null jika pengunjung.
 */
function current_user(): ?array
{
    static $cachedUser = false;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if ($cachedUser !== false) {
        return $cachedUser;
    }

    $stmt = db()->prepare('SELECT id, username, email, role, email_verified, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user_id']);
        $cachedUser = null;
        return null;
    }

    $cachedUser = $user;
    return $cachedUser;
}

/**
 * Apakah pengguna saat ini adalah admin?
 */
function is_admin(): bool
{
    $u = current_user();
    return $u !== null && ($u['role'] ?? '') === 'admin';
}

/**
 * Mewajibkan pengguna login; jika belum, simpan URL tujuan dan arahkan ke auth/login.php (FU-03).
 */
function require_login(bool $asJson = false): array
{
    $user = current_user();
    if ($user !== null) {
        return $user;
    }

    if ($asJson) {
        json_response(false, 'Silakan login terlebih dahulu untuk melakukan aksi ini.', 401);
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if ($requestUri !== '') {
        $_SESSION['redirect_after_login'] = $requestUri;
    }
    flash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
    redirect('auth/login.php');
}

/**
 * Mewajibkan role admin; jika belum login arahkan ke auth/login.php, jika role user biasa tolak dengan 403 (FU-04).
 */
function require_admin(bool $asJson = false): array
{
    $user = require_login($asJson);
    if (($user['role'] ?? '') !== 'admin') {
        if ($asJson) {
            json_response(false, 'Akses ditolak: hanya admin yang diizinkan.', 403);
        }
        http_response_code(403);
        include __DIR__ . '/header.php';
        echo '<main class="container section-block"><div class="empty-state error-state">'
            . '<h1>403 — Akses Ditolak</h1>'
            . '<p>Halaman ini hanya dapat diakses oleh pengelola (Admin).</p>'
            . '<div class="form-actions"><a href="' . e(base_url('index.php')) . '" class="btn btn-primary">Kembali ke Beranda</a></div>'
            . '</div></main>';
        include __DIR__ . '/footer.php';
        exit;
    }
    return $user;
}

/**
 * Memeriksa apakah sesi/IP dibatasi karena terlalu banyak gagal login (5 gagal / 15 menit).
 */
function check_login_rate_limit(): ?int
{
    $now = time();
    $windowStart = $now - LOGIN_LOCKOUT_SECONDS;
    $attempts = $_SESSION['login_attempts'] ?? [];
    $attempts = array_values(array_filter($attempts, static fn($ts) => is_int($ts) && $ts >= $windowStart));
    $_SESSION['login_attempts'] = $attempts;

    if (count($attempts) >= LOGIN_MAX_ATTEMPTS) {
        $oldest = min($attempts);
        return max(1, ($oldest + LOGIN_LOCKOUT_SECONDS) - $now);
    }
    return null;
}

/**
 * Mencatat kegagalan percobaan login.
 */
function record_login_failure(): void
{
    $_SESSION['login_attempts'][] = time();
}

/**
 * Menghapus catatan gagal login setelah berhasil masuk.
 */
function clear_login_failures(): void
{
    unset($_SESSION['login_attempts']);
}

/**
 * Pembatasan frekuensi pengiriman rating per user (maksimal 20 per jam).
 */
function check_rating_rate_limit(): bool
{
    $now = time();
    $windowStart = $now - 3600;
    $events = $_SESSION['rating_timestamps'] ?? [];
    $events = array_values(array_filter($events, static fn($ts) => is_int($ts) && $ts >= $windowStart));
    $_SESSION['rating_timestamps'] = $events;

    return count($events) < RATING_MAX_PER_HOUR;
}

function record_rating_action(): void
{
    $_SESSION['rating_timestamps'][] = time();
}
