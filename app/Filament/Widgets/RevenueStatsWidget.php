<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Static demo data
        $currentMonthRevenue = 125000000; // Rp 125.000.000
        $previousMonthRevenue = 98000000; // Rp 98.000.000

        $currentMonthExpense = 45000000; // Rp 45.000.000
        $previousMonthExpense = 52000000; // Rp 52.000.000

        $currentMonthProfit = $currentMonthRevenue - $currentMonthExpense; // Rp 80.000.000
        $previousMonthProfit = $previousMonthRevenue - $previousMonthExpense; // Rp 46.000.000

        // Calculate percentage changes
        $revenueChange = (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100; // +27.6%
        $expenseChange = (($currentMonthExpense - $previousMonthExpense) / $previousMonthExpense) * 100; // -13.5%
        $profitChange = (($currentMonthProfit - $previousMonthProfit) / abs($previousMonthProfit)) * 100; // +73.9%

        // Static chart data (last 6 months trend)
        $revenueChart = [65000000, 78000000, 92000000, 88000000, 98000000, 125000000];
        $expenseChart = [42000000, 48000000, 55000000, 50000000, 52000000, 45000000];

        return [
            Stat::make('Revenue Desember 2025', 'Rp ' . number_format($currentMonthRevenue, 0, ',', '.'))
                ->description($this->getChangeDescription($revenueChange, 'dari November'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($revenueChart),

            Stat::make('Expense Desember 2025', 'Rp ' . number_format($currentMonthExpense, 0, ',', '.'))
                ->description($this->getChangeDescription($expenseChange, 'dari November'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('success')
                ->chart($expenseChart),

            Stat::make('Net Profit Desember 2025', 'Rp ' . number_format($currentMonthProfit, 0, ',', '.'))
                ->description($this->getChangeDescription($profitChange, 'dari November'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }

    private function getChangeDescription(float $change, string $suffix): string
    {
        $formattedChange = number_format(abs($change), 1);
        $direction = $change >= 0 ? '+' : '-';
        return "{$direction}{$formattedChange}% {$suffix}";
    }
}
