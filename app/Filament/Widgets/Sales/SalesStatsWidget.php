<?php

namespace App\Filament\Widgets\Sales;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Dummy data - replace with real data later
        $revenueData = [
            'current' => 750000000,
            'previous' => 650000000,
            'chart' => [65, 72, 68, 75, 80, 85, 78, 82, 88, 92, 95, 100]
        ];

        $grossProfitData = [
            'current' => 187500000,
            'previous' => 162500000,
            'margin' => 25, // 25%
            'chart' => [20, 22, 21, 24, 26, 28, 25, 27, 30, 32, 34, 35]
        ];

        $dealData = [
            'total' => 24,
            'won' => 18,
            'lost' => 4,
            'pending' => 2,
            'chart' => [3, 5, 4, 6, 7, 8, 5, 6, 8, 9, 10, 12]
        ];

        $revenueChange = (($revenueData['current'] - $revenueData['previous']) / $revenueData['previous']) * 100;
        $profitChange = (($grossProfitData['current'] - $grossProfitData['previous']) / $grossProfitData['previous']) * 100;

        return [
            Stat::make('Revenue', 'Rp ' . number_format($revenueData['current'], 0, ',', '.'))
                ->description(sprintf('%+.1f%% dari bulan lalu', $revenueChange))
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($revenueData['chart']),

            Stat::make('Gross Profit', 'Rp ' . number_format($grossProfitData['current'], 0, ',', '.'))
                ->description(sprintf('%+.1f%% | Margin: %d%%', $profitChange, $grossProfitData['margin']))
                ->descriptionIcon($profitChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($profitChange >= 0 ? 'success' : 'danger')
                ->chart($grossProfitData['chart']),

            Stat::make('Total Deals', $dealData['total'] . ' Deals')
                ->description("Won: {$dealData['won']} | Lost: {$dealData['lost']} | Pending: {$dealData['pending']}")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary')
                ->chart($dealData['chart']),

            Stat::make('Win Rate', number_format(($dealData['won'] / $dealData['total']) * 100, 1) . '%')
                ->description('Dari total deals yang closed')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning')
                ->chart([60, 65, 70, 72, 68, 75, 78, 80, 82, 85, 88, 75]),
        ];
    }
}
