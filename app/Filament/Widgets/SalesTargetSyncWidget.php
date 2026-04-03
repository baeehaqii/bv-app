<?php

namespace App\Filament\Widgets;

use App\Models\GrossProfitTarget;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget: Sinkronisasi Target Sales vs Finance
 *
 * Ditampilkan di header halaman Target Per Sales.
 * Memperlihatkan apakah total target individual sales sudah sesuai
 * dengan Target Deal Revenue yang diset Finance untuk bulan berjalan.
 */
class SalesTargetSyncWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now   = Carbon::now();
        $year  = $now->year;
        $month = $now->month;

        $fmt = fn(float $val): string => 'Rp ' . number_format($val, 0, ',', '.');

        // ── Finance target untuk bulan ini ──────────────────────────────
        $financeTarget = GrossProfitTarget::dealRevenueForMonth($year, $month);

        // ── Total semua target individual sales untuk bulan ini ─────────
        $salesTotal = (float) SalesTarget::where('year', $year)
            ->where('month', $month)
            ->sum('target_amount');

        // ── Selisih ─────────────────────────────────────────────────────
        $diff      = $financeTarget - $salesTotal;
        $isAligned = $financeTarget > 0 && abs($diff) < 1;
        $isOver    = $diff < 0;

        $monthLabel = $now->translatedFormat('F Y');

        // Stat 1: Finance Target Deal Revenue bulan ini
        $financeDesc = $financeTarget > 0
            ? 'Target omset perusahaan yang diset Finance'
            : 'Belum diset — input di menu Finance';

        // Stat 2: Total target sales individual
        $salesCount = SalesTarget::where('year', $year)->where('month', $month)->count();
        $salesDesc  = $salesCount > 0
            ? "Dari {$salesCount} sales person"
            : 'Belum ada target individual yang diset';

        // Stat 3: Status sinkronisasi
        if ($financeTarget <= 0) {
            $syncLabel = 'Finance target belum diset';
            $syncColor = 'warning';
            $syncIcon  = 'heroicon-m-exclamation-circle';
        } elseif ($isAligned) {
            $syncLabel = 'Terdistribusi sempurna';
            $syncColor = 'success';
            $syncIcon  = 'heroicon-m-check-circle';
        } elseif ($isOver) {
            $syncLabel = $fmt(abs($diff)) . ' melebihi target';
            $syncColor = 'danger';
            $syncIcon  = 'heroicon-m-arrow-trending-up';
        } else {
            $syncLabel = $fmt($diff) . ' belum terdistribusi';
            $syncColor = 'warning';
            $syncIcon  = 'heroicon-m-arrow-trending-down';
        }

        return [
            Stat::make("🏦 Finance Target Revenue ({$monthLabel})", $fmt($financeTarget))
                ->description($financeDesc)
                ->descriptionIcon($financeTarget > 0 ? 'heroicon-m-building-office' : 'heroicon-m-exclamation-circle')
                ->color($financeTarget > 0 ? 'info' : 'warning'),

            Stat::make("👥 Total Target Sales Individual", $fmt($salesTotal))
                ->description($salesDesc)
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make("📊 Status Sinkronisasi", $syncLabel)
                ->description($isAligned
                    ? 'Semua target sales sudah sesuai dengan Finance'
                    : ($financeTarget > 0 ? 'Selisih harus disesuaikan' : 'Set Finance target terlebih dahulu'))
                ->descriptionIcon($syncIcon)
                ->color($syncColor),
        ];
    }
}
