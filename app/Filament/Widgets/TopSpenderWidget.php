<?php

namespace App\Filament\Widgets;

use App\Models\BvCampign;
use App\Models\DataClient;
use Filament\Widgets\Widget;

/**
 * Widget: Top Spender Clients
 *
 * Data nyata dari client yang sudah melakukan pembayaran (cashflow bertipe income).
 * Filter: Direct Client (type=direct) atau Agency (type=agency).
 */
class TopSpenderWidget extends Widget
{
    protected string $view = 'filament.widgets.top-spender-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public bool $showFullList = false;

    public string $filter = 'client';

    public bool $campaignsModalOpen = false;

    public string $selectedClientName = '';

    public array $selectedClientCampaigns = [];

    private ?array $cachedData = null;

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->cachedData = null;
    }

    public function toggleFullList(): void
    {
        $this->showFullList = ! $this->showFullList;
        $this->cachedData = null;
    }

    public function getTopSpenderData(): array
    {
        return $this->cachedData ??= $this->buildTopSpenderData();
    }

    private function buildTopSpenderData(): array
    {
        $type = $this->filter === 'agency' ? 'agency' : 'direct';

        return DataClient::query()
            ->where('type', $type)
            ->whereHas('cashflows', fn ($q) => $q->where('type', 'income'))
            ->withSum(['cashflows as total_spent' => fn ($q) => $q->where('type', 'income')], 'amount')
            ->withCount('campaigns')
            ->with('latestCampaign:id,client_id,campaign_name')
            ->orderByDesc('total_spent')
            ->limit($this->showFullList ? 50 : 5)
            ->get(['id', 'nama_brand', 'category', 'status_client'])
            ->values()
            ->map(fn (DataClient $client, int $i) => [
                'rank' => $i + 1,
                'client_name' => $client->nama_brand,
                'industry' => $client->category ?: '-',
                'total_campaigns' => $client->campaigns_count,
                'total_spent' => (float) $client->total_spent,
                'last_campaign' => $client->latestCampaign?->campaign_name ?? '-',
                'status' => $client->status_client ?: '—',
            ])
            ->toArray();
    }

    public function getTotalRevenue(): float
    {
        return collect($this->getTopSpenderData())->sum('total_spent');
    }

    public function getActiveClients(): int
    {
        return collect($this->getTopSpenderData())->where('status', 'Active')->count();
    }

    public function openCampaignsModal(string $clientName): void
    {
        $this->selectedClientName = $clientName;
        $this->campaignsModalOpen = true;

        $client = DataClient::where('nama_brand', $clientName)->first();

        $this->selectedClientCampaigns = $client
            ? $client->campaigns()
                ->withCount('kols')
                ->orderByDesc('campaign_date')
                ->get()
                ->map(fn (BvCampign $campaign) => [
                    'name' => $campaign->campaign_name ?? '-',
                    'period' => $campaign->campaign_date
                        ? $campaign->campaign_date->translatedFormat('M Y')
                        : '-',
                    'kol_count' => $campaign->kols_count,
                    'budget' => (float) $campaign->deal_value,
                    'status' => $campaign->status ?? '-',
                ])
                ->toArray()
            : [];
    }

    public function closeCampaignsModal(): void
    {
        $this->campaignsModalOpen = false;
        $this->selectedClientName = '';
        $this->selectedClientCampaigns = [];
    }
}
