<?php

namespace App\Filament\Resources\DataVendors\Widgets;

use App\Models\DataVendor;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class DataVendorStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public $dateFilter = 'all';

    protected function getStats(): array
    {
        // Get the date range based on filter
        $query = DataVendor::query();

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
                    $query->whereDate('tanggal_registrasi', today());
                } else {
                    $query->whereDate('tanggal_registrasi', '>=', now()->subDays($days));
                }
            }
        }

        // Total Vendors
        $totalVendors = (clone $query)->count();

        // Vendors by Status
        $activeVendors = (clone $query)->where('status', 'Active')->count();
        $inactiveVendors = (clone $query)->where('status', 'Inactive')->count();
        $pendingVendors = (clone $query)->where('status', 'Pending')->count();

        // Recent Registrations (last 30 days)
        $recentRegistrations = DataVendor::whereDate('tanggal_registrasi', '>=', now()->subDays(30))
            ->count();

        // This Month Registrations
        $thisMonthRegistrations = DataVendor::whereYear('tanggal_registrasi', now()->year)
            ->whereMonth('tanggal_registrasi', now()->month)
            ->count();

        // Vendors by Role (top roles)
        $roles = (clone $query)->select('role_pic')
            ->whereNotNull('role_pic')
            ->groupBy('role_pic')
            ->selectRaw('count(*) as count')
            ->orderByDesc('count')
            ->limit(3)
            ->pluck('count', 'role_pic')
            ->toArray();

        $roleText = collect($roles)->map(fn($count, $role) => "{$role}: {$count}")->implode(' | ');

        return [
            Stat::make('Total Vendors', number_format($totalVendors) . ' Vendors')
                ->description('Total vendors in database')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('success')
                ->chart([5, 10, 15, 20, 28, 35, $totalVendors]),

            Stat::make('Vendor Status', "Active: {$activeVendors} | Pending: {$pendingVendors}")
                ->description("Inactive: {$inactiveVendors}")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Recent Registrations', number_format($recentRegistrations) . ' Vendors')
                ->description('Registered in last 30 days')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),
        ];
    }
}
