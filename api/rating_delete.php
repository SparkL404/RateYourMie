<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/noodle_repo.php';

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

$noodleId = filter_var($input['noodle_id'] ?? null, FILTER_VALIDATE_INT);
if (!$noodleId || $noodleId <= 0) {
    json_response(false, 'ID mie tidak valid.', 422);
}

$stmt = db()->prepare('DELETE FROM ratings WHERE user_id = :uid AND noodle_id = :nid');
$stmt->execute([
    ':uid' => (int) $user['id'],
    ':nid' => (int) $noodleId,
]);

if ($stmt->rowCount() === 0) {
    json_response(false, 'Rating tidak ditemukan atau sudah dihapus.', 404);
}

$updatedNoodle = get_noodle_by_id((int) $noodleId);
$raterCount = (int) ($updatedNoodle['rater_count'] ?? 0);
$avgScore = $raterCount > 0 ? round((float) $updatedNoodle['avg_score'], 2) : null;

json_response(true, [
    'message'     => 'Rating Anda berhasil dihapus.',
    'noodle_id'   => (int) $noodleId,
    'rater_count' => $raterCount,
    'avg_score'   => $avgScore,
]);
