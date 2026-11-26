<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Metode tidak diizinkan. Gunakan POST.', 405);
}

$user = require_login(true);

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

$csrfToken = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
csrf_require(is_string($csrfToken) ? $csrfToken : null, true);

$title = trim((string) ($input['title'] ?? ''));
$category = trim((string) ($input['category'] ?? ''));

if ($title === '' || mb_strlen($title, 'UTF-8') > 100) {
    json_response(false, 'Judul tierlist wajib diisi (maksimal 100 karakter).', 422);
}

if (mb_strlen($category, 'UTF-8') > 50) {
    json_response(false, 'Kategori maksimal 50 karakter.', 422);
}

$pdo = db();

// Tegakkan batas MAX_TIERLISTS (10) per pengguna (FT-07)
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM tierlists WHERE user_id = :uid');
$countStmt->execute([':uid' => (int) $user['id']]);
$currentCount = (int) $countStmt->fetchColumn();

if ($currentCount >= MAX_TIERLISTS) {
    json_response(
        false,
        'Batas maksimal ' . MAX_TIERLISTS . ' tierlist per pengguna telah tercapai. Hapus salah satu tierlist lama untuk membuat yang baru.',
        422
    );
}

$stmt = $pdo->prepare('
    INSERT INTO tierlists (user_id, title, category, is_public)
    VALUES (:uid, :title, :category, 0)
');
$stmt->execute([
    ':uid'      => (int) $user['id'],
    ':title'    => $title,
    ':category' => $category !== '' ? $category : null,
]);

$newId = (int) $pdo->lastInsertId();

json_response(true, [
    'message'     => 'Tierlist baru berhasil dibuat.',
    'tierlist_id' => $newId,
    'title'       => $title,
    'category'    => $category !== '' ? $category : null,
    'url'         => base_url('pages/tierlist.php?id=' . $newId),
], 201);
