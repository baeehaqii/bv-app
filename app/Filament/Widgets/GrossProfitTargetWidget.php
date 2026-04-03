<?php

namespace App\Filament\Widgets;

use App\Models\GrossProfitTarget;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget: Target Finance — Deal Revenue & Gross Profit
 *
 * Menampilkan 6 stats dalam 2 baris:
 *   Baris 1 ��� Target Deal Revenue : Bulanan | Quarter | Tahunan
 *   Baris 2 — Target Gross Profit : Bulanan | Quarter | Tahunan
 *
 * Target Quarter & Tahunan dihitung otomatis dari akumulasi data bulanan.
 */
class GrossProfitTargetWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now     = Carbon::now();
        $year    = $now->year;
        $month   = $now->month;
        $quarter = GrossProfitTarget::quarterFromMonth($month);

        $monthLabel   = $now->translatedFormat('F Y');
        $quarterLabel = "Q{$quarter} {$year}";
        $yearLabel    = (string) $year;

        $quarterMonthNames = implode('+', array_map(
            fn($m) => Carbon::createFromDate($year, $m, 1)->translatedFormat('M'),
            GrossProfitTarget::quarterMonths($quarter)
        ));

        $fmt = fn(float $val): string => 'Rp ' . number_format($val, 0, ',', '.');

        // ── Deal Revenue ─────────────────────────────────────────────────
        $monthlyRevenue  = GrossProfitTarget::dealRevenueForMonth($year, $month);
        $quarterRevenue  = (float) GrossProfitTarget::forQuarter($year, $quarter)->sum('target_deal_revenue');
        $yearRevenue     = GrossProfitTarget::dealRevenueForYear($year);

        // ── Gross Profit ──────────────────────────────────────────────────
        $monthlyGp   = (float) GrossProfitTarget::forMonth($year, $month)->value('target_amount') ?? 0;
        $quarterGp   = GrossProfitTarget::totalForQuarter($year, $quarter);
        $yearGp      = GrossProfitTarget::totalForYear($year);

        $notSetDesc  = 'Belum diset — input di menu Finance';
        $setDesc     = fn(string $ctx) => "Target {$ctx} sudah diset";

        return [
            // ── Baris 1: Deal Revenue ────────────────────────────────────
            Stat::make("💰 Deal Revenue Bulan Ini ({$monthLabel})", $fmt($monthlyRevenue))
                ->description($monthlyRevenue > 0 ? $setDesc('bulanan') : $notSetDesc)
                ->descriptionIcon($monthlyRevenue > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($monthlyRevenue > 0 ? 'info' : 'warning'),

            Stat::make("📈 Deal Revenue Quarter ({$quarterLabel})", $fmt($quarterRevenue))
                ->description('Akumulasi target ' . $quarterMonthNames)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make("🏦 Deal Revenue Tahunan ({$yearLabel})", $fmt($yearRevenue))
                ->description('Total target deal revenue tahun ' . $yearLabel)
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),

            // ── Baris 2: Gross Profit ────────────────────────────────────
            Stat::make("🎯 Gross Profit Bulan Ini ({$monthLabel})", $fmt($monthlyGp))
                ->description($monthlyGp > 0 ? $setDesc('bulanan') : $notSetDesc)
                ->descriptionIcon($monthlyGp > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($monthlyGp > 0 ? 'success' : 'warning'),

            Stat::make("📊 Gross Profit Quarter ({$quarterLabel})", $fmt($quarterGp))
                ->description('Akumulasi target ' . $quarterMonthNames)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make("🏆 Gross Profit Tahunan ({$yearLabel})", $fmt($yearGp))
                ->description('Total target gross profit tahun ' . $yearLabel)
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
        ];
    }
}
