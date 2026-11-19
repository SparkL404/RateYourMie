<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (current_user() !== null) {
    redirect('index.php');
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '' || strlen($username) < 3 || strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username wajib terdiri dari 3–50 karakter (huruf, angka, atau garis bawah).';
    }

    if ($email === '' || strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format alamat email tidak valid.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password wajib minimal 8 karakter.';
    } elseif ($password !== $passwordConfirm) {
        $errors[] = 'Konfirmasi password tidak cocok dengan password.';
    }

    if (empty($errors)) {
        $pdo = db();

        $checkStmt = $pdo->prepare('SELECT username, email FROM users WHERE username = :u OR email = :e LIMIT 1');
        $checkStmt->execute([
            ':u' => $username,
            ':e' => $email,
        ]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            if (strcasecmp((string) $existing['username'], $username) === 0) {
                $errors[] = 'Username tersebut sudah digunakan. Pilih username lain.';
            } else {
                $errors[] = 'Alamat email tersebut sudah terdaftar.';
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // Role dikunci sebagai 'user' di sisi server (FU-01)
            $insertStmt = $pdo->prepare('
                INSERT INTO users (username, email, password_hash, role, email_verified)
                VALUES (:u, :e, :p, :role, 0)
            ');
            $insertStmt->execute([
                ':u'    => $username,
                ':e'    => $email,
                ':p'    => $hash,
                ':role' => 'user',
            ]);

            flash('success', 'Pendaftaran berhasil! Silakan login dengan akun baru Anda.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Daftar Akun Baru';
$hideFooter = true;
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container section-block auth-page-main">
    <section class="auth-card">
        <h1>Daftar Akun Komunitas</h1>
        <p class="muted-copy">Buat akun untuk memberi rating 4 aspek, menulis ulasan, dan menyusun hingga <?= (int) MAX_TIERLISTS ?> tierlist pribadi.</p>

        <?php if (!empty($errors)): ?>
            <div class="flash-stack" role="alert">
                <?php foreach ($errors as $err): ?>
                    <div class="flash-item flash-error">
                        <span><?= svg_icon('alert', 17) ?></span>
                        <span><?= e($err) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= e(base_url('register.php')) ?>" method="post" class="form-stack" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    value="<?= e($username) ?>"
                    placeholder="contoh: pecinta_mie99"
                    minlength="3"
                    maxlength="50"
                    pattern="[a-zA-Z0-9_]+"
                    required
                    autocomplete="username"
                >
                <small class="field-hint">3–50 karakter, hanya huruf, angka, dan underscore (_).</small>
            </div>

            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="<?= e($email) ?>"
                    placeholder="nama@email.com"
                    maxlength="255"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Minimal 8 karakter"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="password_confirm">Konfirmasi Password</label>
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    class="form-control"
                    placeholder="Ulangi password"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Daftar Sekarang</button>
            </div>
        </form>

        <p class="auth-switch">
            Sudah punya akun? <a href="<?= e(base_url('login.php')) ?>">Login di sini</a>
        </p>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
