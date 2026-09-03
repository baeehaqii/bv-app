<?php

namespace Database\Seeders;

use App\Models\BvSalesList;
use App\Models\GrossProfitTarget;
use App\Models\SalesTarget;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Target 2026 dari sheet "2026 Sales Target"
 * (docs/Sales Pipeline & Dashboard - Beyond Viral (1).xlsx).
 *
 * Dua lapis, sesuai yang ada di sheet:
 *   1. Booked Revenue per bulan → Target Finance (gross_profit_targets).
 *      Booked GP Target tidak diisi manual: model menghitungnya sendiri dari
 *      revenue x benchmark 31%, dan hasilnya sama persis dengan baris GP di sheet.
 *   2. Baris "Wina" dan "Vacant (Gerry)" → target per sales (sales_targets),
 *      ditautkan lewat email @bvnetwork.net, bukan lewat nama.
 *
 * Di sheet, Januari–Juni belum dipecah ke sales mana pun — hanya Juli–Desember.
 * Jadi seeder ini juga tidak mengarang pembagian untuk paruh pertama.
 */
class SalesTarget2026Seeder extends Seeder
{
    private const YEAR = 2026;

    private const BENCHMARK_PERCENT = 31;

    /** Baris "Booked Revenue" — total 9.717.419.355 setahun. */
    private const BOOKED_REVENUE = [
        1 => 40_000_000,
        2 => 77_419_355,
        3 => 500_000_000,
        4 => 400_000_000,
        5 => 750_000_000,
        6 => 1_100_000_000,
        7 => 1_100_000_000,
        8 => 1_000_000_000,
        9 => 1_150_000_000,
        10 => 1_150_000_000,
        11 => 1_200_000_000,
        12 => 1_250_000_000,
    ];

    /** Target per sales: email → bulan → nominal. Jumlahnya pas dengan Booked Revenue Jul–Des. */
    private const TARGET_PER_SALES = [
        'wina@bvnetwork.net' => [
            7 => 770_000_000,
            8 => 700_000_000,
            9 => 805_000_000,
            10 => 805_000_000,
            11 => 840_000_000,
            12 => 875_000_000,
        ],
        'gerry@bvnetwork.net' => [
            7 => 330_000_000,
            8 => 300_000_000,
            9 => 345_000_000,
            10 => 345_000_000,
            11 => 360_000_000,
            12 => 375_000_000,
        ],
    ];

    public function run(): void
    {
        foreach (self::BOOKED_REVENUE as $month => $revenue) {
            GrossProfitTarget::updateOrCreate(
                ['year' => self::YEAR, 'month' => $month],
                [
                    'target_deal_revenue' => $revenue,
                    'margin_benchmark_percent' => self::BENCHMARK_PERCENT,
                    'notes' => 'Sheet 2026 Sales Target',
                ],
            );
        }

        $this->command?->info(
            count(self::BOOKED_REVENUE) . ' bulan Target Finance ' . self::YEAR . ' ter-seed (Booked Revenue + GP ' . self::BENCHMARK_PERCENT . '%).'
        );

        foreach (self::TARGET_PER_SALES as $email => $months) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                // Bukan tugas seeder ini bikin akun — biar UserSeeder yang pegang itu.
                $this->command?->warn("Target dilewati: user {$email} belum ada. Jalankan UserSeeder dulu.");
                continue;
            }

            $salesList = BvSalesList::firstOrCreate(
                ['user_id' => $user->id],
                ['nama_sales' => $user->name],
            );

            foreach ($months as $month => $amount) {
                SalesTarget::updateOrCreate(
                    [
                        'bv_sales_list_id' => $salesList->id,
                        'year' => self::YEAR,
                        'month' => $month,
                    ],
                    [
                        'target_amount' => $amount,
                        'notes' => 'Sheet 2026 Sales Target',
                    ],
                );
            }

            $total = array_sum($months);
            $this->command?->info(
                "Target {$salesList->nama_sales} ({$email}): " . count($months)
                . ' bulan, total Rp ' . number_format($total, 0, ',', '.') . '.'
            );
        }
    }
}
