<?php

namespace App\Filament\Resources\DataClients\Widgets;

use App\Models\DataClient;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class DataClientStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

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

        // Clients by Status
        $activeClients = (clone $query)->where('status', 'Active')->count();
        $prospectClients = (clone $query)->where('status', 'Prospect')->count();
        $inactiveClients = (clone $query)->where('status', 'Inactive')->count();
        $lostClients = (clone $query)->where('status', 'Lost')->count();

        // Clients by Priority
        $highPriority = (clone $query)->where('priority', 'High')->count();
        $mediumPriority = (clone $query)->where('priority', 'Medium')->count();
        $lowPriority = (clone $query)->where('priority', 'Low')->count();

        // Recent Outreach (last 30 days)
        $recentOutreach = DataClient::whereNotNull('date_outreach')
            ->whereDate('date_outreach', '>=', now()->subDays(30))
            ->count();

        // Pending Follow-ups
        $pendingFollowUps = DataClient::whereNotNull('date_follow_up')
            ->whereDate('date_follow_up', '>=', today())
            ->count();

        return [
            Stat::make('Total Clients', number_format($totalClients) . ' Clients')
                ->description('Total clients in database')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success')
                ->chart([5, 8, 12, 15, 20, 25, $totalClients]),

            Stat::make('Client Status', "Active: {$activeClients} | Prospect: {$prospectClients}")
                ->description("Inactive: {$inactiveClients} | Lost: {$lostClients}")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Priority Distribution', "High: {$highPriority} | Medium: {$mediumPriority}")
                ->description("Low: {$lowPriority}")
                ->descriptionIcon('heroicon-m-flag')
                ->color('warning'),

            Stat::make('Lost Clients', number_format($lostClients) . ' Clients')
                ->description('Total clients marked as lost')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Recent Outreach', number_format($recentOutreach) . ' Clients')
                ->description('Contacted in last 30 days')
                ->descriptionIcon('heroicon-m-phone')
                ->color('success'),

            Stat::make('Pending Follow-ups', number_format($pendingFollowUps) . ' Clients')
                ->description('Upcoming follow-up appointments')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('danger'),
        ];
    }
}
