<?php

namespace App\Http\Controllers;

use App\Models\BvCampign;

class CampaignPublicController extends Controller
{
    public function show(string $token)
    {
        $campaign = BvCampign::where('public_token', $token)
            ->where('is_public', true)
            ->with(['client', 'kols'])
            ->firstOrFail();

        $kols = $campaign->kols()
            ->where('status', '!=', 'pending')
            ->orWhereNotNull('post_url')
            ->orderBy('platform')
            ->get();

        $platforms = $kols->groupBy('platform');

        $totalViews = $kols->sum('views');
        $totalLikes = $kols->sum('likes');
        $totalComments = $kols->sum('comments');
        $totalShares = $kols->sum('shares');
        $totalEngagement = $kols->sum('total_engagement');

        $avgEr = $kols->where('engagement_rate', '>', 0)->avg('engagement_rate');

        return view('campaign.public', compact(
            'campaign',
            'kols',
            'platforms',
            'totalViews',
            'totalLikes',
            'totalComments',
            'totalShares',
            'totalEngagement',
            'avgEr',
        ));
    }
}
