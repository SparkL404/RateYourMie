<?php
declare(strict_types=1);

/**
 * Escape string untuk output aman ke HTML (anti-XSS).
 */
function e(mixed $s): string
{
    if ($s === null) {
        return '';
    }
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * Menghitung base URL relatif agar navigasi dan aset berfungsi baik di root maupun subfolder.
 */
function base_url(string $path = ''): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if (str_ends_with($dir, '/admin') || str_ends_with($dir, '/api')) {
        $dir = rtrim(str_replace('\\', '/', dirname($dir)), '/');
    }
    $base = ($dir === '' || $dir === '.') ? '' : $dir;
    $cleanPath = ltrim($path, '/');
    return $cleanPath === '' ? ($base ?: '/') : $base . '/' . $cleanPath;
}

/**
 * Redirect HTTP dan hentikan eksekusi.
 */
function redirect(string $path): never
{
    if (!preg_match('#^https?://#i', $path)) {
        $path = base_url($path);
    }
    header('Location: ' . $path);
    exit;
}

/**
 * Simpan atau ambil pesan flash session.
 */
function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $_SESSION['flashes'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Mengambil dan mengosongkan seluruh flash message.
 */
function get_flashes(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['flashes'])) {
        return [];
    }
    $flashes = $_SESSION['flashes'];
    unset($_SESSION['flashes']);
    return $flashes;
}

/**
 * Mengirim respons JSON standar untuk endpoint api/ dan menghentikan script.
 */
function json_response(bool $ok, mixed $dataOrError = null, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    if ($ok) {
        echo json_encode([
            'ok'   => true,
            'data' => $dataOrError,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'ok'    => false,
            'error' => is_string($dataOrError) ? $dataOrError : 'Terjadi kesalahan permintaan.',
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/**
 * Format harga ke Rupiah.
 */
function format_rupiah(int|float $price): string
{
    return 'Rp ' . number_format((float) $price, 0, ',', '.');
}

/**
 * Format skor numerik 2 desimal dengan koma khas Indonesia atau titik tabular.
 */
function format_score(float|int|string|null $score, int $decimals = 2): string
{
    if ($score === null || $score === '') {
        return '-';
    }
    return number_format(round((float) $score, 2), $decimals, '.', '');
}

/**
 * Render ikon SVG segaris dengan ketebalan garis konsisten.
 */
function svg_icon(string $name, int $size = 18): string
{
    $s = (int) $size;
    $attrs = sprintf(
        'width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="svg-icon"',
        $s,
        $s
    );

    return match ($name) {
        'search' => "<svg $attrs><circle cx=\"11\" cy=\"11\" r=\"8\"/><path d=\"m21 21-4.3-4.3\"/></svg>",
        'star'   => "<svg width=\"$s\" height=\"$s\" viewBox=\"0 0 24 24\" fill=\"currentColor\" stroke=\"currentColor\" stroke-width=\"1.5\" aria-hidden=\"true\" class=\"svg-icon\"><polygon points=\"12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2\"/></svg>",
        'bowl'   => "<svg $attrs><path d=\"M4 11h16a1 1 0 0 1 1 1v.5a8.5 8.5 0 0 1-17 0V12a1 1 0 0 1 1-1Z\"/><path d=\"M6 20h12\"/><path d=\"M9 3v5\"/><path d=\"M13 3v5\"/><path d=\"M17 5v3\"/></svg>",
        'layers' => "<svg $attrs><polygon points=\"12 2 2 7 12 12 22 7 12 2\"/><polyline points=\"2 17 12 22 22 17\"/><polyline points=\"2 12 12 17 22 12\"/></svg>",
        'award'  => "<svg $attrs><circle cx=\"12\" cy=\"8\" r=\"6\"/><path d=\"M15.477 12.89 17 22l-5-3-5 3 1.523-9.11\"/></svg>",
        'user'   => "<svg $attrs><path d=\"M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2\"/><circle cx=\"12\" cy=\"7\" r=\"4\"/></svg>",
        'shield' => "<svg $attrs><path d=\"M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z\"/></svg>",
        'plus'   => "<svg $attrs><line x1=\"12\" y1=\"5\" x2=\"12\" y2=\"19\"/><line x1=\"5\" y1=\"12\" x2=\"19\" y2=\"12\"/></svg>",
        'trash'  => "<svg $attrs><polyline points=\"3 6 5 6 21 6\"/><path d=\"M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2\"/></svg>",
        'edit'   => "<svg $attrs><path d=\"M12 20h9\"/><path d=\"M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z\"/></svg>",
        'check'  => "<svg $attrs><polyline points=\"20 6 9 17 4 12\"/></svg>",
        'alert'  => "<svg $attrs><circle cx=\"12\" cy=\"12\" r=\"10\"/><line x1=\"12\" y1=\"8\" x2=\"12\" y2=\"12\"/><line x1=\"12\" y1=\"16\" x2=\"12.01\" y2=\"16\"/></svg>",
        'grip'   => "<svg $attrs><circle cx=\"9\" cy=\"6\" r=\"1\"/><circle cx=\"15\" cy=\"6\" r=\"1\"/><circle cx=\"9\" cy=\"12\" r=\"1\"/><circle cx=\"15\" cy=\"12\" r=\"1\"/><circle cx=\"9\" cy=\"18\" r=\"1\"/><circle cx=\"15\" cy=\"18\" r=\"1\"/></svg>",
        'filter' => "<svg $attrs><polygon points=\"22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3\"/></svg>",
        'arrow-left' => "<svg $attrs><line x1=\"19\" y1=\"12\" x2=\"5\" y2=\"12\"/><polyline points=\"12 19 5 12 12 5\"/></svg>",
        default  => "<svg $attrs><circle cx=\"12\" cy=\"12\" r=\"9\"/></svg>",
    };
}

/**
 * Menampilkan gambar kemasan mie (jika ada di uploads/) atau kartu tipografi kemasan minimalis.
 */
function render_noodle_visual(array $noodle, string $sizeClass = 'pack-visual-md'): string
{
    $imageFile = trim((string) ($noodle['image'] ?? ''));
    if ($imageFile !== '') {
        $fullPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($imageFile);
        if (is_file($fullPath)) {
            $src = e(base_url('uploads/' . basename($imageFile)));
            $alt = e($noodle['name'] ?? 'Kemasan Mie');
            return '<div class="noodle-photo-wrap ' . e($sizeClass) . '"><img src="' . $src . '" alt="' . $alt . '" loading="lazy" class="noodle-photo"></div>';
        }
    }

    $id = (int) ($noodle['id'] ?? 1);
    $brand = e((string) ($noodle['brand'] ?? 'Mie Instan'));
    $flavor = e((string) ($noodle['flavor'] ?? 'Varian Klasik'));
    $origin = e(strtoupper(substr((string) ($noodle['origin_country'] ?? 'IDN'), 0, 3)));

    $accents = [
        '#C2301A',
        '#15693F',
        '#1D56A3',
        '#B45309',
        '#4A453E',
        '#8A3B12',
    ];
    $accent = $accents[$id % count($accents)];

    return sprintf(
        '<div class="noodle-monograph %s" style="--mono-accent:%s" role="img" aria-label="Label produk %s">'
        . '<span class="mono-accent-bar" aria-hidden="true"></span>'
        . '<strong class="mono-brand">%s</strong>'
        . '<span class="mono-flavor">%s</span>'
        . '</div>',
        e($sizeClass),
        $accent,
        e($noodle['name'] ?? 'Mie Instan'),
        $brand,
        $flavor
    );
}
