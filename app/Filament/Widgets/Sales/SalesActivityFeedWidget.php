<?php

namespace App\Filament\Widgets\Sales;

use App\Enums\SalesStatus;
use App\Models\BvSales;
use App\Models\BvSalesList;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class SalesActivityFeedWidget extends Widget
{
    protected string $view = 'filament.widgets.sales.sales-activity-feed';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 3;

    public string $dateFilter = 'today';

    #[On('salesDashboardFilterChanged')]
    public function handleFilterChanged(string $dateFilter): void
    {
        $this->dateFilter = $dateFilter;
    }

    public function getDateRange(): array
    {
        $now = Carbon::now();
        return match ($this->dateFilter) {
            'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            default => [Carbon::today()->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    public function getPeriodLabel(): string
    {
        return match ($this->dateFilter) {
            'yesterday' => 'Kemarin',
            '7d' => '7 Hari Terakhir',
            '30d' => '30 Hari Terakhir',
            '90d' => '90 Hari Terakhir',
            default => 'Hari Ini',
        };
    }

    public function getRecentDeals(): \Illuminate\Support\Collection
    {
        $salesList = BvSalesList::where('user_id', auth()->id())->first();

        if (!$salesList) {
            return collect();
        }

        $dateRange = $this->getDateRange();

        return BvSales::where('bv_sales_list_id', $salesList->id)
            ->whereBetween('updated_at', $dateRange)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'event_name', 'company_name', 'deal_value', 'budget_propose', 'status', 'close_date', 'updated_at']);
    }

    public function getPipelineSummary(): array
    {
        $salesList = BvSalesList::where('user_id', auth()->id())->first();

        if (!$salesList) {
            return [];
        }

        return BvSales::where('bv_sales_list_id', $salesList->id)
            ->selectRaw('status, COUNT(*) as count, SUM(deal_value) as total_value')
            ->groupBy('status')
            ->get()
            ->map(fn($row) => [
                'status' => $row->status, // already a SalesStatus enum via model cast
                'count' => $row->count,
                'total_value' => (float) $row->total_value,
            ])
            ->sortBy(fn($item) => array_search($item['status']->value, array_column(SalesStatus::cases(), 'value')))
            ->values()
            ->toArray();
    }
}
