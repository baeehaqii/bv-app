<?php

namespace App\Filament\Widgets;

use App\Filament\Traits\HasDashboardFilter;
use App\Models\BvCashflow;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends BaseWidget
{
    use HasDashboardFilter;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function getHeading(): ?string
    {
        return 'Performa Periode Berjalan';
    }

    protected function getStats(): array
    {
        $range = $this->dashboardDateRange();
        $prevRange = $this->dashboardPreviousRange();

        // ── Revenue (income) ─────────────────────────────────────────────
        $revenue = (float) BvCashflow::where('type', 'income')->whereBetween('transaction_date', [$range['start'], $range['end']])->sum('amount');
        $prevRevenue = (float) BvCashflow::where('type', 'income')->whereBetween('transaction_date', [$prevRange['start'], $prevRange['end']])->sum('amount');

        // ── Expense ──────────────────────────────────────────────────────
        $expense = (float) BvCashflow::where('type', 'expense')->whereBetween('transaction_date', [$range['start'], $range['end']])->sum('amount');
        $prevExpense = (float) BvCashflow::where('type', 'expense')->whereBetween('transaction_date', [$prevRange['start'], $prevRange['end']])->sum('amount');

        // ── Gross Profit & Margin ────────────────────────────────────────
        $grossProfit = $revenue - $expense;
        $prevGrossProfit = $prevRevenue - $prevExpense;
        $profitMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
        $prevProfitMargin = $prevRevenue > 0 ? ($prevGrossProfit / $prevRevenue) * 100 : 0;

        // ── % change ────────────────────────────────────────────────────
        $pct = fn (float $cur, float $prev): float => $prev != 0
            ? round((($cur - $prev) / abs($prev)) * 100, 1)
            : 0;

        $revChange = $pct($revenue, $prevRevenue);
        $gpChange = $pct($grossProfit, $prevGrossProfit);
        $marginChange = $pct($profitMargin, $prevProfitMargin);

        $desc = fn (float $change, string $suffix): string => ($change >= 0 ? '+' : '').number_format($change, 1)."% {$suffix}";
        $fmt = fn (float $v): string => 'Rp '.number_format($v, 0, ',', '.');

        // ── Sparkline (up to 7 daily points within range) ────────────────
        $days = (int) $range['start']->diffInDays($range['end']) + 1;
        $pointCount = min($days, 7);
        $revenueChart = [];
        $gpChart = [];

        for ($i = $pointCount - 1; $i >= 0; $i--) {
            $dayStart = $range['end']->copy()->subDays($i)->startOfDay();
            $dayEnd = $range['end']->copy()->subDays($i)->endOfDay();
            $r = (float) BvCashflow::where('type', 'income')->whereBetween('transaction_date', [$dayStart, $dayEnd])->sum('amount');
            $e = (float) BvCashflow::where('type', 'expense')->whereBetween('transaction_date', [$dayStart, $dayEnd])->sum('amount');
            $revenueChart[] = $r / 1_000_000;
            $gpChart[] = ($r - $e) / 1_000_000;
        }

        return [
            Stat::make("Revenue {$range['label']}", $fmt($revenue))
                ->description($desc($revChange, $range['comparison']))
                ->descriptionIcon($revChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revChange >= 0 ? 'success' : 'danger')
                ->chart($revenueChart),

            Stat::make("Gross Profit {$range['label']}", $fmt($grossProfit))
                ->description($desc($gpChange, $range['comparison']))
                ->descriptionIcon($gpChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($gpChange >= 0 ? 'success' : 'danger')
                ->chart($gpChart),

            Stat::make("Profit Margin {$range['label']}", number_format($profitMargin, 2).'%')
                ->description($desc($marginChange, $range['comparison']))
                ->descriptionIcon($marginChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($marginChange >= 0 ? 'success' : 'danger'),
        ];
    }
}
