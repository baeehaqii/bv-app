<?php

namespace App\Filament\Widgets;

use App\Models\GrossProfitTarget;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget: Sales Target Sync Status vs Finance
 *
 * Displayed in the header of the Sales Target page.
 * Shows whether the total individual sales targets match
 * the Finance Deal Revenue Target set for the current month.
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

        // ── Finance target for current month ──────────────────────────────
        $financeTarget = GrossProfitTarget::dealRevenueForMonth($year, $month);

        // ── Total individual sales targets for current month ──────────────
        $salesTotal = (float) SalesTarget::where('year', $year)
            ->where('month', $month)
            ->sum('target_amount');

        // ── Difference ───────────────────────────────────────────────────
        $diff      = $financeTarget - $salesTotal;
        $isAligned = $financeTarget > 0 && abs($diff) < 1;
        $isOver    = $diff < 0;

        $monthLabel = $now->translatedFormat('F Y');

        // Stat 1: Finance Target Deal Revenue this month
        $financeDesc = $financeTarget > 0
            ? 'Company revenue target set by Finance'
            : 'Not set — please input via Finance menu';

        // Stat 2: Total individual sales targets
        $salesCount = SalesTarget::where('year', $year)->where('month', $month)->count();
        $salesDesc  = $salesCount > 0
            ? "From {$salesCount} sales person(s)"
            : 'No individual target has been set';

        // Stat 3: Sync status
        if ($financeTarget <= 0) {
            $syncLabel = 'Finance target not set';
            $syncColor = 'warning';
            $syncIcon  = 'heroicon-m-exclamation-circle';
            $syncDesc  = 'Set Finance target first';
        } elseif ($isAligned) {
            $syncLabel = 'Perfectly distributed';
            $syncColor = 'success';
            $syncIcon  = 'heroicon-m-check-circle';
            $syncDesc  = 'All sales targets match Finance target';
        } elseif ($isOver) {
            $syncLabel = $fmt(abs($diff)) . ' over target';
            $syncColor = 'danger';
            $syncIcon  = 'heroicon-m-arrow-trending-up';
            $syncDesc  = 'Individual targets exceed Finance target';
        } else {
            $syncLabel = $fmt($diff) . ' undistributed';
            $syncColor = 'warning';
            $syncIcon  = 'heroicon-m-arrow-trending-down';
            $syncDesc  = 'Gap must be adjusted with Finance target';
        }

        return [
            Stat::make("🏦 Finance Target Revenue ({$monthLabel})", $fmt($financeTarget))
                ->description($financeDesc)
                ->descriptionIcon($financeTarget > 0 ? 'heroicon-m-building-office' : 'heroicon-m-exclamation-circle')
                ->color($financeTarget > 0 ? 'info' : 'warning'),

            Stat::make("👥 Total Individual Sales Target", $fmt($salesTotal))
                ->description($salesDesc)
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make("📊 Sync Status", $syncLabel)
                ->description($syncDesc)
                ->descriptionIcon($syncIcon)
                ->color($syncColor)
                ->url(route('filament.office.resources.target-finance.index'))
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
