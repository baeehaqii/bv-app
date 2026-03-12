<?php

namespace App\Filament\Widgets;

use App\Enums\SalesStatus;
use App\Models\BvSales;
use App\Models\BvSalesList;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/**
 * Widget: BD Manager Report
 *
 * Menampilkan report performa per BD Manager (Sales) meliputi:
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

    /**
     * Ambil data report per BD Manager berdasarkan filter periode.
     */
    public function getReportData(): array
    {
        $period = $this->filters['period'] ?? 'monthly';
        $dateRange = $this->getDateRange($period);

        $salesPeople = BvSalesList::orderBy('nama_sales')->get();

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

        foreach ($salesPeople as $sales) {
            $query = BvSales::where('bv_sales_list_id', $sales->id);

            // Filter berdasarkan periode
            if ($dateRange['start'] && $dateRange['end']) {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }

            $allDeals = $query->get();

            // Hitung total client yg dipegang sales ini dalam periode tersebut
            $clientQuery = \App\Models\DataClient::where('pic_internal_sales_id', $sales->id);
            if ($dateRange['start'] && $dateRange['end']) {
                $clientQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }
            $totalClients = $clientQuery->count();

            $totalCampaigns = $allDeals->count();
            if ($totalCampaigns === 0 && $totalClients === 0) {
                continue; // Skip sales tanpa campaign maupun client di periode ini
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
                'name' => $sales->nama_sales,
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
