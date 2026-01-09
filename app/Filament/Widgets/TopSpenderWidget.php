<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TopSpenderWidget extends Widget
{
    protected string $view = 'filament.widgets.top-spender-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public bool $showFullList = false;

    // Dummy data for top spender clients
    public function getTopSpenderData(): array
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
