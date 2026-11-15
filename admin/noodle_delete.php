<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/noodle_repo.php';

$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan. Penghapusan wajib menggunakan POST.');
}

csrf_require();

$id = (int) ($_POST['id'] ?? 0);
$noodle = $id > 0 ? get_noodle_by_id($id) : null;

if (!$noodle) {
    flash('error', 'Data mie yang akan dihapus tidak ditemukan.');
    redirect('admin/noodles.php');
}

// Hapus file gambar di uploads/ jika ada
if (!empty($noodle['image'])) {
    $imgPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename((string) $noodle['image']);
    if (is_file($imgPath)) {
        @unlink($imgPath);
    }
}

$stmt = db()->prepare('DELETE FROM noodles WHERE id = :id');
$stmt->execute([':id' => $id]);

flash('success', 'Mie "' . $noodle['name'] . '" berhasil dihapus dari katalog.');
redirect('admin/noodles.php');
