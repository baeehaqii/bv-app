<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Semua angka rumus Media Plan Internal yang bisa diatur dari UI.
 * Acuan: sheet `[INT] ... - KOL List.xlsx`.
 *
 *   kolom AC  Rounded  = ROUNDUP(AB, -5)   → rounding_step 100.000, mode "up"
 *   kolom AA  MU**     = Z / 0.5           → default_margin_percent 50
 *   kolom I   Tier     = IF berjenjang     → tier_thresholds
 *
 * Tabelnya cuma berisi SATU baris. `current()` mengembalikan baris itu, atau
 * instance kosong berisi default kolom kalau tabelnya belum diisi — supaya
 * perhitungan tetap jalan di instalasi baru dan di test.
 */
class MediaPlanCalcSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'rounding_step' => 'decimal:2',
        'default_margin_percent' => 'decimal:2',
        'max_margin_percent' => 'decimal:2',
        'tier_thresholds' => 'array',
    ];

    /**
     * Default kolom — satu-satunya tempat angka ini ditulis di kode.
     *
     * `default_margin_percent` & `max_margin_percent` sengaja TIDAK ada di
     * halaman Masterdata: margin bisnisnya diatur di Master Margin (yang selalu
     * punya baris tanpa batas), dua kolom ini cuma pengaman kalau tak ada baris
     * yang cocok dan penjaga agar pembagi (1 - margin) tidak nol.
     */
    protected $attributes = [
        'rounding_step' => 100000,
        'rounding_mode' => 'up',
        'default_margin_percent' => 50,
        'max_margin_percent' => 99,
    ];

    const ROUNDING_MODES = [
        'up' => 'Ke atas — ROUNDUP (sesuai sheet KOL List)',
        'nearest' => 'Ke terdekat — ROUND',
        'down' => 'Ke bawah — ROUNDDOWN',
    ];

    /**
     * Ambang Tier bawaan — gabungan dua skema yang dulu terpisah, dipilih agar
     * tidak ada band yang hilang:
     *   "Celebrity" (≥4jt) dari kolom I sheet KOL List;
     *   "Mini" (<1rb) dari DataKol & service scraping.
     * Sisanya sama di kedua skema. Berlaku untuk SEMUA modul (Media Plan
     * Internal, KOL Data, hasil scraping) — dulu masing-masing punya versi
     * sendiri, dan Threads bahkan memakai huruf kecil + band "mid".
     */
    const DEFAULT_TIERS = [
        ['label' => 'Celebrity', 'min_followers' => 4_000_000],
        ['label' => 'Mega', 'min_followers' => 1_000_000],
        ['label' => 'Macro', 'min_followers' => 100_000],
        ['label' => 'Micro', 'min_followers' => 10_000],
        ['label' => 'Nano', 'min_followers' => 1_000],
        ['label' => 'Mini', 'min_followers' => 0],
    ];

    /** Warna badge per peringkat tier — ikut urutan, bukan nama, agar label buatan sendiri tetap dapat warna. */
    private const BADGE_COLORS = ['success', 'warning', 'primary', 'info', 'gray', 'gray'];

    private const BADGE_ICONS = [
        'heroicon-o-star', 'heroicon-o-fire', 'heroicon-o-sparkles',
        'heroicon-o-light-bulb', 'heroicon-o-user', 'heroicon-o-user',
    ];

    /**
     * Dipanggil sekali per baris budget saat KOL List dirender; tanpa cache
     * per-request itu ribuan query ke tabel satu baris.
     */
    private static ?self $current = null;

    protected static function booted(): void
    {
        static::saved(fn() => self::$current = null);
        static::deleted(fn() => self::$current = null);
    }

    public static function current(): self
    {
        return self::$current ??= static::query()->first() ?? new static;
    }

    /** Dipakai test & seeder yang menyuntik nilai di tengah jalan. */
    public static function forgetCached(): void
    {
        self::$current = null;
    }

    public function tiers(): array
    {
        $tiers = $this->tier_thresholds ?: self::DEFAULT_TIERS;

        // Band terbesar harus dicek duluan, apa pun urutan input dari UI.
        usort($tiers, fn($a, $b) => ($b['min_followers'] ?? 0) <=> ($a['min_followers'] ?? 0));

        return $tiers;
    }

    /** Kolom I: Tier dari jumlah follower. */
    public function tierFor(int $followers): string
    {
        foreach ($this->tiers() as $tier) {
            if ($followers >= (int) ($tier['min_followers'] ?? 0)) {
                return (string) $tier['label'];
            }
        }

        return 'Nano';
    }

    /**
     * Tier + batas atasnya: ['Mega' => [1000000, 3999999], ...]. Batas atas
     * diturunkan dari ambang band di atasnya, jadi tak ada angka kembar yang
     * bisa jadi tidak sinkron.
     *
     * @return array<string, array{0:int, 1:?int}>
     */
    public function tierRanges(): array
    {
        $ranges = [];
        $atas = null;

        foreach ($this->tiers() as $tier) {
            $min = (int) ($tier['min_followers'] ?? 0);
            $ranges[(string) $tier['label']] = [$min, $atas === null ? null : $atas - 1];
            $atas = $min;
        }

        return $ranges;
    }

    /** Pilihan filter siap pakai: 'Macro' => 'Macro (100K–999K)'. */
    public function tierOptions(): array
    {
        $ringkas = function (int $n): string {
            return match (true) {
                $n >= 1_000_000 => rtrim(rtrim(number_format($n / 1_000_000, 1, '.', ''), '0'), '.').'M',
                $n >= 1_000 => rtrim(rtrim(number_format($n / 1_000, 1, '.', ''), '0'), '.').'K',
                default => (string) $n,
            };
        };

        $options = [];

        foreach ($this->tierRanges() as $label => [$min, $max]) {
            $options[$label] = $max === null
                ? "{$label} ({$ringkas($min)}+)"
                : "{$label} ({$ringkas($min)}–{$ringkas($max)})";
        }

        return $options;
    }

    public function tierBadgeColor(?string $label): string
    {
        return self::BADGE_COLORS[$this->tierRank($label)] ?? 'gray';
    }

    public function tierBadgeIcon(?string $label): string
    {
        return self::BADGE_ICONS[$this->tierRank($label)] ?? 'heroicon-o-user';
    }

    private function tierRank(?string $label): int
    {
        $labels = array_map(fn($t) => (string) $t['label'], $this->tiers());
        $index = array_search((string) $label, $labels, true);

        return $index === false ? PHP_INT_MAX : $index;
    }

    /** Kolom AC: pembulatan harga jual. */
    public function roundPrice(float $price): float
    {
        $step = (float) $this->rounding_step;

        if ($price <= 0) {
            return 0;
        }

        if ($step <= 0) {
            return $price; // pembulatan dimatikan dari UI
        }

        return match ($this->rounding_mode) {
            'down' => floor($price / $step) * $step,
            'nearest' => round($price / $step) * $step,
            default => ceil($price / $step) * $step,
        };
    }

    /** Margin dijaga di rentang wajar sebelum dipakai sebagai pembagi (1 - m). */
    public function clampMargin(float $percent): float
    {
        return min(max($percent, 0), (float) $this->max_margin_percent);
    }

    /** Kolom AA: MU** = cost / (1 - margin). */
    public function applyMargin(float $cost, float $marginPercent): float
    {
        $decimal = $this->clampMargin($marginPercent) / 100;

        return $decimal >= 1 ? $cost : $cost / (1 - $decimal);
    }
}
