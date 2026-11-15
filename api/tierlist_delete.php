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
if (!$tierlistId || $tierlistId <= 0) {
    json_response(false, 'ID tierlist tidak valid.', 422);
}

$pdo = db();

$checkStmt = $pdo->prepare('SELECT id, user_id FROM tierlists WHERE id = :id LIMIT 1');
$checkStmt->execute([':id' => (int) $tierlistId]);
$row = $checkStmt->fetch();

if (!$row) {
    json_response(false, 'Tierlist tidak ditemukan.', 404);
}

if ((int) $row['user_id'] !== (int) $user['id']) {
    json_response(false, 'Akses ditolak: Anda bukan pemilik tierlist ini.', 403);
}

$delStmt = $pdo->prepare('DELETE FROM tierlists WHERE id = :id AND user_id = :uid');
$delStmt->execute([
    ':id'  => (int) $tierlistId,
    ':uid' => (int) $user['id'],
]);

json_response(true, [
    'message'     => 'Tierlist berhasil dihapus.',
    'tierlist_id' => (int) $tierlistId,
]);
