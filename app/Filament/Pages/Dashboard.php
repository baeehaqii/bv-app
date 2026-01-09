<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ClientDemographyChart;
use App\Filament\Widgets\ClientStatusChart;
use App\Filament\Widgets\RevenueStatsWidget;
use App\Filament\Widgets\TopSpenderWidget;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $form): Schema
    {
        return $form
            ->components([
                Select::make('period')
                    ->label('Periode')
                    ->options([
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                        'quarterly' => 'Per Kuartal',
                    ])
                    ->default('monthly')
                    ->selectablePlaceholder(false)
                    ->native(false),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            RevenueStatsWidget::class,
            ClientStatusChart::class,
            ClientDemographyChart::class,
            TopSpenderWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
