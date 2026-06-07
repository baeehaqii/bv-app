<?php

namespace App\Filament\Widgets;

use App\Enums\SalesStatus;
use App\Filament\Pages\SalesKanban;
use App\Filament\Traits\HasDashboardFilter;
use App\Models\BvEmploye;
use App\Models\BvSales;
use App\Models\DataClient;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Widget: BD Manager Report
 *
 * Report performa per BD Manager (karyawan ber-Position "Business Development Manager").
 * Tim sales diambil dari karyawan yang melapor ke manager tersebut (reports_to),
 * lalu pipeline-nya via relasi BvSalesList.bv_employe_id.
 */
class BdManagerReportWidget extends Widget
{
    use HasDashboardFilter;

    protected string $view = 'filament.widgets.bd-manager-report-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    private const BD_MANAGER_POSITION = 'Business Development Manager';

    public bool $campaignModalOpen = false;

    public string $selectedBdName = '';

    public array $selectedBdCampaigns = [];

    public array $selectedBdSalesNames = [];

    public string $selectedPeriodLabel = '';

    public string $salesActivityUrl = '';

    /** Karyawan BD Manager beserta tim & pipeline-nya (eager load untuk hindari N+1). */
    private function bdManagers(): Collection
    {
        return BvEmploye::query()
            ->whereHas('position', fn ($q) => $q->where('name', self::BD_MANAGER_POSITION))
            ->with(['directReports:id,reports_to_id,nama_lengkap', 'directReports.salesList:id,bv_employe_id,nama_sales'])
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap']);
    }

    /** Map sales (BvSalesList) milik tim seorang BD Manager: [salesListId => nama_sales]. */
    private function teamSales(BvEmploye $manager): Collection
    {
        return $manager->directReports
            ->map(fn (BvEmploye $report) => $report->salesList)
            ->filter()
            ->mapWithKeys(fn ($salesList) => [$salesList->id => $salesList->nama_sales]);
    }

    public function getReportData(): array
    {
        $range = $this->dashboardDateRange();

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

        foreach ($this->bdManagers() as $manager) {
            $salesIds = $this->teamSales($manager)->keys()->all();
            $salesCount = count($salesIds);

            $allDeals = BvSales::query()
                ->when($salesCount > 0, fn ($q) => $q->whereIn('bv_sales_list_id', $salesIds), fn ($q) => $q->whereRaw('1 = 0'))
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->get(['status', 'deal_value', 'budget_propose', 'margin']);

            $totalClients = DataClient::query()
                ->when($salesCount > 0, fn ($q) => $q->whereIn('pic_internal_sales_id', $salesIds), fn ($q) => $q->whereRaw('1 = 0'))
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->count();

            $totalCampaigns = $allDeals->count();
            if ($totalCampaigns === 0 && $totalClients === 0) {
                continue;
            }

            $wonCampaigns = $allDeals->filter(fn ($d) => in_array($d->status?->value ?? $d->status, [
                SalesStatus::CAMPAIGN_LIVE->value,
                SalesStatus::REPORTING->value,
                SalesStatus::INVOICING->value,
                SalesStatus::PAID->value,
            ]))->count();

            $lostCampaigns = $allDeals->filter(fn ($d) => ($d->status?->value ?? $d->status) === SalesStatus::CLOSE_LOSE->value)->count();

            $totalDealValue = (float) $allDeals->sum('deal_value');
            $totalBudgetPropose = (float) $allDeals->sum('budget_propose');
            $grossProfit = $allDeals->sum(fn ($deal) => ((float) $deal->deal_value) * ((float) $deal->margin) / 100);
            $winRate = $totalCampaigns > 0 ? round(($wonCampaigns / $totalCampaigns) * 100, 1) : 0;

            $reports[] = [
                'bd_id' => $manager->id,
                'name' => $manager->nama_lengkap,
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

        usort($reports, fn ($a, $b) => $b['total_deal_value'] <=> $a['total_deal_value']);

        return [
            'reports' => $reports,
            'totals' => $totals,
            'period_label' => $range['label'],
        ];
    }

    public function openCampaignModal(int $bdId): void
    {
        $range = $this->dashboardDateRange();

        $manager = BvEmploye::query()
            ->with(['directReports:id,reports_to_id,nama_lengkap', 'directReports.salesList:id,bv_employe_id,nama_sales'])
            ->find($bdId);

        if (! $manager) {
            $this->closeCampaignModal();

            return;
        }

        $salesNames = $this->teamSales($manager);
        $salesIds = $salesNames->keys()->all();

        $campaigns = count($salesIds) === 0
            ? []
            : BvSales::query()
                ->whereIn('bv_sales_list_id', $salesIds)
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->with(['salesList:id,nama_sales'])
                ->orderByDesc('deal_value')
                ->get()
                ->map(fn (BvSales $campaign) => [
                    'campaign_name' => $campaign->event_name,
                    'client_name' => $campaign->company_name,
                    'sales_name' => $campaign->salesList?->nama_sales ?? '-',
                    'status' => $campaign->status?->getLabel() ?? (string) $campaign->status,
                    'deal_value' => (float) $campaign->deal_value,
                    'budget_propose' => (float) $campaign->budget_propose,
                ])
                ->toArray();

        $this->selectedBdName = $manager->nama_lengkap;
        $this->selectedBdCampaigns = $campaigns;
        $this->selectedBdSalesNames = $salesNames->values()->filter()->unique()->sort()->values()->all();
        $this->selectedPeriodLabel = $range['label'];
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
}
