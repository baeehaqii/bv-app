<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class ClientStatusChart extends ChartWidget
{
    protected ?string $heading = 'Client Status Distribution';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 1;
    protected static bool $isLazy = false; // Disable lazy loading for animation

    protected function getData(): array
    {
        // Static demo data
        $data = [12, 8, 5, 3];
        $labels = ['New List', 'Approaching', 'Waiting Feedback', 'Not Available'];

        $backgroundColors = [
            'rgba(59, 130, 246, 0.8)',   // Blue
            'rgba(16, 185, 129, 0.8)',   // Green
            'rgba(245, 158, 11, 0.8)',   // Amber
            'rgba(239, 68, 68, 0.8)',    // Red
        ];
        $borderColors = [
            'rgb(59, 130, 246)',
            'rgb(16, 185, 129)',
            'rgb(245, 158, 11)',
            'rgb(239, 68, 68)',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Total Clients',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
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
            'animations' => [
                'y' => [
                    'from' => 500, // Start from bottom (high y value)
                ],
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
