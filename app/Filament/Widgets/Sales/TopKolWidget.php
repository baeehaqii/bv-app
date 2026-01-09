<?php

namespace App\Filament\Widgets\Sales;

use Filament\Widgets\Widget;

class TopKolWidget extends Widget
{
    protected string $view = 'filament.widgets.top-kol-widget';

    protected int|string|array $columnSpan = 'full';

    public bool $showFullList = false;

    // Dummy data for top KOLs per campaign
    public function getTopKolData(): array
    {
        return [
            [
                'rank' => 1,
                'username' => '@maya_beauty',
                'campaign' => 'Skincare Launch Q1',
                'channel' => 'Instagram',
                'followers' => 2500000,
                'engagement_rate' => 4.5,
                'total_reach' => 5000000,
                'conversions' => 1250,
                'revenue' => 125000000,
            ],
            [
                'rank' => 2,
                'username' => '@tech_reviewer_id',
                'campaign' => 'Gadget Promo',
                'channel' => 'YouTube',
                'followers' => 1800000,
                'engagement_rate' => 6.2,
                'total_reach' => 3600000,
                'conversions' => 980,
                'revenue' => 98000000,
            ],
            [
                'rank' => 3,
                'username' => '@foodie_jakarta',
                'campaign' => 'F&B Campaign',
                'channel' => 'TikTok',
                'followers' => 3200000,
                'engagement_rate' => 8.1,
                'total_reach' => 6400000,
                'conversions' => 850,
                'revenue' => 85000000,
            ],
            [
                'rank' => 4,
                'username' => '@lifestyle_andi',
                'campaign' => 'Fashion Week',
                'channel' => 'Instagram',
                'followers' => 1500000,
                'engagement_rate' => 5.3,
                'total_reach' => 2800000,
                'conversions' => 720,
                'revenue' => 72000000,
            ],
            [
                'rank' => 5,
                'username' => '@gaming_master',
                'campaign' => 'Game Launch',
                'channel' => 'YouTube',
                'followers' => 2100000,
                'engagement_rate' => 7.8,
                'total_reach' => 4200000,
                'conversions' => 650,
                'revenue' => 65000000,
            ],
            [
                'rank' => 6,
                'username' => '@travel_nusantara',
                'campaign' => 'Tourism Promo',
                'channel' => 'Instagram',
                'followers' => 980000,
                'engagement_rate' => 4.2,
                'total_reach' => 1800000,
                'conversions' => 520,
                'revenue' => 52000000,
            ],
            [
                'rank' => 7,
                'username' => '@parenting_tips',
                'campaign' => 'Baby Products',
                'channel' => 'TikTok',
                'followers' => 1200000,
                'engagement_rate' => 6.5,
                'total_reach' => 2200000,
                'conversions' => 480,
                'revenue' => 48000000,
            ],
            [
                'rank' => 8,
                'username' => '@fitness_coach',
                'campaign' => 'Health Supps',
                'channel' => 'Instagram',
                'followers' => 850000,
                'engagement_rate' => 5.8,
                'total_reach' => 1500000,
                'conversions' => 420,
                'revenue' => 42000000,
            ],
        ];
    }

    public function toggleFullList(): void
    {
        $this->showFullList = !$this->showFullList;
    }
}
