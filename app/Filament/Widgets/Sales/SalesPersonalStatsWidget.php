<?php

namespace App\Filament\Widgets\Sales;

use App\Enums\SalesStatus;
use App\Models\BvSales;
use App\Models\BvSalesList;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class SalesPersonalStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 'full';

    public string $dateFilter = 'today';

    #[On('salesDashboardFilterChanged')]
    public function handleFilterChanged(string $dateFilter): void
    {
        $this->dateFilter = $dateFilter;
    }

    private function getDateRange(): array
    {
        $now = Carbon::now();
        return match ($this->dateFilter) {
            'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            default => [Carbon::today()->startOfDay(), $now->copy()->endOfDay()], // today
        };
    }

    private function getPeriodLabel(): string
    {
        return match ($this->dateFilter) {
            'yesterday' => 'Kemarin',
            '7d' => '7 Hari Terakhir',
            '30d' => '30 Hari Terakhir',
            '90d' => '90 Hari Terakhir',
            default => 'Hari Ini',
        };
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $now = Carbon::now();
        $salesList = BvSalesList::where('user_id', $user->id)->first();

        if (!$salesList) {
            return [];
        }

        $base = BvSales::where('bv_sales_list_id', $salesList->id);
        $dateRange = $this->getDateRange();
        $period = $this->getPeriodLabel();

        $wonStatuses = [
            SalesStatus::CAMPAIGN_LIVE->value,
            SalesStatus::REPORTING->value,
            SalesStatus::INVOICING->value,
            SalesStatus::PAID->value,
        ];

        // ── Pipeline aktif (tidak difilter per periode) ──────────────────
        $activePipeline = (clone $base)->whereNotIn('status', [
            SalesStatus::CLOSE_LOSE->value,
            SalesStatus::PAID->value,
        ])->count();
        $totalDeals = (clone $base)->count();
        $newInPeriod = (clone $base)->whereBetween('created_at', $dateRange)->count();

        // ── Won / Lost dalam periode ─────────────────────────────────────
        $wonInPeriod = (clone $base)->whereIn('status', $wonStatuses)->whereBetween('updated_at', $dateRange)->count();
        $lostInPeriod = (clone $base)->where('status', SalesStatus::CLOSE_LOSE->value)->whereBetween('updated_at', $dateRange)->count();

        // ── Win rate all-time ────────────────────────────────────────────
        $wonTotal = (clone $base)->whereIn('status', $wonStatuses)->count();
        $lostTotal = (clone $base)->where('status', SalesStatus::CLOSE_LOSE->value)->count();
        $winRate = ($wonTotal + $lostTotal) > 0
            ? round(($wonTotal / ($wonTotal + $lostTotal)) * 100, 1)
            : 0;

        // ── Deal Value dalam periode ─────────────────────────────────────
        $dealValueInPeriod = (float) (clone $base)
            ->whereIn('status', $wonStatuses)
            ->whereBetween('close_date', $dateRange)
            ->sum('deal_value');

        $dealCountInPeriod = (clone $base)
            ->whereIn('status', $wonStatuses)
            ->whereBetween('close_date', $dateRange)
            ->count();

        // ── Trend chart 6 bulan terakhir ─────────────────────────────────
        $chart = collect(range(5, 0))->map(
            fn(int $offset) => (float) BvSales::where('bv_sales_list_id', $salesList->id)
                ->whereIn('status', $wonStatuses)
                ->whereYear('close_date', $now->copy()->subMonths($offset)->year)
                ->whereMonth('close_date', $now->copy()->subMonths($offset)->month)
                ->sum('deal_value') / 1_000_000
        )->values()->toArray();

        $fmt = fn(float $val): string => 'Rp ' . number_format($val, 0, ',', '.');

        return [
            Stat::make('Total Pipeline Aktif', $activePipeline . ' Deals')
                ->description("Total semua deals: {$totalDeals} | Baru {$period}: {$newInPeriod}")
                ->descriptionIcon('heroicon-m-funnel')
                ->color('primary'),

            Stat::make("Won {$period}", $wonInPeriod . ' Deals')
                ->description("Lost: {$lostInPeriod} | Win rate all-time: {$winRate}%")
                ->descriptionIcon($wonInPeriod > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-minus-circle')
                ->color($wonInPeriod > 0 ? 'success' : 'gray'),

            Stat::make("Deal Value {$period}", $fmt($dealValueInPeriod))
                ->description("Dari {$dealCountInPeriod} deals yang closed/active")
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info')
                ->chart($chart),
        ];
    }
}
