<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (current_user() !== null) {
    redirect('index.php');
}

$error = null;
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $waitSeconds = check_login_rate_limit();
    if ($waitSeconds !== null) {
        $minutes = (int) ceil($waitSeconds / 60);
        $error = "Terlalu banyak percobaan login gagal. Silakan tunggu sekitar {$minutes} menit sebelum mencoba kembali.";
    } else {
        $identifier = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($identifier === '' || $password === '') {
            $error = 'Username atau password salah.';
            record_login_failure();
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE username = :u1 OR email = :u2 LIMIT 1');
            $stmt->execute([
                ':u1' => $identifier,
                ':u2' => $identifier,
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, (string) $user['password_hash'])) {
                session_regenerate_id(true);
                clear_login_failures();
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                flash('success', 'Selamat datang kembali, ' . $user['username'] . '!');

                $target = $_SESSION['redirect_after_login'] ?? '';
                unset($_SESSION['redirect_after_login']);

                if (is_string($target) && $target !== '' && str_starts_with($target, '/') && !str_starts_with($target, '//')) {
                    header('Location: ' . $target);
                    exit;
                }

                if (($user['role'] ?? '') === 'admin') {
                    redirect('admin/index.php');
                }
                redirect('index.php');
            } else {
                record_login_failure();
                $error = 'Username atau password salah.';
            }
        }
    }
}

$pageTitle = 'Login Pengguna';
include __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container section-block">
    <section class="auth-card">
        <h1>Masuk ke Akun Anda</h1>
        <p class="muted-copy">Login untuk memberi penilaian 4 aspek pada mie instan dan menyimpan tierlist pribadi.</p>

        <?php if ($error !== null): ?>
            <div class="flash-stack" role="alert">
                <div class="flash-item flash-error">
                    <span><?= svg_icon('alert', 17) ?></span>
                    <span><?= e($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= e(base_url('login.php')) ?>" method="post" class="form-stack" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username">Username atau Email</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    value="<?= e($identifier) ?>"
                    placeholder="Masukkan username atau email"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Masukkan password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </div>
        </form>

        <p class="auth-switch">
            Belum punya akun? <a href="<?= e(base_url('register.php')) ?>">Daftar di sini</a>
        </p>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
