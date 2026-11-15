<?php
declare(strict_types=1);
?>
    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <strong><?= e(SITE_NAME) ?></strong>
                <span>Indeks 4 aspek (Rasa, Tekstur, Porsi, Harga) · Syarat tier komunitas min. <?= (int) (get_app_settings()['min_raters'] ?? MIN_RATERS) ?> penilai</span>
            </div>
            <div class="footer-links">
                <a href="<?= e(base_url('index.php')) ?>">Katalog</a>
                <a href="<?= e(base_url('community.php')) ?>">Tierlist Komunitas</a>
                <a href="<?= e(base_url('top10.php')) ?>">Top 10</a>
                <?php if (current_user() !== null): ?>
                    <a href="<?= e(base_url('tierlists.php')) ?>">Tierlist Saya</a>
                <?php else: ?>
                    <a href="<?= e(base_url('login.php')) ?>">Masuk</a>
                <?php endif; ?>
            </div>
        </div>
    </footer>
</body>
</html>
