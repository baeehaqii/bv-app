<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ClientDemographyChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;
    protected static bool $isLazy = false; // Disable lazy loading for animation

    public function getHeading(): ?string
    {
        $period = $this->filters['period'] ?? 'monthly';
        $label = $this->getPeriodLabel($period);

        return "Client by Category - {$label}";
    }

    protected function getData(): array
    {
        $period = $this->filters['period'] ?? 'monthly';

        // Static demo data - adjusted based on period
        // In production, you would query your database here
        $multiplier = match ($period) {
            'daily' => 0.1,
            'weekly' => 0.5,
            'monthly' => 1,
            'quarterly' => 3,
            default => 1,
        };

        $data = [
            (int) (8 * $multiplier),
            (int) (6 * $multiplier),
            (int) (5 * $multiplier),
            (int) (4 * $multiplier),
            (int) (3 * $multiplier),
        ];
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

    private function getPeriodLabel(string $period): string
    {
        $now = Carbon::now();

        return match ($period) {
            'daily' => $now->translatedFormat('d F Y'),
            'weekly' => 'Minggu ke-' . $now->weekOfYear . ' ' . $now->year,
            'monthly' => $now->translatedFormat('F Y'),
            'quarterly' => 'Q' . $now->quarter . ' ' . $now->year,
            default => $now->translatedFormat('F Y'),
        };
    }
}

