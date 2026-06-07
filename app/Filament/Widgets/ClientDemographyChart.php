<?php

namespace App\Filament\Widgets;

use App\Filament\Traits\HasDashboardFilter;
use App\Models\DataClient;
use Filament\Widgets\ChartWidget;

class ClientDemographyChart extends ChartWidget
{
    use HasDashboardFilter;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static bool $isLazy = false;

    private const PALETTE = [
        ['rgba(139, 92, 246, 0.8)', 'rgb(139, 92, 246)'],
        ['rgba(236, 72, 153, 0.8)', 'rgb(236, 72, 153)'],
        ['rgba(59, 130, 246, 0.8)', 'rgb(59, 130, 246)'],
        ['rgba(16, 185, 129, 0.8)', 'rgb(16, 185, 129)'],
        ['rgba(245, 158, 11, 0.8)', 'rgb(245, 158, 11)'],
    ];

    public function getHeading(): ?string
    {
        return "Client by Category - {$this->dashboardPeriodLabel()}";
    }

    protected function getData(): array
    {
        $range = $this->dashboardDateRange();

        $rows = DataClient::query()
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->whereNotNull('category')
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category');

        $backgroundColors = [];
        $borderColors = [];
        foreach ($rows->keys() as $i => $label) {
            [$bg, $border] = self::PALETTE[$i % count(self::PALETTE)];
            $backgroundColors[] = $bg;
            $borderColors[] = $border;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Clients',
                    'data' => $rows->values()->all(),
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $rows->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'animation' => [
                'duration' => 1500,
                'easing' => 'easeOutQuart',
                'delay' => 0,
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
