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

if (!check_rating_rate_limit()) {
    json_response(false, 'Anda telah mencapai batas pengiriman rating (maksimal 20 kali per jam). Silakan coba beberapa saat lagi.', 429);
}

$noodleId = filter_var($input['noodle_id'] ?? null, FILTER_VALIDATE_INT);
if (!$noodleId || $noodleId <= 0) {
    json_response(false, 'ID mie tidak valid.', 422);
}

$noodle = get_noodle_by_id((int) $noodleId);
if (!$noodle) {
    json_response(false, 'Data mie tidak ditemukan.', 404);
}

$requiredAspects = ['taste', 'texture', 'portion', 'value'];
$scores = [];

foreach ($requiredAspects as $aspect) {
    $val = filter_var($input[$aspect] ?? null, FILTER_VALIDATE_INT);
    if ($val === false || $val < 1 || $val > 5) {
        json_response(false, "Setiap aspek penilaian (rasa, tekstur, porsi, harga) wajib berupa angka bulat 1 sampai 5.", 422);
    }
    $scores[$aspect] = (int) $val;
}

$review = trim((string) ($input['review'] ?? ''));
if (mb_strlen($review, 'UTF-8') > 500) {
    json_response(false, 'Ulasan singkat maksimal 500 karakter.', 422);
}

$pdo = db();
$stmt = $pdo->prepare('
    INSERT INTO ratings (user_id, noodle_id, taste, texture, `portion`, `value`, review)
    VALUES (:uid, :nid, :taste, :texture, :portion, :val, :review)
    ON DUPLICATE KEY UPDATE
        taste = VALUES(taste),
        texture = VALUES(texture),
        `portion` = VALUES(`portion`),
        `value` = VALUES(`value`),
        review = VALUES(review)
');

$stmt->execute([
    ':uid'     => (int) $user['id'],
    ':nid'     => (int) $noodleId,
    ':taste'   => $scores['taste'],
    ':texture' => $scores['texture'],
    ':portion' => $scores['portion'],
    ':val'     => $scores['value'],
    ':review'  => $review !== '' ? $review : null,
]);

record_rating_action();

$updatedNoodle = get_noodle_by_id((int) $noodleId);
$raterCount = (int) ($updatedNoodle['rater_count'] ?? 0);
$avgScore = $raterCount > 0 ? round((float) $updatedNoodle['avg_score'], 2) : null;
$minRaters = (int) (get_app_settings()['min_raters'] ?? MIN_RATERS);

json_response(true, [
    'message'     => 'Rating dan ulasan Anda berhasil disimpan.',
    'noodle_id'   => (int) $noodleId,
    'rater_count' => $raterCount,
    'avg_score'   => $avgScore,
    'tier'        => ($avgScore !== null && $raterCount >= $minRaters) ? score_to_tier($avgScore) : null,
    'aspects'     => [
        'taste'   => $raterCount > 0 ? round((float) $updatedNoodle['avg_taste'], 2) : null,
        'texture' => $raterCount > 0 ? round((float) $updatedNoodle['avg_texture'], 2) : null,
        'portion' => $raterCount > 0 ? round((float) $updatedNoodle['avg_portion'], 2) : null,
        'value'   => $raterCount > 0 ? round((float) $updatedNoodle['avg_value'], 2) : null,
    ],
]);
