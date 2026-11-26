<?php
declare(strict_types=1);
?>
<?php if (empty($hideFooter)): ?>
            <footer class="site-footer">
                <div class="container footer-inner">
                    <div class="footer-brand">
                        <strong><?= e(SITE_NAME) ?></strong>
                        <span>Indeks 4 aspek (Rasa, Tekstur, Porsi, Harga) · Syarat tier komunitas min. <?= (int) (get_app_settings()['min_raters'] ?? MIN_RATERS) ?> penilai</span>
                    </div>
                    <div class="footer-links">
                        <a href="<?= e(base_url('index.php')) ?>">Katalog</a>
                        <a href="<?= e(base_url('pages/community.php')) ?>">Tierlist Komunitas</a>
                        <a href="<?= e(base_url('pages/top10.php')) ?>">Top 10</a>
                        <?php if (current_user() !== null): ?>
                            <a href="<?= e(base_url('pages/tierlists.php')) ?>">Tierlist Saya</a>
                        <?php else: ?>
                            <a href="<?= e(base_url('auth/login.php')) ?>">Masuk</a>
                        <?php endif; ?>
                    </div>
                </div>
            </footer>
<?php endif; ?>
        </div>
    </div>

    <script>
    (function () {
        document.querySelectorAll('.js-theme-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var isDark = document.documentElement.classList.toggle('dark');
                try {
                    localStorage.setItem('rym_theme', isDark ? 'dark' : 'light');
                } catch (e) {}
            });
        });

        // Konfirmasi hapus dua langkah inline tanpa window.confirm
        document.querySelectorAll('.js-inline-confirm-form').forEach(function (form) {
            var triggerBtn = form.querySelector('.js-confirm-trigger');
            var stepBox = form.querySelector('.js-confirm-step');
            var cancelBtn = form.querySelector('.js-confirm-cancel');
            if (!triggerBtn || !stepBox) return;

            triggerBtn.addEventListener('click', function () {
                triggerBtn.hidden = true;
                stepBox.hidden = false;
            });

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    stepBox.hidden = true;
                    triggerBtn.hidden = false;
                });
            }
        });
    })();
    </script>
</body>
</html>
