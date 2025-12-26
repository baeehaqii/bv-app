<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ClientDemographyChart;
use App\Filament\Widgets\ClientStatusChart;
use App\Filament\Widgets\RevenueStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{

    public function getWidgets(): array
    {
        return [
            RevenueStatsWidget::class,
            ClientStatusChart::class,
            ClientDemographyChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
