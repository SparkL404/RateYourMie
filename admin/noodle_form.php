<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/noodle_repo.php';

$admin = require_admin();
$pdo = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$editing = null;

if ($id > 0) {
    $editing = get_noodle_by_id($id);
    if (!$editing) {
        flash('error', 'Data mie tidak ditemukan.');
        redirect('admin/noodles.php');
    }
}

$errors = [];
$formData = [
    'name'           => $editing['name'] ?? '',
    'brand'          => $editing['brand'] ?? '',
    'flavor'         => $editing['flavor'] ?? '',
    'origin_country' => $editing['origin_country'] ?? 'Indonesia',
    'price'          => isset($editing['price']) ? (string) $editing['price'] : '3000',
    'description'    => $editing['description'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $formData['name'] = trim((string) ($_POST['name'] ?? ''));
    $formData['brand'] = trim((string) ($_POST['brand'] ?? ''));
    $formData['flavor'] = trim((string) ($_POST['flavor'] ?? ''));
    $formData['origin_country'] = trim((string) ($_POST['origin_country'] ?? ''));
    $formData['price'] = trim((string) ($_POST['price'] ?? ''));
    $formData['description'] = trim((string) ($_POST['description'] ?? ''));
    $removeImage = !empty($_POST['remove_image']);

    if ($formData['name'] === '' || strlen($formData['name']) > 150) {
        $errors[] = 'Nama mie wajib diisi (maksimal 150 karakter).';
    }
    if ($formData['brand'] === '' || strlen($formData['brand']) > 100) {
        $errors[] = 'Brand wajib diisi (maksimal 100 karakter).';
    }
    if ($formData['flavor'] === '' || strlen($formData['flavor']) > 100) {
        $errors[] = 'Varian rasa wajib diisi (maksimal 100 karakter).';
    }
    if ($formData['origin_country'] === '' || strlen($formData['origin_country']) > 80) {
        $errors[] = 'Negara asal wajib diisi (maksimal 80 karakter).';
    }
    if ($formData['price'] === '' || !ctype_digit($formData['price']) || (int) $formData['price'] < 0) {
        $errors[] = 'Harga wajib berupa bilangan bulat positif atau 0 (dalam Rupiah).';
    }

    $newImageFilename = $editing['image'] ?? null;

    if ($removeImage && $newImageFilename) {
        $oldFile = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename((string) $newImageFilename);
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
        $newImageFilename = null;
    }

    // Validasi upload gambar menggunakan finfo_file
    if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $fileErr = (int) $_FILES['image']['error'];
        if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = 'Ukuran file gambar melebihi batas maksimal 2 MB.';
        } elseif ($fileErr !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah file gambar.';
        } else {
            $tmpPath = (string) $_FILES['image']['tmp_name'];
            $fileSize = (int) $_FILES['image']['size'];

            if ($fileSize <= 0 || $fileSize > UPLOAD_MAX_BYTES) {
                $errors[] = 'Ukuran file gambar tidak boleh melebihi 2 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = (string) $finfo->file($tmpPath);
                $allowed = UPLOAD_ALLOWED;

                if (!isset($allowed[$mime])) {
                    $errors[] = 'Format gambar tidak didukung. Hanya menerima JPG, PNG, atau WebP asli.';
                } else {
                    $imgInfo = @getimagesize($tmpPath);
                    if ($imgInfo === false) {
                        $errors[] = 'File yang diunggah bukan gambar yang valid.';
                    } else {
                        $ext = $allowed[$mime];
                        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
                        if (!is_dir(UPLOAD_DIR)) {
                            mkdir(UPLOAD_DIR, 0755, true);
                        }
                        $destPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $randomName;
                        if (!move_uploaded_file($tmpPath, $destPath)) {
                            $errors[] = 'Gagal menyimpan file gambar ke folder uploads.';
                        } else {
                            if (!empty($editing['image'])) {
                                $oldPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename((string) $editing['image']);
                                if (is_file($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }
                            $newImageFilename = $randomName;
                        }
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        if ($editing) {
            $stmt = $pdo->prepare('
                UPDATE noodles
                SET name = :name,
                    brand = :brand,
                    flavor = :flavor,
                    origin_country = :origin_country,
                    price = :price,
                    description = :description,
                    image = :image
                WHERE id = :id
            ');
            $stmt->execute([
                ':name'           => $formData['name'],
                ':brand'          => $formData['brand'],
                ':flavor'         => $formData['flavor'],
                ':origin_country' => $formData['origin_country'],
                ':price'          => (int) $formData['price'],
                ':description'    => $formData['description'] !== '' ? $formData['description'] : null,
                ':image'          => $newImageFilename,
                ':id'             => (int) $editing['id'],
            ]);
            flash('success', 'Data mie "' . $formData['name'] . '" berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO noodles (name, brand, flavor, origin_country, price, description, image)
                VALUES (:name, :brand, :flavor, :origin_country, :price, :description, :image)
            ');
            $stmt->execute([
                ':name'           => $formData['name'],
                ':brand'          => $formData['brand'],
                ':flavor'         => $formData['flavor'],
                ':origin_country' => $formData['origin_country'],
                ':price'          => (int) $formData['price'],
                ':description'    => $formData['description'] !== '' ? $formData['description'] : null,
                ':image'          => $newImageFilename,
            ]);
            flash('success', 'Mie baru "' . $formData['name'] . '" berhasil ditambahkan ke katalog.');
        }

        redirect('admin/noodles.php');
    }
}

$pageTitle = $editing ? 'Ubah Data Mie' : 'Tambah Mie Baru';
include __DIR__ . '/../includes/header.php';
?>

<main id="main-content" class="container section-block">
    <div class="breadcrumb-bar">
        <a href="<?= e(base_url('admin/noodles.php')) ?>" class="back-link">
            <?= svg_icon('arrow-left', 15) ?> Kembali ke Daftar Mie
        </a>
    </div>

    <section class="page-head">
        <div class="page-head-copy">
            <h1><?= $editing ? 'Ubah Data: ' . e($editing['name']) : 'Tambah Varian Mie Baru' ?></h1>
            <p>Isi informasi produk mie instan. Perubahan langsung tampil di katalog publik.</p>
        </div>
    </section>

    <?php if (!empty($errors)): ?>
        <div class="flash-stack" role="alert" style="margin-bottom:1.25rem;">
            <?php foreach ($errors as $err): ?>
                <div class="flash-item flash-error">
                    <span><?= svg_icon('alert', 17) ?></span>
                    <span><?= e($err) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="panel-card form-panel-narrow">
        <form
            action="<?= e(base_url('admin/noodle_form.php' . ($editing ? '?id=' . (int) $editing['id'] : ''))) ?>"
            method="post"
            enctype="multipart/form-data"
            class="form-stack"
            novalidate
        >
            <?= csrf_field() ?>
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Nama Produk Mie Instan *</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    value="<?= e($formData['name']) ?>"
                    placeholder="Contoh: Indomie Mi Goreng Spesial"
                    maxlength="150"
                    required
                >
            </div>

            <div class="form-two-col">
                <div class="form-group">
                    <label for="brand">Brand / Merek *</label>
                    <input
                        type="text"
                        id="brand"
                        name="brand"
                        class="form-control"
                        value="<?= e($formData['brand']) ?>"
                        placeholder="Contoh: Indomie"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="flavor">Varian Rasa *</label>
                    <input
                        type="text"
                        id="flavor"
                        name="flavor"
                        class="form-control"
                        value="<?= e($formData['flavor']) ?>"
                        placeholder="Contoh: Mi Goreng Original"
                        maxlength="100"
                        required
                    >
                </div>
            </div>

            <div class="form-two-col">
                <div class="form-group">
                    <label for="origin_country">Negara Asal *</label>
                    <input
                        type="text"
                        id="origin_country"
                        name="origin_country"
                        class="form-control"
                        value="<?= e($formData['origin_country']) ?>"
                        placeholder="Contoh: Indonesia"
                        maxlength="80"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="price">Harga Perkiraan (Rupiah) *</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        class="form-control tabular"
                        value="<?= e($formData['price']) ?>"
                        min="0"
                        step="100"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="description">Deskripsi Singkat</label>
                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    placeholder="Jelaskan karakter bumbu, tekstur mie, atau keunikan varian ini..."
                ><?= e($formData['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="image">Foto Kemasan Mie (JPG, PNG, WebP · Maks 2 MB)</label>
                <?php if (!empty($editing['image'])): ?>
                    <div class="current-image-preview">
                        <?= render_noodle_visual($editing, 'pack-visual-sm') ?>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="remove_image" value="1">
                            <span>Hapus gambar saat ini</span>
                        </label>
                    </div>
                <?php endif; ?>
                <input
                    type="file"
                    id="image"
                    name="image"
                    class="form-control"
                    accept="image/jpeg,image/png,image/webp"
                >
                <small class="field-hint">
                    Jika dikosongkan, sistem menampilkan label tipografi produk otomatis.
                </small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= svg_icon('check', 16) ?> <?= $editing ? 'Simpan Perubahan' : 'Tambahkan Mie' ?>
                </button>
                <a href="<?= e(base_url('admin/noodles.php')) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
