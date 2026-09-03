<?php

namespace App\Filament\Widgets;

use App\Enums\ClientStatus;
use App\Filament\Traits\HasDashboardFilter;
use App\Models\DataClient;
use Filament\Widgets\ChartWidget;

class ClientStatusChart extends ChartWidget
{
    use HasDashboardFilter;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static bool $isLazy = false;

    private const PALETTE = [
        ['rgba(107, 114, 128, 0.8)', 'rgb(107, 114, 128)'],
        ['rgba(59, 130, 246, 0.8)', 'rgb(59, 130, 246)'],
        ['rgba(245, 158, 11, 0.8)', 'rgb(245, 158, 11)'],
        ['rgba(16, 185, 129, 0.8)', 'rgb(16, 185, 129)'],
        ['rgba(239, 68, 68, 0.8)', 'rgb(239, 68, 68)'],
        ['rgba(139, 92, 246, 0.8)', 'rgb(139, 92, 246)'],
    ];

    public function getHeading(): ?string
    {
        return "Client Status Distribution - {$this->dashboardPeriodLabel()}";
    }

    protected function getData(): array
    {
        $range = $this->dashboardDateRange();

        $rows = DataClient::query()
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->whereNotNull('status_client')
            ->selectRaw('status_client, count(*) as total')
            ->groupBy('status_client')
            ->orderByDesc('total')
            ->pluck('total', 'status_client');

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
            'labels' => $rows->keys()->map(fn($status) => ClientStatus::labelFor($status))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
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
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
