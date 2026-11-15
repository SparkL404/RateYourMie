<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan. Logout wajib menggunakan POST.');
}

csrf_require();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        (bool) ($params['secure'] ?? false),
        (bool) ($params['httponly'] ?? true)
    );
}

session_destroy();

init_session();
flash('info', 'Anda telah keluar dari sesi akun.');
redirect('index.php');
