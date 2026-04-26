<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class ClientDemographyChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;
    protected static bool $isLazy = false;

    public string $dateFilter = 'today';

    #[On('executiveDashboardFilterChanged')]
    public function handleFilterChanged(string $dateFilter): void
    {
        $this->dateFilter = $dateFilter;
    }

    public function getHeading(): ?string
    {
        $label = $this->getPeriodLabel($this->dateFilter);

        return "Client by Category - {$label}";
    }

    protected function getData(): array
    {
        $multiplier = match ($this->dateFilter) {
            'today', 'yesterday' => 0.1,
            '7d' => 0.5,
            '30d' => 1,
            '90d' => 3,
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

    private function getPeriodLabel(string $filter): string
    {
        return match ($filter) {
            'yesterday' => Carbon::yesterday()->translatedFormat('d F Y'),
            '7d' => '7 Hari Terakhir',
            '30d' => '30 Hari Terakhir',
            '90d' => '90 Hari Terakhir',
            default => 'Hari Ini',
        };
    }
}

