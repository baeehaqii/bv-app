<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ThreadsService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.scrapecreators.com/v1/threads';

    public function __construct()
    {
        $apiKey = config('services.scrapecreators.api_key') ?? env('SCRAPECREATORS_API_KEY');

        if (empty($apiKey)) {
            throw new Exception('ScrapeCreators API key is not configured');
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Get Threads profile data from username or profile URL
     *
     * @param string $linkUserProfile Threads username or profile URL
     * @return array
     * @throws Exception
     */
    public function getProfile(string $linkUserProfile): array
    {
        $username = $this->extractUsername($linkUserProfile);

        Log::info('🔍 Threads Profile API Request', [
            'original_input' => $linkUserProfile,
            'extracted_username' => $username,
        ]);

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
        ])->get("{$this->baseUrl}/profile", [
                    'handle' => $username,
                ]);

        if (!$response->successful()) {
            Log::error('❌ Threads Profile API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to fetch Threads profile: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['success']) || !$data['success']) {
            throw new Exception('Threads API returned unsuccessful response');
        }

        $postsData = $this->getUserPosts($username);

        $parsedData = $this->parseProfileData($data, $postsData);

        Log::info('✅ Threads Profile Parsed Successfully', [
            'username' => $parsedData['username'],
            'followers' => $parsedData['followers_count'],
            'engagement_rate' => $parsedData['engagement_rate'],
        ]);

        return $parsedData;
    }

    /**
     * Get user posts (last 20-30 posts from API)
     *
     * @param string $username
     * @return array
     */
    public function getUserPosts(string $username): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/user/posts", [
                        'handle' => $username,
                    ]);

            if (!$response->successful()) {
                Log::warning('❌ Threads Posts API Error', [
                    'status' => $response->status(),
                    'username' => $username,
                ]);
                return [];
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                return [];
            }

            return $data['posts'] ?? [];

        } catch (Exception $e) {
            Log::warning('Failed to fetch Threads user posts', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get post stats from a Threads post URL.
     * Used by PostPerformanceService for KOL Performance fetch.
     *
     * Fields available from API:
     * - like_count                               → likes
     * - text_post_app_info.direct_reply_count    → comments
     * - text_post_app_info.repost_count          → reposts (shares)
     * - text_post_app_info.reshare_count         → reshares
     * - view_counts                              → views (for video posts)
     *
     * ER: (likes + comments) / followers × 100  [er_type = followers]
     * If view_counts available: (likes + comments) / views × 100  [er_type = views]
     *
     * @param string $postUrl Threads post URL
     * @return array
     * @throws Exception
     */
    public function getPostStats(string $postUrl): array
    {
        Log::info('🔍 Threads Post Stats Request', ['url' => $postUrl]);

        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/post", [
                    'url' => $postUrl,
                ]);

        if (!$response->successful()) {
            Log::error('❌ Threads Post API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to fetch Threads post: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['success']) || !$data['success']) {
            throw new Exception('Threads Post API returned unsuccessful response');
        }

        $post = $data['post'] ?? [];
        $appInfo = $post['text_post_app_info'] ?? [];
        $owner = $post['user'] ?? [];

        $likes = max(0, (int) ($post['like_count'] ?? 0));
        $comments = max(0, (int) ($appInfo['direct_reply_count'] ?? 0));
        $shares = max(0, (int) ($appInfo['repost_count'] ?? 0)) + max(0, (int) ($appInfo['reshare_count'] ?? 0));
        $views = max(0, (int) ($post['view_counts'] ?? 0));
        $username = $owner['username'] ?? null;
        $followersCount = max(0, (int) ($owner['follower_count'] ?? 0));

        // Threads tidak menyediakan saves secara publik
        $saves = 0;

        $totalEngagement = $likes + $comments + $shares;

        // ER: gunakan views jika tersedia (video post), fallback ke followers
        if ($views > 0) {
            $erType = 'views';
            $engagementRate = round((($likes + $comments) / $views) * 100, 4);
        } else {
            $erType = 'followers';
            $engagementRate = 0; // Akan dihitung ulang oleh PostPerformanceService jika ada followers
        }

        $result = [
            'username' => $username,
            'followers_count' => $followersCount,
            'views' => $views,
            'likes' => $likes,
            'comments' => $comments,
            'shares' => $shares,
            'saves' => $saves,
            'total_engagement' => $totalEngagement,
            'er_type' => $erType,
            'engagement_rate' => $engagementRate,
        ];

        Log::info('✅ Threads Post Stats Retrieved', $result);

        return $result;
    }

    /**
     * Extract username from Threads URL or return as-is
     *
     * Supported formats:
     * - https://www.threads.com/@username
     * - https://www.threads.net/@username
     * - threads.com/@username
     * - @username
     * - username
     */
    protected function extractUsername(string $input): string
    {
        if (!str_contains($input, '/') && !str_contains($input, '.')) {
            return ltrim($input, '@');
        }

        $pattern = '/(?:https?:\/\/)?(?:www\.)?threads\.(?:com|net)\/@?([a-zA-Z0-9._]+)\/?/';

        if (preg_match($pattern, $input, $matches)) {
            return $matches[1];
        }

        return ltrim(trim($input), '@/');
    }

    /**
     * Parse raw profile + posts API data into standard format
     */
    protected function parseProfileData(array $profileData, array $posts = []): array
    {
        $followersCount = (int) ($profileData['follower_count'] ?? 0);

        $engagementMetrics = $this->calculateEngagementMetrics($posts, $followersCount);

        return [
            'id' => $profileData['id'] ?? $profileData['pk'] ?? null,
            'username' => $profileData['username'] ?? null,
            'full_name' => $profileData['full_name'] ?? null,
            'biography' => $profileData['biography'] ?? null,
            'profile_pic_url' => $profileData['profile_pic_url'] ?? null,
            'profile_pic_url_hd' => $profileData['hd_profile_pic_versions'][1]['url']
                ?? $profileData['hd_profile_pic_versions'][0]['url']
                ?? $profileData['profile_pic_url']
                ?? null,

            'followers_count' => $followersCount,
            'following_count' => 0, // Threads API tidak menyediakan ini
            'media_count' => count($posts),

            'tier' => $this->calculateTier($followersCount),

            'engagement_rate' => $engagementMetrics['engagement_rate'],
            'total_engagements' => $engagementMetrics['total_engagements'],
            'average_likes' => $engagementMetrics['average_likes'],
            'average_comments' => $engagementMetrics['average_comments'],
            'average_impressions' => $engagementMetrics['average_impressions'],

            'is_private' => $profileData['text_post_app_is_private'] ?? false,
            'is_verified' => $profileData['is_verified'] ?? false,
            'is_business_account' => false,
            'is_professional_account' => false,

            'business_category_name' => null,
            'category_name' => null,
            'business_email' => null,
            'business_phone_number' => null,
            'business_address' => null,

            'bio_links' => $profileData['bio_links'] ?? [],
            'recent_media' => $this->parseRecentMedia($posts),
            'raw_data' => $profileData,
        ];
    }

    /**
     * Calculate engagement metrics from recent posts.
     *
     * Formula:
     * - Total Engagement = likes + comments + reposts (per post)
     * - ER% = (Average Engagement per Post / Followers) × 100
     */
    protected function calculateEngagementMetrics(array $posts, int $followersCount): array
    {
        $empty = [
            'engagement_rate' => 0,
            'total_engagements' => 0,
            'average_likes' => 0,
            'average_comments' => 0,
            'average_impressions' => 0,
        ];

        if (empty($posts) || $followersCount === 0) {
            return $empty;
        }

        // Gunakan maks 9 post terbaru (exclude < 24 jam)
        $oneDayAgo = time() - (24 * 60 * 60);
        $validPosts = array_filter($posts, fn($p) => ($p['taken_at'] ?? 0) < $oneDayAgo);
        $validPosts = array_slice($validPosts, 0, 9);

        if (empty($validPosts)) {
            $validPosts = array_slice($posts, 0, 9);
        }

        if (empty($validPosts)) {
            return $empty;
        }

        $totalLikes = 0;
        $totalComments = 0;
        $totalShares = 0;
        $postCount = count($validPosts);

        foreach ($validPosts as $post) {
            $appInfo = $post['text_post_app_info'] ?? [];
            $totalLikes += (int) ($post['like_count'] ?? 0);
            $totalComments += (int) ($appInfo['direct_reply_count'] ?? 0);
            $totalShares += (int) ($appInfo['repost_count'] ?? 0) + (int) ($appInfo['reshare_count'] ?? 0);
        }

        $totalEngagements = $totalLikes + $totalComments + $totalShares;
        $avgEngagementPerPost = $postCount > 0 ? $totalEngagements / $postCount : 0;
        $engagementRate = round(($avgEngagementPerPost / $followersCount) * 100, 2);

        return [
            'engagement_rate' => $engagementRate,
            'total_engagements' => $totalEngagements,
            'average_likes' => $postCount > 0 ? round($totalLikes / $postCount) : 0,
            'average_comments' => $postCount > 0 ? round($totalComments / $postCount) : 0,
            'average_impressions' => 0, // Threads tidak menyediakan impressions
        ];
    }

    /**
     * Parse recent posts into simplified format
     */
    protected function parseRecentMedia(array $posts): array
    {
        return array_map(function ($post) {
            $appInfo = $post['text_post_app_info'] ?? [];
            return [
                'id' => $post['pk'] ?? null,
                'code' => $post['code'] ?? null,
                'url' => $post['url'] ?? null,
                'caption' => $post['caption']['text'] ?? null,
                'likes' => $post['like_count'] ?? 0,
                'comments' => $appInfo['direct_reply_count'] ?? 0,
                'reposts' => $appInfo['repost_count'] ?? 0,
                'reshares' => $appInfo['reshare_count'] ?? 0,
                'taken_at' => $post['taken_at'] ?? null,
            ];
        }, array_slice($posts, 0, 9));
    }

    /**
     * Calculate tier from followers count
     */
    protected function calculateTier(int $followers): string
    {
        return match (true) {
            $followers >= 1_000_000 => 'mega',
            $followers >= 500_000 => 'macro',
            $followers >= 100_000 => 'mid',
            $followers >= 10_000 => 'micro',
            $followers >= 1_000 => 'nano',
            default => 'nano',
        };
    }
}
