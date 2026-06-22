<?php

namespace App\Filament\Resources\DataClients\Widgets;

use App\Models\DataClient;
use Filament\Schemas\Components\Component;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class DataClientStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public function getSectionContentComponent(): Component
    {
        return parent::getSectionContentComponent()
            ->extraAttributes(['style' => 'background:transparent;box-shadow:none;border:none;']);
    }

    #[Reactive]
    public $dateFilter = 'all';

    protected function getStats(): array
    {
        // Get the date range based on filter
        $query = DataClient::query();

        if ($this->dateFilter !== 'all') {
            $days = match ($this->dateFilter) {
                'today' => 0,
                '7days' => 7,
                '14days' => 14,
                '30days' => 30,
                '60days' => 60,
                '90days' => 90,
                default => null,
            };

            if ($days !== null) {
                if ($days === 0) {
                    $query->whereDate('created_at', today());
                } else {
                    $query->whereDate('created_at', '>=', now()->subDays($days));
                }
            }
        }

        // Total Clients
        $totalClients = (clone $query)->count();

        // Agency vs Brand (Direct)
        $agencyCount = (clone $query)->where('type', 'agency')->count();
        $brandCount = (clone $query)->where('type', 'direct')->count();

        return [
            Stat::make('Total Client', number_format($totalClients) . ' Clients')
                ->description('Total client di database')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success')
                ->chart([5, 8, 12, 15, 20, 25, $totalClients])
                ->extraAttributes(['style' => 'background-color:#ffffff;box-shadow:0 1px 3px rgba(0,0,0,0.08);border-radius:0.75rem;']),

            Stat::make('Agency', number_format($agencyCount) . ' Agency')
                ->description('Total client tipe agency')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('warning')
                ->extraAttributes(['style' => 'background-color:#ffffff;box-shadow:0 1px 3px rgba(0,0,0,0.08);border-radius:0.75rem;']),

            Stat::make('Brand', number_format($brandCount) . ' Brand')
                ->description('Total client direct brand')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info')
                ->extraAttributes(['style' => 'background-color:#ffffff;box-shadow:0 1px 3px rgba(0,0,0,0.08);border-radius:0.75rem;']),
        ];
    }
}
