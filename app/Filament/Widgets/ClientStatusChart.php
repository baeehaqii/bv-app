<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class ClientStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;
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

        return "Client Status Distribution - {$label}";
    }

    protected function getData(): array
    {
        // Static demo data - adjusted based on filter range
        $multiplier = match ($this->dateFilter) {
            'today', 'yesterday' => 0.1,
            '7d' => 0.5,
            '30d' => 1,
            '90d' => 3,
            default => 1,
        };

        $data = [
            (int) (12 * $multiplier),
            (int) (8 * $multiplier),
            (int) (6 * $multiplier),
            (int) (5 * $multiplier),
            (int) (3 * $multiplier),
        ];
        $labels = ['Draft', 'Upcoming', 'Ongoing', 'Completed', 'Cancelled'];

        $backgroundColors = [
            'rgba(107, 114, 128, 0.8)',  // Gray (Draft)
            'rgba(59, 130, 246, 0.8)',   // Blue (Upcoming)
            'rgba(245, 158, 11, 0.8)',   // Amber (Ongoing)
            'rgba(16, 185, 129, 0.8)',   // Green (Completed)
            'rgba(239, 68, 68, 0.8)',    // Red (Cancelled)
        ];
        $borderColors = [
            'rgb(107, 114, 128)',
            'rgb(59, 130, 246)',
            'rgb(245, 158, 11)',
            'rgb(16, 185, 129)',
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

