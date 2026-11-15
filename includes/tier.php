<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Mengonversi skor rata-rata ke huruf tier (S, A, B, C, D, F).
 * Membandingkan nilai yang sudah dibulatkan 2 desimal dengan batas bawah inklusif.
 */
function score_to_tier(float $score): string
{
    $rounded = round($score, 2);
    $settings = get_app_settings();
    $thresholds = $settings['tier_thresholds'] ?? TIER_THRESHOLDS;

    if ($rounded >= (float) ($thresholds['S'] ?? 4.50)) {
        return 'S';
    }
    if ($rounded >= (float) ($thresholds['A'] ?? 4.00)) {
        return 'A';
    }
    if ($rounded >= (float) ($thresholds['B'] ?? 3.50)) {
        return 'B';
    }
    if ($rounded >= (float) ($thresholds['C'] ?? 3.00)) {
        return 'C';
    }
    if ($rounded >= (float) ($thresholds['D'] ?? 2.00)) {
        return 'D';
    }
    return 'F';
}

/**
 * Daftar seluruh tier valid secara berurutan.
 */
function all_tiers(): array
{
    return ['S', 'A', 'B', 'C', 'D', 'F'];
}

/**
 * Metadata tampilan dan rentang skor untuk setiap tier.
 */
function tier_meta(string $tier): array
{
    $t = strtoupper($tier);
    return match ($t) {
        'S' => [
            'tier'  => 'S',
            'name'  => 'Istimewa (Legendaris)',
            'range' => '4.50 – 5.00',
            'class' => 'tier-s',
        ],
        'A' => [
            'tier'  => 'A',
            'name'  => 'Sangat Direkomendasikan',
            'range' => '4.00 – 4.49',
            'class' => 'tier-a',
        ],
        'B' => [
            'tier'  => 'B',
            'name'  => 'Mantap & Layak Beli',
            'range' => '3.50 – 3.99',
            'class' => 'tier-b',
        ],
        'C' => [
            'tier'  => 'C',
            'name'  => 'Standar Harian',
            'range' => '3.00 – 3.49',
            'class' => 'tier-c',
        ],
        'D' => [
            'tier'  => 'D',
            'name'  => 'Kurang Memuaskan',
            'range' => '2.00 – 2.99',
            'class' => 'tier-d',
        ],
        default => [
            'tier'  => 'F',
            'name'  => 'Tidak Direkomendasikan',
            'range' => '< 2.00',
            'class' => 'tier-f',
        ],
    };
}
