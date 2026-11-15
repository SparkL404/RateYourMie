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

$tierlistId = filter_var($input['tierlist_id'] ?? null, FILTER_VALIDATE_INT);
$title = trim((string) ($input['title'] ?? ''));

if (!$tierlistId || $tierlistId <= 0) {
    json_response(false, 'ID tierlist tidak valid.', 422);
}

if ($title === '' || mb_strlen($title, 'UTF-8') > 100) {
    json_response(false, 'Judul tierlist wajib diisi (maksimal 100 karakter).', 422);
}

$pdo = db();

// Cek kepemilikan tierlist
$checkStmt = $pdo->prepare('SELECT id, user_id FROM tierlists WHERE id = :id LIMIT 1');
$checkStmt->execute([':id' => (int) $tierlistId]);
$row = $checkStmt->fetch();

if (!$row) {
    json_response(false, 'Tierlist tidak ditemukan.', 404);
}

if ((int) $row['user_id'] !== (int) $user['id']) {
    json_response(false, 'Akses ditolak: Anda bukan pemilik tierlist ini.', 403);
}

$updateStmt = $pdo->prepare('UPDATE tierlists SET title = :title WHERE id = :id AND user_id = :uid');
$updateStmt->execute([
    ':title' => $title,
    ':id'    => (int) $tierlistId,
    ':uid'   => (int) $user['id'],
]);

json_response(true, [
    'message'     => 'Judul tierlist berhasil diperbarui.',
    'tierlist_id' => (int) $tierlistId,
    'title'       => $title,
]);
