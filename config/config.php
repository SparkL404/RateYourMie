<?php
declare(strict_types=1);

// Konfigurasi koneksi database (XAMPP / Laragon)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'rating_mie');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// Ambang tier komunitas bawaan (skor akhir dibulatkan 2 desimal, batas bawah inklusif)
define('TIER_THRESHOLDS', [
    'S' => 4.50,
    'A' => 4.00,
    'B' => 3.50,
    'C' => 3.00,
    'D' => 2.00,
    'F' => 0.00,
]);

// Syarat minimal penilai agar mie masuk Tierlist Komunitas & Top 10
define('MIN_RATERS', 3);

// Batas maksimal tierlist pribadi per pengguna
define('MAX_TIERLISTS', 10);

// Konfigurasi upload gambar kemasan mie
define('UPLOAD_MAX_BYTES', 2097152); // 2 MB
define('UPLOAD_ALLOWED', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);
define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');

// Pembatasan keamanan (rate limit)
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 900); // 15 menit
define('RATING_MAX_PER_HOUR', 20);

// Nama situs
define('SITE_NAME', 'RateYourMie');
define('SITE_TAGLINE', 'Katalog Rating 4 Aspek & Papan Tierlist Mie Instan');
