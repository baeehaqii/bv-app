<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

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

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function openCampaignsModal(string $clientName): void
    {
        $this->selectedClientName = $clientName;
        $this->campaignsModalOpen = true;

        // Dummy campaigns data
        $this->selectedClientCampaigns = [
            [
                'name' => 'Q1 Brand Awereness ' . date('Y'),
                'status' => 'Ongoing',
                'period' => 'Jan - Mar ' . date('Y'),
                'budget' => 450000000,
                'kol_count' => 12
            ],
            [
                'name' => 'Ramadan Special Campaign',
                'status' => 'Planning',
                'period' => 'Mar - Apr ' . date('Y'),
                'budget' => 850000000,
                'kol_count' => 25
            ],
            [
                'name' => 'New Product Launch',
                'status' => 'Completed',
                'period' => 'Dec ' . (date('Y') - 1),
                'budget' => 350000000,
                'kol_count' => 8
            ],
            [
                'name' => 'Year End Sale Support',
                'status' => 'Completed',
                'period' => 'Nov - Dec ' . (date('Y') - 1),
                'budget' => 600000000,
                'kol_count' => 15
            ],
        ];
    }

    public function closeCampaignsModal(): void
    {
        $this->campaignsModalOpen = false;
        $this->selectedClientName = '';
        $this->selectedClientCampaigns = [];
    }

    public function getTopSpenderData(): array
    {
        return $this->filter === 'agency' ? $this->getAgencyData() : $this->getClientData();
    }

    protected function getAgencyData(): array
    {
        return [
            [
                'rank' => 1,
                'client_name' => 'Dentsu Indonesia',
                'industry' => 'Agency',
                'total_campaigns' => 24,
                'total_spent' => 4500000000,
                'last_campaign' => 'Multi-brand Activation',
                'status' => 'Active',
            ],
            [
                'rank' => 2,
                'client_name' => 'Ogilvy',
                'industry' => 'Agency',
                'total_campaigns' => 18,
                'total_spent' => 3200000000,
                'last_campaign' => 'Tech Giant Launch',
                'status' => 'Active',
            ],
            [
                'rank' => 3,
                'client_name' => 'GroupM',
                'industry' => 'Agency media',
                'total_campaigns' => 15,
                'total_spent' => 2800000000,
                'last_campaign' => 'FMCG Q1 Push',
                'status' => 'Active',
            ],
            [
                'rank' => 4,
                'client_name' => 'Havas Operations',
                'industry' => 'Agency',
                'total_campaigns' => 12,
                'total_spent' => 2100000000,
                'last_campaign' => 'Banking Digital',
                'status' => 'Active',
            ],
            [
                'rank' => 5,
                'client_name' => 'Publicis Groupe',
                'industry' => 'Agency',
                'total_campaigns' => 10,
                'total_spent' => 1800000000,
                'last_campaign' => 'Beauty Care Promo',
                'status' => 'Active',
            ],
        ];
    }

    protected function getClientData(): array
    {
        return [
            [
                'rank' => 1,
                'client_name' => 'PT. Unilever Indonesia',
                'industry' => 'FMCG',
                'total_campaigns' => 12,
                'total_spent' => 2500000000,
                'last_campaign' => 'Beauty Campaign Q4',
                'status' => 'Active',
            ],
            [
                'rank' => 2,
                'client_name' => 'Tokopedia',
                'industry' => 'E-Commerce',
                'total_campaigns' => 8,
                'total_spent' => 1800000000,
                'last_campaign' => 'Ramadan Sale',
                'status' => 'Active',
            ],
            [
                'rank' => 3,
                'client_name' => 'Bank BCA',
                'industry' => 'Banking',
                'total_campaigns' => 6,
                'total_spent' => 1500000000,
                'last_campaign' => 'Digital Banking',
                'status' => 'Active',
            ],
            [
                'rank' => 4,
                'client_name' => 'Samsung Indonesia',
                'industry' => 'Technology',
                'total_campaigns' => 5,
                'total_spent' => 1200000000,
                'last_campaign' => 'Galaxy Launch',
                'status' => 'Active',
            ],
            [
                'rank' => 5,
                'client_name' => 'Grab Indonesia',
                'industry' => 'Technology',
                'total_campaigns' => 7,
                'total_spent' => 980000000,
                'last_campaign' => 'GrabFood Promo',
                'status' => 'Active',
            ],
            [
                'rank' => 6,
                'client_name' => 'L\'Oreal Indonesia',
                'industry' => 'Beauty',
                'total_campaigns' => 4,
                'total_spent' => 850000000,
                'last_campaign' => 'Skincare Launch',
                'status' => 'Inactive',
            ],
            [
                'rank' => 7,
                'client_name' => 'Telkomsel',
                'industry' => 'Telco',
                'total_campaigns' => 9,
                'total_spent' => 780000000,
                'last_campaign' => 'By.U Campaign',
                'status' => 'Active',
            ],
            [
                'rank' => 8,
                'client_name' => 'Shopee Indonesia',
                'industry' => 'E-Commerce',
                'total_campaigns' => 10,
                'total_spent' => 720000000,
                'last_campaign' => '12.12 Sale',
                'status' => 'Active',
            ],
            [
                'rank' => 9,
                'client_name' => 'Indofood',
                'industry' => 'FMCG',
                'total_campaigns' => 3,
                'total_spent' => 650000000,
                'last_campaign' => 'Indomie Campaign',
                'status' => 'Active',
            ],
            [
                'rank' => 10,
                'client_name' => 'Gojek',
                'industry' => 'Technology',
                'total_campaigns' => 6,
                'total_spent' => 580000000,
                'last_campaign' => 'GoFood Festival',
                'status' => 'Active',
            ],
        ];
    }

    public function toggleFullList(): void
    {
        $this->showFullList = !$this->showFullList;
    }

    public function getTotalRevenue(): float
    {
        return collect($this->getTopSpenderData())->sum('total_spent');
    }

    public function getActiveClients(): int
    {
        return collect($this->getTopSpenderData())->where('status', 'Active')->count();
    }
}
