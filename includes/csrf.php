<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Menghasilkan atau mengambil token CSRF dari session aktif.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Session belum diinisialisasi sebelum meminta CSRF token.');
    }
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Menghasilkan elemen input hidden CSRF untuk form HTML.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Memverifikasi token CSRF dari POST form, JSON body, atau header X-CSRF-Token.
 */
function csrf_verify(?string $providedToken = null): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['csrf_token'])) {
        return false;
    }

    if ($providedToken === null) {
        $providedToken = $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';
    }

    if (!is_string($providedToken) || $providedToken === '') {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $providedToken);
}

/**
 * Menolak request dengan HTTP 403 jika token CSRF tidak valid.
 */
function csrf_require(?string $providedToken = null, bool $asJson = false): void
{
    if (!csrf_verify($providedToken)) {
        if ($asJson) {
            json_response(false, 'Sesi keamanan (CSRF) tidak valid atau telah kedaluwarsa. Muat ulang halaman.', 403);
        }
        http_response_code(403);
        exit('Permintaan ditolak: Token keamanan CSRF tidak valid.');
    }
}
