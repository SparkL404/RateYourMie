<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tier.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Metode tidak diizinkan. Gunakan POST.', 405);
}

$user = require_login(true);

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody ?: '', true);
if (!is_array($input)) {
    json_response(false, 'Format payload JSON tidak valid.', 422);
}

$csrfToken = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
csrf_require(is_string($csrfToken) ? $csrfToken : null, true);

$tierlistId = filter_var($input['tierlist_id'] ?? null, FILTER_VALIDATE_INT);
if (!$tierlistId || $tierlistId <= 0) {
    json_response(false, 'ID tierlist tidak valid.', 422);
}

$items = $input['items'] ?? null;
if (!is_array($items)) {
    json_response(false, 'Daftar item tierlist tidak valid.', 422);
}

$pdo = db();

// Pastikan tierlist ada dan dimiliki oleh pengguna yang login
$checkStmt = $pdo->prepare('SELECT id, user_id FROM tierlists WHERE id = :id LIMIT 1');
$checkStmt->execute([':id' => (int) $tierlistId]);
$tierlist = $checkStmt->fetch();

if (!$tierlist) {
    json_response(false, 'Tierlist tidak ditemukan.', 404);
}

if ((int) $tierlist['user_id'] !== (int) $user['id']) {
    json_response(false, 'Akses ditolak: Anda tidak berhak mengubah tierlist pengguna lain.', 403);
}

$validTiers = all_tiers();
$seenNoodles = [];
$cleanItems = [];

// Ambil seluruh ID mie yang sah dari database
$validNoodleIds = array_map(
    'intval',
    $pdo->query('SELECT id FROM noodles')->fetchAll(PDO::FETCH_COLUMN)
);
$validNoodleSet = array_flip($validNoodleIds);

foreach ($items as $idx => $item) {
    if (!is_array($item)) {
        json_response(false, 'Struktur item pada posisi ' . $idx . ' tidak valid.', 422);
    }

    $noodleId = filter_var($item['noodle_id'] ?? null, FILTER_VALIDATE_INT);
    $tier = strtoupper(trim((string) ($item['tier'] ?? '')));
    $position = filter_var($item['position'] ?? $idx, FILTER_VALIDATE_INT);

    if (!$noodleId || !isset($validNoodleSet[(int) $noodleId])) {
        json_response(false, 'Terdapat ID mie yang tidak terdaftar di katalog.', 422);
    }

    if (!in_array($tier, $validTiers, true)) {
        json_response(false, 'Huruf tier tidak valid. Hanya mengizinkan S, A, B, C, D, atau F.', 422);
    }

    if (isset($seenNoodles[(int) $noodleId])) {
        json_response(false, 'Satu mie hanya boleh muncul satu kali dalam satu tierlist.', 422);
    }

    $seenNoodles[(int) $noodleId] = true;
    $cleanItems[] = [
        'noodle_id' => (int) $noodleId,
        'tier'      => $tier,
        'position'  => ($position !== false && $position >= 0) ? min(65535, (int) $position) : 0,
    ];
}

try {
    $pdo->beginTransaction();

    $delStmt = $pdo->prepare('DELETE FROM tierlist_items WHERE tierlist_id = :tid');
    $delStmt->execute([':tid' => (int) $tierlistId]);

    if (!empty($cleanItems)) {
        $insStmt = $pdo->prepare('
            INSERT INTO tierlist_items (tierlist_id, noodle_id, tier, position)
            VALUES (:tid, :nid, :tier, :pos)
        ');
        foreach ($cleanItems as $ci) {
            $insStmt->execute([
                ':tid'  => (int) $tierlistId,
                ':nid'  => $ci['noodle_id'],
                ':tier' => $ci['tier'],
                ':pos'  => $ci['position'],
            ]);
        }
    }

    $touchStmt = $pdo->prepare('UPDATE tierlists SET updated_at = CURRENT_TIMESTAMP WHERE id = :tid');
    $touchStmt->execute([':tid' => (int) $tierlistId]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Tierlist save error: ' . $e->getMessage());
    json_response(false, 'Gagal menyimpan susunan tierlist ke database.', 500);
}

json_response(true, [
    'message'     => 'Tersimpan',
    'tierlist_id' => (int) $tierlistId,
    'item_count'  => count($cleanItems),
    'saved_at'    => date('H:i:s'),
]);
