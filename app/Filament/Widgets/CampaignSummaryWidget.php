<?php

namespace App\Filament\Widgets;

use App\Models\BvCampign;
use App\Models\BvCampaignKol;
use Filament\Widgets\Widget;

class CampaignSummaryWidget extends Widget
{
    protected string $view = 'filament.widgets.campaign-summary-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getStats(): array
    {
        // Get campaign statistics
        $totalCampaigns = BvCampign::count();
        $totalKols = BvCampaignKol::count();
        $totalCost = BvCampign::sum('total_cost');

        // Get performance from KOLs
        $totalViews = BvCampaignKol::sum('views');
        $totalLikes = BvCampaignKol::sum('likes');
        $totalComments = BvCampaignKol::sum('comments');
        $totalShares = BvCampaignKol::sum('shares');
        $totalEngagement = $totalLikes + $totalComments + $totalShares;

        // Calculate rates
        $engagementRate = $totalViews > 0 ? ($totalEngagement / $totalViews) * 100 : 0;
        $cpe = $totalEngagement > 0 ? $totalCost / $totalEngagement : 0;
        $cpv = $totalViews > 0 ? $totalCost / $totalViews : 0;

        return [
            [
                'label' => 'Total Campaign',
                'value' => number_format($totalCampaigns),
                'icon' => 'heroicon-o-megaphone',
                'color' => 'primary',
            ],
            [
                'label' => 'KOL',
                'value' => number_format($totalKols),
                'icon' => 'heroicon-o-user',
                'color' => 'info',
            ],
            [
                'label' => 'Cost',
                'value' => 'IDR ' . number_format($totalCost, 0, ',', '.'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
            ],
            [
                'label' => 'View',
                'value' => number_format($totalViews),
                'icon' => 'heroicon-o-eye',
                'color' => 'success',
            ],
            [
                'label' => 'Engagement',
                'value' => number_format($totalEngagement, 0, ',', '.'),
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'color' => 'primary',
            ],
            [
                'label' => 'Engagement Rate',
                'value' => number_format($engagementRate, 0) . '%',
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'info',
            ],
            [
                'label' => 'CPE',
                'value' => 'IDR ' . number_format($cpe, 2, ',', '.'),
                'description' => 'Cost Per Engagement',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'warning',
            ],
            [
                'label' => 'CPV',
                'value' => number_format($cpv, 2),
                'description' => 'Cost Per View',
                'icon' => 'heroicon-o-play',
                'color' => 'success',
            ],
        ];
    }
}
