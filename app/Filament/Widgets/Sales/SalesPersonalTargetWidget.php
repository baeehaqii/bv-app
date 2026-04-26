<?php

namespace App\Filament\Widgets\Sales;

use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class SalesPersonalTargetWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 'full';

    public string $dateFilter = 'today';

    #[On('salesDashboardFilterChanged')]
    public function handleFilterChanged(string $dateFilter): void
    {
        $this->dateFilter = $dateFilter;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        $fmt = fn(float $val): string => 'Rp ' . number_format($val, 0, ',', '.');

        // Cari BvSalesList berdasarkan user yang login
        $salesList = BvSalesList::where('user_id', $user->id)->first();

        if (!$salesList) {
            return [
                Stat::make('Target Bulan Ini', '—')
                    ->description('Akun sales belum terhubung ke profil sales')
                    ->descriptionIcon('heroicon-m-exclamation-circle')
                    ->color('warning'),
            ];
        }

        $monthLabel = $now->translatedFormat('F Y');
        $quarterNum = SalesTarget::quarterFromMonth($month);
        $quarterMonths = SalesTarget::quarterMonths($quarterNum);
        $quarterLabel = "Q{$quarterNum} {$year}";

        // ── Target ──────────────────────────────────────────────────────
        $monthlyTarget = (float) SalesTarget::forSales($salesList->id)->forMonth($year, $month)->value('target_amount') ?? 0;
        $quarterTarget = (float) SalesTarget::forSales($salesList->id)->forYear($year)->whereIn('month', $quarterMonths)->sum('target_amount');
        $yearlyTarget = (float) SalesTarget::forSales($salesList->id)->forYear($year)->sum('target_amount');

        // ── Realisasi (deal aktif & won) ────────────────────────────────
        $wonStatuses = ['briefing', 'proposal_building', 'negotiation', 'campaign_live', 'reporting', 'invoicing', 'paid'];
        $monthlyActual = (float) BvSales::where('bv_sales_list_id', $salesList->id)
            ->whereIn('status', $wonStatuses)
            ->whereYear('close_date', $year)
            ->whereMonth('close_date', $month)
            ->sum('deal_value');

        $quarterActual = (float) BvSales::where('bv_sales_list_id', $salesList->id)
            ->whereIn('status', $wonStatuses)
            ->whereYear('close_date', $year)
            ->whereRaw('MONTH(close_date) IN (' . implode(',', $quarterMonths) . ')')
            ->sum('deal_value');

        $yearlyActual = (float) BvSales::where('bv_sales_list_id', $salesList->id)
            ->whereIn('status', $wonStatuses)
            ->whereYear('close_date', $year)
            ->sum('deal_value');

        // ── Achievement % ────────────────────────────────────────────────
        $monthPct = $monthlyTarget > 0 ? round(($monthlyActual / $monthlyTarget) * 100, 1) : 0;
        $qPct = $quarterTarget > 0 ? round(($quarterActual / $quarterTarget) * 100, 1) : 0;
        $yearPct = $yearlyTarget > 0 ? round(($yearlyActual / $yearlyTarget) * 100, 1) : 0;

        $colorFor = fn(float $pct): string => match (true) {
            $pct >= 100 => 'success',
            $pct >= 70 => 'warning',
            default => 'danger',
        };

        $chartFor = fn(int $salesListId, int $y, array $months): array => collect($months)
            ->map(
                fn(int $m) => (float) BvSales::where('bv_sales_list_id', $salesListId)
                    ->whereIn('status', $wonStatuses)
                    ->whereYear('close_date', $y)->whereMonth('close_date', $m)
                    ->sum('deal_value') / 1_000_000
            )->values()->toArray();

        $quarterChartMonths = array_merge(
            range(max(1, $quarterMonths[0] - 2), $quarterMonths[0] - 1),
            $quarterMonths
        );

        return [
            Stat::make("🎯 Target {$monthLabel}", $fmt($monthlyTarget))
                ->description($monthlyTarget > 0
                    ? "Tercapai: {$fmt($monthlyActual)} ({$monthPct}%)"
                    : 'Target belum diset untuk bulan ini')
                ->descriptionIcon($monthPct >= 100 ? 'heroicon-m-check-circle' : 'heroicon-m-arrow-trending-up')
                ->color($monthlyTarget > 0 ? $colorFor($monthPct) : 'warning'),

            Stat::make("📈 Target {$quarterLabel}", $fmt($quarterTarget))
                ->description("Tercapai: {$fmt($quarterActual)} ({$qPct}%)")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($quarterTarget > 0 ? $colorFor($qPct) : 'gray')
                ->chart($chartFor($salesList->id, $year, $quarterMonths)),

            Stat::make("🏆 Target Tahunan {$year}", $fmt($yearlyTarget))
                ->description("Tercapai: {$fmt($yearlyActual)} ({$yearPct}%)")
                ->descriptionIcon('heroicon-m-trophy')
                ->color($yearlyTarget > 0 ? $colorFor($yearPct) : 'gray'),
        ];
    }
}
