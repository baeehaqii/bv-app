<?php

namespace App\Filament\Widgets;

use App\Models\GrossProfitTarget;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget: Target Gross Profit Summary
 *
 * Menampilkan target GP Bulanan, Quarterly, dan Tahunan.
 * Target Quarter & Tahunan dihitung otomatis dari akumulasi data bulanan.
 *
 * Permission: Assign widget permission via Filament Shield agar hanya
 * role Super Admin, C Level, dan Finance yang bisa melihatnya.
 */
class GrossProfitTargetWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        $quarter = GrossProfitTarget::quarterFromMonth($month);

        // ---- Target Bulanan ----
        $monthlyTarget = (float) GrossProfitTarget::forMonth($year, $month)->value('target_amount') ?? 0;

        // ---- Target Quarter (Q1/Q2/Q3/Q4) ----
        $quarterTarget = GrossProfitTarget::totalForQuarter($year, $quarter);

        // ---- Target Tahunan ----
        $yearTarget = GrossProfitTarget::totalForYear($year);

        // ---- Label bulan & quarter ----
        $monthLabel = $now->translatedFormat('F Y');
        $quarterLabel = "Q{$quarter} {$year}";
        $yearLabel = (string) $year;

        // Helper format rupiah
        $fmt = fn(float $val): string => 'Rp ' . number_format($val, 0, ',', '.');

        return [
            Stat::make("🎯 Target GP Bulan Ini ({$monthLabel})", $fmt($monthlyTarget))
                ->description($monthlyTarget > 0 ? 'Target sudah diset' : 'Belum diset — silakan input di menu Finance')
                ->descriptionIcon($monthlyTarget > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($monthlyTarget > 0 ? 'success' : 'warning'),

            Stat::make("📊 Target GP Quarter ({$quarterLabel})", $fmt($quarterTarget))
                ->description('Akumulasi target ' . implode('+', array_map(
                    fn($m) => Carbon::createFromDate($year, $m, 1)->translatedFormat('M'),
                    GrossProfitTarget::quarterMonths($quarter)
                )))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make("🏆 Target GP Tahunan ({$yearLabel})", $fmt($yearTarget))
                ->description('Total target dari 12 bulan di tahun ' . $yearLabel)
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }
}
