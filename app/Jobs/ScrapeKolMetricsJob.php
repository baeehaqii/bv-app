<?php

namespace App\Jobs;

use App\Models\BvCampaignKol;
use App\Service\InstagramService;
use App\Service\TiktokService;
use App\Service\YoutubeChannelsService;
use App\Service\YoutubeShortsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeKolMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * @return array<int>
     */
    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function __construct(
        public readonly int $campaignKolId,
    ) {
    }

    public function handle(): void
    {
        $kol = BvCampaignKol::find($this->campaignKolId);

        if (!$kol || empty($kol->post_url)) {
            return;
        }

        Log::info('[ScrapeKolMetrics] Starting scrape', [
            'campaign_kol_id' => $this->campaignKolId,
            'platform' => $kol->platform,
            'url' => $kol->post_url,
        ]);

        try {
            $metrics = match ($kol->platform) {
                'instagram' => $this->scrapeInstagram($kol),
                'tiktok' => $this->scrapeTiktok($kol),
                'youtube' => $this->scrapeYoutube($kol),
                default => null,
            };

            if ($metrics) {
                $kol->update(array_merge($metrics, [
                    'last_fetched_at' => now(),
                ]));

                Log::info('[ScrapeKolMetrics] Completed', [
                    'campaign_kol_id' => $this->campaignKolId,
                    'metrics' => $metrics,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[ScrapeKolMetrics] Failed', [
                'campaign_kol_id' => $this->campaignKolId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function scrapeInstagram(BvCampaignKol $kol): ?array
    {
        $service = app(InstagramService::class);

        $username = $kol->username ?? $this->extractUsernameFromUrl($kol->post_url, 'instagram');

        if (!$username) {
            return null;
        }

        $posts = $service->getUserPosts($username, 9, true);

        if (empty($posts)) {
            return null;
        }

        // Cari post yang sesuai dengan URL
        $postUrl = rtrim($kol->post_url, '/');
        $matchedPost = collect($posts)->first(function ($post) use ($postUrl) {
            return isset($post['url']) && str_contains($post['url'], $postUrl);
        }) ?? $posts[0];

        return [
            'views' => $matchedPost['video_view_count'] ?? $matchedPost['views'] ?? 0,
            'likes' => $matchedPost['likes'] ?? 0,
            'comments' => $matchedPost['comments'] ?? 0,
            'shares' => $matchedPost['shares'] ?? 0,
            'saves' => $matchedPost['saves'] ?? 0,
            'reach' => $matchedPost['reach'] ?? 0,
            'impressions' => $matchedPost['impressions'] ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function scrapeTiktok(BvCampaignKol $kol): ?array
    {
        $service = app(TiktokService::class);

        $username = $kol->username ?? $this->extractUsernameFromUrl($kol->post_url, 'tiktok');

        if (!$username) {
            return null;
        }

        $data = $service->getUserProfile($username);

        if (empty($data)) {
            return null;
        }

        return [
            'views' => $data['video_view_count'] ?? $data['views'] ?? 0,
            'likes' => $data['likes'] ?? 0,
            'comments' => $data['comments'] ?? 0,
            'shares' => $data['shares'] ?? 0,
            'followers_count' => $data['followers'] ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function scrapeYoutube(BvCampaignKol $kol): ?array
    {
        $service = $kol->content_type === 'short'
            ? app(YoutubeShortsService::class)
            : app(YoutubeChannelsService::class);

        $data = $service->getVideoMetrics($kol->post_url);

        if (empty($data)) {
            return null;
        }

        return [
            'views' => $data['views'] ?? 0,
            'likes' => $data['likes'] ?? 0,
            'comments' => $data['comments'] ?? 0,
            'shares' => $data['shares'] ?? 0,
        ];
    }

    private function extractUsernameFromUrl(string $url, string $platform): ?string
    {
        $patterns = [
            'instagram' => '/instagram\.com\/([^\/\?]+)/i',
            'tiktok' => '/tiktok\.com\/@([^\/\?]+)/i',
        ];

        if (!isset($patterns[$platform])) {
            return null;
        }

        preg_match($patterns[$platform], $url, $matches);

        return $matches[1] ?? null;
    }
}
