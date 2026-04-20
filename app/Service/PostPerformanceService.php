<?php

namespace App\Service;

use App\Models\BvCampaignKol;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Unified service to fetch post performance from various platforms
 * Supports: Instagram, TikTok, YouTube (regular videos & shorts), Threads
 */
class PostPerformanceService
{
    protected InstagramService $instagramService;
    protected TiktokService $tiktokService;
    protected YoutubeChannelsService $youtubeService;
    protected ThreadsService $threadsService;

    public function __construct()
    {
        $this->instagramService = new InstagramService();
        $this->tiktokService = new TiktokService();
        $this->youtubeService = new YoutubeChannelsService();
        $this->threadsService = new ThreadsService();
    }

    /**
     * Detect platform from URL
     * 
     * @param string $url
     * @return string|null
     */
    public function detectPlatform(string $url): ?string
    {
        $url = strtolower($url);

        if (str_contains($url, 'instagram.com')) {
            return 'instagram';
        }

        if (str_contains($url, 'tiktok.com')) {
            return 'tiktok';
        }

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }

        if (str_contains($url, 'threads.com') || str_contains($url, 'threads.net')) {
            return 'threads';
        }

        return null;
    }

    /**
     * Fetch post performance from URL
     * Automatically detects platform and calls appropriate service
     * 
     * @param string $postUrl
     * @return array Performance data with keys: views, likes, comments, shares, saves, total_engagement
     * @throws Exception
     */
    public function fetchPerformance(string $postUrl): array
    {
        $platform = $this->detectPlatform($postUrl);

        if (!$platform) {
            throw new Exception("Unable to detect platform from URL: {$postUrl}");
        }

        Log::info('📊 Fetching post performance', [
            'url' => $postUrl,
            'platform' => $platform,
        ]);

        return match ($platform) {
            'instagram' => $this->instagramService->getPostStats($postUrl),
            'tiktok' => $this->tiktokService->getPostStats($postUrl),
            'youtube' => $this->youtubeService->getVideoStats($postUrl),
            'threads' => $this->threadsService->getPostStats($postUrl),
            default => throw new Exception("Unsupported platform: {$platform}"),
        };
    }

    /**
     * Fetch and update a single BvCampaignKol record
     * 
     * ER Calculation Logic:
     * - Instagram Reels/Video (has views): ER = (Like + Comment) / Views × 100
     * - Instagram Photo/Carousel (no views): ER = (Like + Comment) / Followers × 100
     * - TikTok/YouTube: ER = (Like + Comment) / Views × 100
     * 
     * @param BvCampaignKol $kol
     * @return BvCampaignKol Updated record
     * @throws Exception
     */
    public function fetchAndUpdateKol(BvCampaignKol $kol): BvCampaignKol
    {
        if (empty($kol->post_url)) {
            throw new Exception("KOL {$kol->creator_name} has no post URL");
        }

        $stats = $this->fetchPerformance($kol->post_url);

        // Update KOL record with base metrics
        $updateData = [
            'views' => $stats['views'] ?? 0,
            'likes' => $stats['likes'] ?? 0,
            'comments' => $stats['comments'] ?? 0,
            'shares' => $stats['shares'] ?? 0,
            'saves' => $stats['saves'] ?? 0,
            'last_fetched_at' => now(),
        ];

        // If service returns followers_count (Instagram), save it
        if (isset($stats['followers_count']) && $stats['followers_count'] > 0) {
            $updateData['followers_count'] = $stats['followers_count'];
        }

        // If service returns content_type, update it
        if (isset($stats['content_type'])) {
            $updateData['content_type'] = $stats['content_type'];
        }

        // Calculate and set engagement rate
        // Use pre-calculated ER from service if available, otherwise calculate
        if (isset($stats['engagement_rate']) && $stats['engagement_rate'] > 0) {
            $updateData['engagement_rate'] = $stats['engagement_rate'];
            $updateData['er_type'] = $stats['er_type'] ?? 'views';
        } else {
            // Fallback calculation
            $totalEngagement = ($stats['likes'] ?? 0) + ($stats['comments'] ?? 0);
            $views = $stats['views'] ?? 0;
            $followers = $stats['followers_count'] ?? $kol->followers_count ?? 0;

            if ($views > 0) {
                // ER by Views
                $updateData['engagement_rate'] = round(($totalEngagement / $views) * 100, 4);
                $updateData['er_type'] = 'views';
            } elseif ($followers > 0) {
                // ER by Followers (for content without views)
                $updateData['engagement_rate'] = round(($totalEngagement / $followers) * 100, 4);
                $updateData['er_type'] = 'followers';
            } else {
                // Cannot calculate ER - no denominator available
                $updateData['engagement_rate'] = 0;
                $updateData['er_type'] = 'followers';
            }
        }

        $kol->update($updateData);

        Log::info('✅ KOL performance updated', [
            'kol_id' => $kol->id,
            'creator_name' => $kol->creator_name,
            'content_type' => $updateData['content_type'] ?? $kol->content_type,
            'er_type' => $updateData['er_type'],
            'views' => $updateData['views'],
            'likes' => $updateData['likes'],
            'comments' => $updateData['comments'],
            'followers_count' => $updateData['followers_count'] ?? $kol->followers_count,
            'engagement_rate' => $updateData['engagement_rate'],
        ]);

        return $kol->fresh();
    }


    /**
     * Bulk fetch and update multiple KOL records
     * 
     * @param \Illuminate\Database\Eloquent\Collection $kols
     * @return array Results with success and failed counts
     */
    public function bulkFetchAndUpdate($kols): array
    {
        $results = [
            'total' => $kols->count(),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($kols as $kol) {
            try {
                if (empty($kol->post_url)) {
                    $results['failed']++;
                    $results['errors'][] = "{$kol->creator_name}: No post URL";
                    continue;
                }

                $this->fetchAndUpdateKol($kol);
                $results['success']++;

                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 second

            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "{$kol->creator_name}: {$e->getMessage()}";

                Log::error('❌ Failed to fetch KOL performance', [
                    'kol_id' => $kol->id,
                    'creator_name' => $kol->creator_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('📊 Bulk fetch completed', $results);

        return $results;
    }
}
