<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class ClientDemographyChart extends ChartWidget
{
    protected ?string $heading = 'Client by Category';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;
    protected static bool $isLazy = false; // Disable lazy loading for animation

    protected function getData(): array
    {
        // Static demo data
        $data = [8, 6, 5, 4, 3];
        $labels = ['F&B', 'Beauty', 'Fashion', 'Technology', 'Home Care'];

        // Vibrant color palette
        $backgroundColors = [
            'rgba(139, 92, 246, 0.8)',   // Purple
            'rgba(236, 72, 153, 0.8)',   // Pink
            'rgba(59, 130, 246, 0.8)',   // Blue
            'rgba(16, 185, 129, 0.8)',   // Green
            'rgba(245, 158, 11, 0.8)',   // Amber
        ];

        $borderColors = [
            'rgb(139, 92, 246)',
            'rgb(236, 72, 153)',
            'rgb(59, 130, 246)',
            'rgb(16, 185, 129)',
            'rgb(245, 158, 11)',
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
            'indexAxis' => 'y', // Horizontal bar chart
            'animation' => [
                'duration' => 1500,
                'easing' => 'easeOutQuart',
                'delay' => 0,
            ],
            'animations' => [
                'x' => [
                    'from' => 0, // Start from left (0 value)
                ],
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
