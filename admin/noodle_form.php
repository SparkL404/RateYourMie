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
$removeImage = false;
$formData = [
    'name'           => $editing['name'] ?? '',
    'brand'          => $editing['brand'] ?? '',
    'flavor'         => $editing['flavor'] ?? '',
    'origin_country' => $editing['origin_country'] ?? 'Indonesia',
    'price'          => isset($editing['price']) ? (string) $editing['price'] : '3200',
    'description'    => $editing['description'] ?? '',
];

$charLen = static fn(string $s): int => function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $formData['name'] = trim((string) ($_POST['name'] ?? ''));
    $formData['brand'] = trim((string) ($_POST['brand'] ?? ''));
    $formData['flavor'] = trim((string) ($_POST['flavor'] ?? ''));
    $formData['origin_country'] = trim((string) ($_POST['origin_country'] ?? ''));
    $formData['price'] = trim((string) ($_POST['price'] ?? ''));
    $formData['description'] = trim((string) ($_POST['description'] ?? ''));
    $removeImage = !empty($_POST['remove_image']);

    if ($formData['name'] === '' || $charLen($formData['name']) > 150) {
        $errors[] = 'Nama mie wajib diisi (maksimal 150 karakter).';
    }
    if ($formData['brand'] === '' || $charLen($formData['brand']) > 100) {
        $errors[] = 'Brand wajib diisi (maksimal 100 karakter).';
    }
    if ($formData['flavor'] === '' || $charLen($formData['flavor']) > 100) {
        $errors[] = 'Varian rasa wajib diisi (maksimal 100 karakter).';
    }
    if ($formData['origin_country'] === '' || $charLen($formData['origin_country']) > 80) {
        $errors[] = 'Negara asal wajib diisi (maksimal 80 karakter).';
    }
    if ($formData['price'] === '' || !ctype_digit($formData['price']) || (int) $formData['price'] < 0) {
        $errors[] = 'Harga wajib berupa bilangan bulat positif atau 0 (dalam Rupiah).';
    }

    $pendingUpload = null;
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
                } elseif (@getimagesize($tmpPath) === false) {
                    $errors[] = 'File yang diunggah bukan gambar yang valid.';
                } else {
                    $pendingUpload = [
                        'tmp'      => $tmpPath,
                        'filename' => bin2hex(random_bytes(16)) . '.' . $allowed[$mime],
                    ];
                }
            }
        }
    }

    $previousImage = $editing['image'] ?? null;
    $newImageFilename = $removeImage ? null : $previousImage;

    if (empty($errors) && $pendingUpload !== null) {
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        $destPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $pendingUpload['filename'];
        if (!move_uploaded_file($pendingUpload['tmp'], $destPath)) {
            $errors[] = 'Gagal menyimpan file gambar ke folder uploads.';
        } else {
            $newImageFilename = $pendingUpload['filename'];
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

        if (!empty($previousImage) && ($removeImage || $pendingUpload !== null)) {
            $oldPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename((string) $previousImage);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
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
            <p>Isi informasi produk mie instan dan tinjau pratinjau kartu publik di sebelah kanan.</p>
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

    <div class="editor-split-layout">
        <section class="panel-card">
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
                            <?= render_noodle_swatch($editing, 'swatch-md') ?>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="remove_image" value="1" <?= $removeImage ? 'checked' : '' ?>>
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
                        Jika dikosongkan, sistem menampilkan stempel monogram brand otomatis.
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

        <aside class="editor-preview-col" aria-label="Pratinjau Kartu Katalog">
            <div class="noodle-card editor-live-card">
                <div class="noodle-card-body">
                    <div class="noodle-card-head">
                        <span class="brand-swatch swatch-md" id="js-preview-swatch" style="background-color:#0F766E">IN</span>
                        <div class="noodle-head-copy">
                            <div class="noodle-card-meta">
                                <span id="js-preview-meta">Indomie · Indonesia</span>
                            </div>
                            <h2 class="noodle-card-title" id="js-preview-name">Nama Mie Instan</h2>
                        </div>
                        <div class="noodle-head-tier">
                            <span class="tier-badge tier-unranked">BARU</span>
                        </div>
                    </div>

                    <div class="noodle-card-sub">
                        <span class="flavor-pill" id="js-preview-flavor">Mi Goreng Original</span>
                        <span class="price-tag tabular" id="js-preview-price">Rp 3.200</span>
                    </div>

                    <p class="muted-copy" id="js-preview-desc" style="margin:0.25rem 0 0;">
                        Pratinjau tampilan kartu mie pada halaman katalog publik.
                    </p>
                </div>
            </div>
        </aside>
    </div>
</main>

<script>
(function () {
    var fName = document.getElementById('name');
    var fBrand = document.getElementById('brand');
    var fFlavor = document.getElementById('flavor');
    var fCountry = document.getElementById('origin_country');
    var fPrice = document.getElementById('price');
    var fDesc = document.getElementById('description');

    var pSwatch = document.getElementById('js-preview-swatch');
    var pMeta = document.getElementById('js-preview-meta');
    var pName = document.getElementById('js-preview-name');
    var pFlavor = document.getElementById('js-preview-flavor');
    var pPrice = document.getElementById('js-preview-price');
    var pDesc = document.getElementById('js-preview-desc');

    function initials(str) {
        var s = (str || '').trim();
        if (!s) return 'MI';
        var parts = s.split(/[\s\/\-]+/);
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
        }
        return s.slice(0, 2).toUpperCase();
    }

    function sync() {
        var name = (fName && fName.value.trim()) || 'Nama Mie Instan';
        var brand = (fBrand && fBrand.value.trim()) || 'Brand';
        var flavor = (fFlavor && fFlavor.value.trim()) || 'Varian Rasa';
        var country = (fCountry && fCountry.value.trim()) || 'Indonesia';
        var price = parseInt((fPrice && fPrice.value) || '0', 10) || 0;
        var desc = (fDesc && fDesc.value.trim()) || 'Pratinjau tampilan kartu mie pada halaman katalog publik.';

        if (pName) pName.textContent = name;
        if (pMeta) pMeta.textContent = brand + ' · ' + country;
        if (pFlavor) pFlavor.textContent = flavor;
        if (pPrice) pPrice.textContent = 'Rp ' + price.toLocaleString('id-ID');
        if (pSwatch) pSwatch.textContent = initials(brand);
        if (pDesc) pDesc.textContent = desc;
    }

    [fName, fBrand, fFlavor, fCountry, fPrice, fDesc].forEach(function (el) {
        if (el) el.addEventListener('input', sync);
    });
    sync();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
