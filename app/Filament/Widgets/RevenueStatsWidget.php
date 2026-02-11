<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $period = $this->filters['period'] ?? 'monthly';
        $dateRange = $this->getDateRangeFromPeriod($period);

        // Static demo data - In production, you would query your database here
        // using $dateRange['start'] and $dateRange['end']
        $currentData = $this->getDemoDataForPeriod($period, 'current');
        $previousData = $this->getDemoDataForPeriod($period, 'previous');

        $currentMonthRevenue = $currentData['revenue'];
        $previousMonthRevenue = $previousData['revenue'];

        $currentMonthExpense = $currentData['expense'];
        $previousMonthExpense = $previousData['expense'];

        // Calculate Gross Profit (Revenue - Expense)
        $currentGrossProfit = $currentMonthRevenue - $currentMonthExpense;
        $previousGrossProfit = $previousMonthRevenue - $previousMonthExpense;

        // Calculate Profit Margin ((Gross Profit / Revenue) * 100)
        $currentProfitMargin = $currentMonthRevenue > 0 ? ($currentGrossProfit / $currentMonthRevenue) * 100 : 0;
        $previousProfitMargin = $previousMonthRevenue > 0 ? ($previousGrossProfit / $previousMonthRevenue) * 100 : 0;

        // Calculate percentage changes
        $revenueChange = $previousMonthRevenue > 0
            ? (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100
            : 0;

        $grossProfitChange = abs($previousGrossProfit) > 0
            ? (($currentGrossProfit - $previousGrossProfit) / abs($previousGrossProfit)) * 100
            : 0;

        $profitMarginChange = abs($previousProfitMargin) > 0
            ? (($currentProfitMargin - $previousProfitMargin) / abs($previousProfitMargin)) * 100
            : 0;

        // Chart data based on period
        $revenueChart = $this->getChartData($period, 'revenue');
        $expenseChart = $this->getChartData($period, 'expense');

        // Calculate Profit Chart
        $profitChart = [];
        foreach ($revenueChart as $key => $revenue) {
            if (isset($expenseChart[$key])) {
                $profitChart[] = $revenue - $expenseChart[$key];
            }
        }

        return [
            Stat::make("Revenue {$dateRange['label']}", 'Rp ' . number_format($currentMonthRevenue, 0, ',', '.'))
                ->description($this->getChangeDescription($revenueChange, $dateRange['comparisonText']))
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($revenueChart),

            Stat::make("Gross Profit {$dateRange['label']}", 'Rp ' . number_format($currentGrossProfit, 0, ',', '.'))
                ->description($this->getChangeDescription($grossProfitChange, $dateRange['comparisonText']))
                ->descriptionIcon($grossProfitChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($grossProfitChange >= 0 ? 'success' : 'danger')
                ->chart($profitChart),

            Stat::make("Profit Margin {$dateRange['label']}", number_format($currentProfitMargin, 2) . '%')
                ->description($this->getChangeDescription($profitMarginChange, $dateRange['comparisonText']))
                ->descriptionIcon($profitMarginChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($profitMarginChange >= 0 ? 'success' : 'danger'),
        ];
    }

    private function getDateRangeFromPeriod(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'daily' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => $now->format('d F Y'),
                'comparisonText' => 'from yesterday',
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'label' => 'Week ' . $now->weekOfYear . ' ' . $now->year,
                'comparisonText' => 'from last week',
            ],
            'monthly' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->format('F Y'),
                'comparisonText' => 'from last month',
            ],
            'quarterly' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
                'label' => 'Q' . $now->quarter . ' ' . $now->year,
                'comparisonText' => 'from last quarter',
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->format('F Y'),
                'comparisonText' => 'from last month',
            ],
        };
    }

    private function getDemoDataForPeriod(string $period, string $type): array
    {
        // Demo data scaled by period
        $baseRevenue = match ($period) {
            'daily' => 5000000,      // 5 juta/hari
            'weekly' => 35000000,    // 35 juta/minggu
            'monthly' => 125000000,  // 125 juta/bulan
            'quarterly' => 375000000, // 375 juta/kuartal
            default => 125000000,
        };

        $baseExpense = match ($period) {
            'daily' => 2000000,      // 2 juta/hari
            'weekly' => 15000000,    // 15 juta/minggu
            'monthly' => 45000000,   // 45 juta/bulan
            'quarterly' => 135000000, // 135 juta/kuartal
            default => 45000000,
        };

        // Add variation for previous period
        if ($type === 'previous') {
            return [
                'revenue' => (int) ($baseRevenue * 0.85), // 15% less than current
                'expense' => (int) ($baseExpense * 1.15), // 15% more than current
            ];
        }

        return [
            'revenue' => $baseRevenue,
            'expense' => $baseExpense,
        ];
    }

    private function getChartData(string $period, string $type): array
    {
        // Generate chart data points based on period
        $points = match ($period) {
            'daily' => 24,    // hourly for daily
            'weekly' => 7,    // daily for weekly
            'monthly' => 30,  // daily for monthly
            'quarterly' => 12, // weekly for quarterly
            default => 6,
        };

        $data = [];
        $baseValue = $type === 'revenue' ? 5000000 : 2000000;

        for ($i = 0; $i < min($points, 10); $i++) {
            // Random variation for demo purposes
            $variation = 0.7 + (($i + 1) / $points) * 0.6; // Growing trend
            $data[] = (int) ($baseValue * $variation * (0.9 + rand(0, 20) / 100));
        }

        return $data;
    }

    private function getChangeDescription(float $change, string $suffix): string
    {
        $formattedChange = number_format(abs($change), 1);
        $direction = $change >= 0 ? '+' : '-';
        return "{$direction}{$formattedChange}% {$suffix}";
    }
}

