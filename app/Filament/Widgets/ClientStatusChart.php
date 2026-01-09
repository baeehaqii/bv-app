<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ClientStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 1;
    protected static bool $isLazy = false; // Disable lazy loading for animation

    public function getHeading(): ?string
    {
        $period = $this->filters['period'] ?? 'monthly';
        $label = $this->getPeriodLabel($period);

        return "Client Status Distribution - {$label}";
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
            (int) (12 * $multiplier),
            (int) (8 * $multiplier),
            (int) (5 * $multiplier),
            (int) (3 * $multiplier),
        ];
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

