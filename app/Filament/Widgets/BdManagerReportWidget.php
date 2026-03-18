<?php

namespace App\Filament\Widgets;

use App\Enums\SalesStatus;
use App\Filament\Pages\SalesKanban;
use App\Models\BvBussinesDirector;
use App\Models\BvSales;
use App\Models\DataClient;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/**
 * Widget: BD Manager Report
 *
 * Menampilkan report performa per Business Director meliputi:
 * - Jumlah campaign
 * - Total deal value
 * - Total budget propose
 * - Gross profit (margin * deal_value / 100)
 * - Win rate
 *
 * Dilengkapi filter periode: bulanan, quarter, tahunan.
 */
class BdManagerReportWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.bd-manager-report-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public bool $campaignModalOpen = false;

    public string $selectedBdName = '';

    public array $selectedBdCampaigns = [];

    public array $selectedBdSalesNames = [];

    public string $selectedPeriodLabel = '';

    public string $salesActivityUrl = '';

    /**
     * Ambil data report per BD Manager berdasarkan filter periode.
     */
    public function getReportData(): array
    {
        $period = $this->filters['period'] ?? 'monthly';
        $dateRange = $this->getDateRange($period);

        $directors = BvBussinesDirector::query()
            ->with(['salesLists:id,bv_bussines_director_id,nama_sales'])
            ->orderBy('nama_lengkap')
            ->get();

        $reports = [];
        $totals = [
            'total_clients' => 0,
            'total_campaigns' => 0,
            'won_campaigns' => 0,
            'lost_campaigns' => 0,
            'total_deal_value' => 0,
            'total_budget_propose' => 0,
            'total_gross_profit' => 0,
        ];

        foreach ($directors as $director) {
            $salesIds = $director->salesLists->pluck('id')->all();
            $salesCount = count($salesIds);

            $query = BvSales::query()
                ->when(
                    $salesCount > 0,
                    fn($q) => $q->whereIn('bv_sales_list_id', $salesIds),
                    fn($q) => $q->whereRaw('1 = 0')
                );

            // Filter berdasarkan periode
            if ($dateRange['start'] && $dateRange['end']) {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }

            $allDeals = $query->get();

            // Hitung total client yg dipegang seluruh sales di bawah BD ini
            $clientQuery = DataClient::query()
                ->when(
                    $salesCount > 0,
                    fn($q) => $q->whereIn('pic_internal_sales_id', $salesIds),
                    fn($q) => $q->whereRaw('1 = 0')
                );

            if ($dateRange['start'] && $dateRange['end']) {
                $clientQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }

            $totalClients = (clone $clientQuery)
                ->distinct('id')
                ->count('id');

            $totalCampaigns = $allDeals->count();
            if ($totalCampaigns === 0 && $totalClients === 0) {
                continue; // Skip BD tanpa campaign maupun client di periode ini
            }

            $wonCampaigns = $allDeals->filter(fn($d) => in_array($d->status?->value ?? $d->status, [
                SalesStatus::CAMPAIGN_LIVE->value,
                SalesStatus::REPORTING->value,
                SalesStatus::INVOICING->value,
                SalesStatus::PAID->value,
            ]))->count();

            $lostCampaigns = $allDeals->filter(fn($d) => ($d->status?->value ?? $d->status) === SalesStatus::CLOSE_LOSE->value)->count();

            $totalDealValue = (float) $allDeals->sum('deal_value');
            $totalBudgetPropose = (float) $allDeals->sum('budget_propose');

            // Gross Profit = sum(deal_value * margin / 100) per deal
            $grossProfit = $allDeals->sum(function ($deal) {
                return ((float) $deal->deal_value) * ((float) $deal->margin) / 100;
            });

            $winRate = $totalCampaigns > 0 ? round(($wonCampaigns / $totalCampaigns) * 100, 1) : 0;

            $reports[] = [
                'bd_id' => $director->id,
                'name' => $director->nama_lengkap,
                'total_sales' => $salesCount,
                'total_clients' => $totalClients,
                'total_campaigns' => $totalCampaigns,
                'won_campaigns' => $wonCampaigns,
                'lost_campaigns' => $lostCampaigns,
                'total_deal_value' => $totalDealValue,
                'total_budget_propose' => $totalBudgetPropose,
                'gross_profit' => $grossProfit,
                'win_rate' => $winRate,
            ];

            $totals['total_clients'] += $totalClients;
            $totals['total_campaigns'] += $totalCampaigns;
            $totals['won_campaigns'] += $wonCampaigns;
            $totals['lost_campaigns'] += $lostCampaigns;
            $totals['total_deal_value'] += $totalDealValue;
            $totals['total_budget_propose'] += $totalBudgetPropose;
            $totals['total_gross_profit'] += $grossProfit;
        }

        // Sort by deal value desc
        usort($reports, fn($a, $b) => $b['total_deal_value'] <=> $a['total_deal_value']);

        return [
            'reports' => $reports,
            'totals' => $totals,
            'period_label' => $dateRange['label'],
        ];
    }

    public function openCampaignModal(int $bdId): void
    {
        $period = $this->filters['period'] ?? 'monthly';
        $dateRange = $this->getDateRange($period);

        $director = BvBussinesDirector::query()
            ->with(['salesLists:id,bv_bussines_director_id,nama_sales'])
            ->find($bdId);

        if (!$director) {
            $this->closeCampaignModal();
            return;
        }

        $salesNames = $director->salesLists->pluck('nama_sales', 'id');
        $salesIds = $salesNames->keys()->all();

        if (count($salesIds) === 0) {
            $this->selectedBdName = $director->nama_lengkap;
            $this->selectedBdCampaigns = [];
            $this->selectedBdSalesNames = [];
            $this->selectedPeriodLabel = $dateRange['label'];
            $this->campaignModalOpen = true;
            $this->salesActivityUrl = SalesKanban::getUrl();

            return;
        }

        $query = BvSales::query()->whereIn('bv_sales_list_id', $salesIds);

        if ($dateRange['start'] && $dateRange['end']) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        $campaigns = $query
            ->with(['salesList:id,nama_sales'])
            ->orderByDesc('deal_value')
            ->get()
            ->map(function (BvSales $campaign) {
                $status = $campaign->status?->getLabel() ?? (string) $campaign->status;

                return [
                    'campaign_name' => $campaign->event_name,
                    'client_name' => $campaign->company_name,
                    'sales_name' => $campaign->salesList?->nama_sales ?? '-',
                    'status' => $status,
                    'deal_value' => (float) $campaign->deal_value,
                    'budget_propose' => (float) $campaign->budget_propose,
                ];
            })
            ->toArray();

        $this->selectedBdName = $director->nama_lengkap;
        $this->selectedBdCampaigns = $campaigns;
        $this->selectedBdSalesNames = $salesNames
            ->values()
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
        $this->selectedPeriodLabel = $dateRange['label'];
        $this->campaignModalOpen = true;
        $this->salesActivityUrl = SalesKanban::getUrl();
    }

    public function closeCampaignModal(): void
    {
        $this->campaignModalOpen = false;
        $this->selectedBdName = '';
        $this->selectedBdCampaigns = [];
        $this->selectedBdSalesNames = [];
        $this->selectedPeriodLabel = '';
        $this->salesActivityUrl = '';
    }

    /**
     * Tentukan range tanggal berdasarkan periode yang dipilih.
     */
    private function getDateRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'daily' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => $now->translatedFormat('d F Y'),
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'label' => 'Minggu ke-' . $now->weekOfYear . ' ' . $now->year,
            ],
            'monthly' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->translatedFormat('F Y'),
            ],
            'quarterly' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
                'label' => 'Q' . $now->quarter . ' ' . $now->year,
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->translatedFormat('F Y'),
            ],
        };
    }
}
