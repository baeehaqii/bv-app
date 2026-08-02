<?php

namespace App\Service;

use App\Service\KolPostNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TiktokService
{
    use \App\Service\Concerns\CalculatesEngagementRate;

    protected string $apiKey;
    protected string $baseUrl = 'https://api.scrapecreators.com/v1/tiktok';

    public function __construct()
    {
        $apiKey = config('services.scrapecreators.api_key') ?? env('SCRAPECREATORS_API_KEY');

        if (empty($apiKey)) {
            throw new Exception('ScrapeCreators API key is not configured');
        }

        $this->apiKey = $apiKey;
    }

    /** Biaya endpoint audiens, jauh di atas endpoint lain — jangan dipanggil otomatis. */
    public const AUDIENCE_CREDITS = 26;

    /**
     * Sebaran negara audiens. SATU-SATUNYA data demografi audiens yang tersedia
     * di ScrapeCreators, dan hanya untuk TikTok — kota, umur, dan gender tidak ada
     * di endpoint mana pun. Mahal (26 kredit), jadi dipanggil on-demand saja.
     *
     * @return array<int, array{country: string, countryCode: ?string, count: int, percentage: float}>
     */
    public function getAudienceCountries(string $linkUserProfile): array
    {
        $username = $this->extractUsername($linkUserProfile);

        $response = Http::withHeaders(['x-api-key' => $this->apiKey])
            ->get("{$this->baseUrl}/user/audience", ['handle' => $username]);

        if (! $response->successful()) {
            Log::error('❌ TikTok Audience API Error', [
                'username' => $username,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception('Gagal mengambil data audiens TikTok: ' . $response->status());
        }

        $negara = $response->json('countries') ?? $response->json('data.countries') ?? [];

        return array_map(fn(array $baris) => [
            'country' => $baris['country'] ?? '—',
            'countryCode' => $baris['countryCode'] ?? null,
            'count' => (int) ($baris['count'] ?? 0),
            'percentage' => (float) ($baris['percentage'] ?? 0),
        ], $negara);
    }

    /**
     * Get TikTok profile data from username or profile URL
     *
     * @param string $linkUserProfile TikTok username or profile URL
     * @return array
     * @throws Exception
     */
    public function getProfile(string $linkUserProfile): array
    {
        try {
            // Extract username from URL if needed
            $username = $this->extractUsername($linkUserProfile);

            Log::info('🔍 TikTok API Request', [
                'original_input' => $linkUserProfile,
                'extracted_username' => $username,
                'api_url' => "{$this->baseUrl}/profile",
            ]);

            // Make API request to get profile
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/profile", [
                        'handle' => $username,
                    ]);

            Log::info('📡 TikTok Profile API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            // Check if request was successful
            if (!$response->successful()) {
                Log::error('❌ TikTok API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Failed to fetch TikTok profile: ' . $response->body());
            }

            $data = $response->json();

            // Check if API returned success
            if (!isset($data['success']) || !$data['success']) {
                Log::error('❌ API returned unsuccessful', [
                    'response' => $data,
                ]);
                throw new Exception('API returned unsuccessful response');
            }

            // TikTok API returns data at root level
            $userData = $data['user'] ?? [];
            $statsData = $data['stats'] ?? [];

            // Get user videos for accurate engagement calculation
            $videosData = $this->getUserVideos($username);

            // Parse and return formatted data
            $parsedData = $this->parseProfileData([
                'user' => $userData,
                'stats' => $statsData,
                'videos' => $videosData,
            ]);

            Log::info('✅ TikTok Profile Parsed Successfully', [
                'username' => $parsedData['username'],
                'followers' => $parsedData['followers_count'],
                'videos' => $parsedData['media_count'],
                'engagement_rate' => $parsedData['engagement_rate'],
            ]);

            return $parsedData;

        } catch (Exception $e) {
            Log::error('💥 TikTok Service Error', [
                'message' => $e->getMessage(),
                'username' => $username ?? $linkUserProfile,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get user videos using v3 API for accurate engagement metrics
     * 
     * @param string $username
     * @param int $amount Number of videos to fetch (default 9)
     * @return array
     */
    protected function getUserVideos(string $username, int $amount = KolPostNormalizer::LIMIT): array
    {
        try {
            // Path benar per docs: /v3/tiktok/profile/videos (BUKAN "profile-videos",
            // yang selalu 404 dan membuat semua scraping TikTok jatuh ke estimasi).
            // `amount` bukan parameter yang didukung — pembatasan dilakukan di sini.
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("https://api.scrapecreators.com/v3/tiktok/profile/videos", [
                        'handle' => $username,
                        'sort_by' => 'latest',
                    ]);

            if (!$response->successful()) {
                Log::warning('TikTok Videos API Error', [
                    'status' => $response->status(),
                    'username' => $username,
                    'body' => $response->body(),
                ]);
                return [];
            }

            // Respons berupa objek {success, aweme_list: [...], max_cursor, ...},
            // bukan array video telanjang.
            $videos = $response->json('aweme_list');

            if (!is_array($videos)) {
                Log::warning('TikTok Videos API: aweme_list kosong / bukan array', [
                    'username' => $username,
                    'keys' => array_keys((array) $response->json()),
                ]);
                return [];
            }

            Log::info('🎬 TikTok Videos Fetched', [
                'username' => $username,
                'count' => count($videos),
            ]);

            return array_slice($videos, 0, $amount);

        } catch (Exception $e) {
            Log::warning('Failed to fetch user videos', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Extract username from TikTok URL or return as-is if already username
     * 
     * @param string $linkUserProfile
     * @return string
     */
    protected function extractUsername(string $linkUserProfile): string
    {
        // If it's already a username (no slashes or dots), return as-is
        if (!str_contains($linkUserProfile, '/') && !str_contains($linkUserProfile, '.')) {
            return $linkUserProfile;
        }

        // Extract username from URL patterns:
        // https://www.tiktok.com/@username
        // https://tiktok.com/@username
        // www.tiktok.com/@username
        // tiktok.com/@username

        $pattern = '/(?:https?:\/\/)?(?:www\.)?tiktok\.com\/@?([a-zA-Z0-9._]+)\/?/';

        if (preg_match($pattern, $linkUserProfile, $matches)) {
            return $matches[1];
        }

        // If no pattern matches, assume it's already a username
        return trim($linkUserProfile, '/@');
    }

    /**
     * Parse raw API data into structured format
     * 
     * @param array $profileData
     * @return array
     */
    protected function parseProfileData(array $profileData): array
    {
        $user = $profileData['user'] ?? [];
        $stats = $profileData['stats'] ?? [];
        $videos = $profileData['videos'] ?? [];

        $followerCount = $stats['followerCount'] ?? 0;

        Log::info('🔍 Parsing TikTok Profile Data', [
            'has_user' => !empty($user),
            'has_stats' => !empty($stats),
            'videos_count' => count($videos),
            'followerCount' => $followerCount,
        ]);

        // Calculate engagement metrics from v3 videos data if available
        if (!empty($videos)) {
            $engagementMetrics = $this->calculateEngagementMetrics($videos, $followerCount);
        } else {
            // Fallback: use stats data for estimation
            $engagementMetrics = $this->calculateEngagementMetricsFromStats(
                $stats['heart'] ?? $stats['heartCount'] ?? 0,
                $stats['videoCount'] ?? 0,
                $followerCount
            );
        }

        Log::info('📊 Engagement Metrics Calculated', [
            'engagement_rate' => $engagementMetrics['engagement_rate'],
            'total_engagements' => $engagementMetrics['total_engagements'],
            'average_impressions' => $engagementMetrics['average_impressions'],
        ]);

        return [
            // Basic Info
            'id' => $user['id'] ?? null,
            'username' => $user['uniqueId'] ?? null,
            'full_name' => $user['nickname'] ?? null,
            'biography' => $user['signature'] ?? null,
            'external_url' => $user['bioLink']['link'] ?? null,

            // Profile Images
            'profile_pic_url' => $user['avatarMedium'] ?? null,
            'profile_pic_url_hd' => $user['avatarLarger'] ?? null,

            // Stats
            'followers_count' => $followerCount,
            'following_count' => $stats['followingCount'] ?? 0,
            'media_count' => $stats['videoCount'] ?? 0,
            'likes_count' => $stats['heart'] ?? $stats['heartCount'] ?? 0,

            // Tier (calculated from followers)
            'tier' => $this->calculateTier($followerCount),

            // Engagement Metrics (calculated from recent posts)
            'engagement_rate' => $engagementMetrics['engagement_rate'],
            'total_engagements' => $engagementMetrics['total_engagements'],
            'average_likes' => $engagementMetrics['average_likes'],
            'average_comments' => $engagementMetrics['average_comments'],
            'average_impressions' => $engagementMetrics['average_impressions'],

            // Account Type
            'is_private' => $user['privateAccount'] ?? false,
            'is_verified' => $user['verified'] ?? false,
            'is_business_account' => false, // TikTok API doesn't provide this in public profile
            'is_professional_account' => false, // TikTok API doesn't provide this in public profile

            // Business Info
            'business_category_name' => null,
            'category_name' => null,
            'business_email' => null,
            'business_phone_number' => null,
            'business_address' => null,

            // Bio Links
            'bio_links' => [],

            // Additional Info
            'sec_uid' => $user['secUid'] ?? null,
            'create_time' => $user['createTime'] ?? null,
            'created_at' => isset($user['createTime'])
                ? date('Y-m-d H:i:s', $user['createTime'])
                : null,
            'is_download_banned' => $user['downloadSetting'] ?? 0,
            'comment_setting' => $user['commentSetting'] ?? 0,
            'duet_setting' => $user['duetSetting'] ?? 0,
            'stitch_setting' => $user['stitchSetting'] ?? 0,

            // Recent Media (first video if available)
            'recent_media' => $this->parseRecentMedia($videos),

            // Raw data (for debugging or additional processing)
            'raw_data' => $profileData,
        ];
    }

    /**
     * Calculate engagement metrics from recent videos using v3 API data
     * This method provides ACCURATE engagement including saves, shares, and reposts
     * 
     * Requirements from user:
     * - Avg views: Average of 9 videos (>24 hours old) = AVERAGE(total views 9 postingan)
     * - Total Engagement: Total engagement dari 9 videos (likes + comments + saves + shares + reposts)
     * - ER%: (Average Engagement per Video / Followers) × 100
     * 
     * @param array $videos Array of video data from v3 API
     * @param int $followerCount Follower count for ER calculation
     * @return array
     */
    protected function calculateEngagementMetrics(array $videos, int $followerCount): array
    {
        Log::info('📈 Starting Engagement Calculation', [
            'videos_count' => count($videos),
            'followerCount' => $followerCount,
        ]);

        // If no followers, return zeros
        if ($followerCount === 0) {
            Log::warning('⚠️ No followers found');

            return [
                'engagement_rate' => 0,
                'total_engagements' => 0,
                'average_likes' => 0,
                'average_comments' => 0,
                'average_impressions' => 0,
            ];
        }

        // If no videos available
        if (empty($videos)) {
            Log::warning('⚠️ No videos data from v3 API');

            return [
                'engagement_rate' => 0,
                'total_engagements' => 0,
                'average_likes' => 0,
                'average_comments' => 0,
                'average_impressions' => 0,
            ];
        }

        $now = time();
        $oneDayAgo = $now - (24 * 60 * 60);

        // Filter videos older than 24 hours
        $validVideos = array_filter($videos, function ($video) use ($oneDayAgo) {
            $createTime = $video['create_time'] ?? 0;
            return $createTime > 0 && $createTime < $oneDayAgo;
        });

        // Limit to 9 videos
        $validVideos = array_slice($validVideos, 0, 9);

        // If no videos older than 24h, use all recent videos
        if (empty($validVideos)) {
            Log::warning('⚠️ No videos older than 24h, using all recent videos');
            $validVideos = array_slice($videos, 0, 9);
        }

        if (empty($validVideos)) {
            return [
                'engagement_rate' => 0,
                'total_engagements' => 0,
                'average_likes' => 0,
                'average_comments' => 0,
                'average_impressions' => 0,
            ];
        }

        $totalLikes = 0;
        $totalComments = 0;
        $totalShares = 0;
        $totalSaves = 0;
        $totalViews = 0;
        $videoCount = count($validVideos);
        $perPostEngagement = [];

        foreach ($validVideos as $index => $video) {
            // Get stats from video statistics object (v3 API structure)
            $stats = $video['statistics'] ?? [];

            $likes = $stats['digg_count'] ?? 0;
            $comments = $stats['comment_count'] ?? 0;
            $shares = $stats['share_count'] ?? 0;
            $saves = $stats['collect_count'] ?? 0;
            $views = $stats['play_count'] ?? 0;

            $perPostEngagement[] = [
                'engagement' => $likes + $comments + $shares + $saves,
                'views' => $views,
            ];

            Log::info("📊 Video #{$index} Stats", [
                'aweme_id' => $video['aweme_id'] ?? 'unknown',
                'create_time' => isset($video['create_time']) ? date('Y-m-d H:i:s', $video['create_time']) : 'N/A',
                'likes' => $likes,
                'comments' => $comments,
                'shares' => $shares,
                'saves' => $saves,
                'views' => $views,
            ]);

            $totalLikes += $likes;
            $totalComments += $comments;
            $totalShares += $shares;
            $totalSaves += $saves;
            $totalViews += $views;
        }

        // Calculate averages for individual metrics
        $averageLikes = $videoCount > 0 ? round($totalLikes / $videoCount) : 0;
        $averageComments = $videoCount > 0 ? round($totalComments / $videoCount) : 0;

        // Total Engagement = Sum of (likes + comments + saves + shares) dari 9 videos
        $totalEngagements = $totalLikes + $totalComments + $totalSaves + $totalShares;

        // Average Engagement per Video (untuk ER% calculation)
        $averageEngagementPerVideo = $videoCount > 0 ? $totalEngagements / $videoCount : 0;

        // ER% standar: TikTok = konten video, basis views (reach), bukan followers
        $engagementRate = $this->averageEngagementRate($perPostEngagement, $followerCount);

        // Avg Views = AVERAGE dari total views 9 videos
        $averageImpressions = $videoCount > 0 ? round($totalViews / $videoCount) : 0;

        Log::info('✅ Final Engagement Metrics', [
            'videoCount' => $videoCount,
            'totalEngagements' => $totalEngagements,
            'averageEngagementPerVideo' => round($averageEngagementPerVideo),
            'totalLikes' => $totalLikes,
            'totalComments' => $totalComments,
            'totalShares' => $totalShares,
            'totalSaves' => $totalSaves,
            'totalViews' => $totalViews,
            'engagementRate' => $engagementRate,
            'averageImpressions' => $averageImpressions,
        ]);

        return [
            'engagement_rate' => $engagementRate,
            'total_engagements' => $totalEngagements, // Total dari 9 videos
            'average_likes' => $averageLikes,
            'average_comments' => $averageComments,
            'average_impressions' => $averageImpressions,
        ];
    }

    /**
     * Calculate engagement metrics from stats data (FALLBACK method)
     * Used when v3 API videos are not available
     * 
     * @param int $totalHearts Total likes from stats.heart
     * @param int $videoCount Total video count from stats.videoCount
     * @param int $followerCount Follower count for ER calculation
     * @return array
     */
    protected function calculateEngagementMetricsFromStats(int $totalHearts, int $videoCount, int $followerCount): array
    {
        if ($followerCount === 0) {
            return [
                'engagement_rate' => 0,
                'total_engagements' => 0,
                'average_likes' => 0,
                'average_comments' => 0,
                'average_impressions' => 0,
            ];
        }

        // Engagement Rate based on average likes per video
        // Formula: (Total Likes / Videos) / Followers × 100%
        $averageLikesPerVideo = $videoCount > 0
            ? $totalHearts / $videoCount
            : $totalHearts;

        // Fallback tanpa data per-video views → basis followers, di-clamp via helper
        $engagementRate = $this->engagementRateForPost((float) $averageLikesPerVideo, 0.0, $followerCount) ?? 0.0;

        // Estimate total engagements from average of 9 videos
        // Assume 15-20% of likes are comments/shares/saves
        $estimatedEngagements = $averageLikesPerVideo * 9 * 1.18; // 9 videos × 18% engagement multiplier

        $estimatedImpressions = $followerCount * ($followerCount < 1000 ? 0.15 : 0.075);

        Log::warning('⚠️ Using fallback stats calculation', [
            'totalHearts' => $totalHearts,
            'videoCount' => $videoCount,
            'engagement_rate' => round($engagementRate, 2),
            'estimated_engagements' => round($estimatedEngagements),
            'estimated_impressions' => round($estimatedImpressions),
            'followerCount' => $followerCount,
        ]);

        return [
            'engagement_rate' => round($engagementRate, 2),
            'total_engagements' => round($estimatedEngagements),
            'average_likes' => round($averageLikesPerVideo),
            'average_comments' => 0,
            'average_impressions' => round($estimatedImpressions),
        ];
    }

    /**
     * Calculate tier based on followers count
     * 
     * @param int $followersCount
     * @return string
     */
    protected function calculateTier(int $followersCount): string
    {
        if ($followersCount >= 1000000) {
            return 'Mega';
        } elseif ($followersCount >= 100000) {
            return 'Macro';
        } elseif ($followersCount >= 10000) {
            return 'Micro';
        } elseif ($followersCount >= 1000) {
            return 'Nano';
        } else {
            return 'Mini'; // Below 1,000 followers
        }
    }

    /**
     * Parse recent media videos from v3 API
     * 
     * @param array $videos Videos from v3 API (/v3/tiktok/profile-videos)
     * @return array
     */
    protected function parseRecentMedia(array $videos): array
    {
        return array_map(function ($video) {
            // v3 API structure: statistics object at video level
            $stats = $video['statistics'] ?? [];
            $videoData = $video['video'] ?? [];

            return [
                'id' => $video['aweme_id'] ?? null,
                'desc' => $video['desc'] ?? '',
                'type' => 'video',
                'is_video' => true,
                'display_url' => $videoData['dynamic_cover']['url_list'][0] ?? null,
                'thumbnail_src' => $videoData['cover']['url_list'][0] ?? $videoData['dynamic_cover']['url_list'][0] ?? null,
                'video_url' => $videoData['play_addr']['url_list'][0] ?? null,
                'video_view_count' => $stats['play_count'] ?? 0,
                'likes_count' => $stats['digg_count'] ?? 0,
                'comments_count' => $stats['comment_count'] ?? 0,
                'shares_count' => $stats['share_count'] ?? 0,
                'bookmark_count' => $stats['collect_count'] ?? 0,
                'taken_at_timestamp' => $video['create_time'] ?? null,
                'taken_at' => isset($video['create_time'])
                    ? date('Y-m-d H:i:s', $video['create_time'])
                    : null,
            ];
        }, array_slice($videos, 0, 12)); // Limit to first 12 videos
    }

    /**
     * Get simplified profile data (only essential fields)
     * 
     * @param string $linkUserProfile
     * @return array
     */
    public function getSimpleProfile(string $linkUserProfile): array
    {
        $fullProfile = $this->getProfile($linkUserProfile);

        return [
            'username' => $fullProfile['username'],
            'full_name' => $fullProfile['full_name'],
            'biography' => $fullProfile['biography'],
            'profile_pic_url' => $fullProfile['profile_pic_url_hd'] ?? $fullProfile['profile_pic_url'],
            'followers_count' => $fullProfile['followers_count'],
            'following_count' => $fullProfile['following_count'],
            'media_count' => $fullProfile['media_count'],
            'is_verified' => $fullProfile['is_verified'],
            'is_business' => $fullProfile['is_business_account'],
            'external_url' => $fullProfile['external_url'],
        ];
    }

    /**
     * Get post/video stats from TikTok URL
     * 
     * @param string $postUrl TikTok video URL
     * @return array
     * @throws Exception
     */
    public function getPostStats(string $postUrl): array
    {
        try {
            Log::info('🔍 TikTok Post Stats Request', [
                'url' => $postUrl,
            ]);

            // Call API to get video details (v2 endpoint)
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                ])->get("https://api.scrapecreators.com/v2/tiktok/video", [
                        'url' => $postUrl,
                    ]);

            Log::info('📡 TikTok Video API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                Log::error('❌ TikTok Video API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('Failed to fetch TikTok video: ' . $response->body());
            }

            $data = $response->json();

            Log::info('📦 TikTok Video Raw Response Keys', [
                'keys' => array_keys($data ?? []),
            ]);

            // v2 response structure: { aweme_detail: { statistics: {...}, author: {...}, ... } }
            if (isset($data['success']) && !$data['success']) {
                throw new Exception('API returned unsuccessful response: ' . ($data['message'] ?? 'Unknown error'));
            }

            // v2 wraps everything under 'aweme_detail'
            $videoData = $data['aweme_detail'] ?? $data['data'] ?? $data;

            Log::info('📦 TikTok v2 aweme_detail keys', [
                'keys' => array_keys($videoData),
            ]);

            // Extract stats from response
            $stats = $videoData['statistics'] ?? [];
            $author = $videoData['author'] ?? [];

            Log::info('📦 TikTok v2 author fields', [
                'author_keys' => array_keys($author),
                'followerCount' => $author['followerCount'] ?? 'N/A',
                'follower_count' => $author['follower_count'] ?? 'N/A',
                'fans' => $author['fans'] ?? 'N/A',
                'followingCount' => $author['followingCount'] ?? 'N/A',
            ]);

            // Extract values (API may return -1 when data is not publicly available)
            $views = $stats['play_count'] ?? 0;
            $likes = $stats['digg_count'] ?? 0;
            $comments = $stats['comment_count'] ?? 0;
            $saves = $stats['collect_count'] ?? 0;
            $shares = $stats['share_count'] ?? 0;

            Log::info('📊 TikTok v2 raw stats', [
                'play_count' => $views,
                'digg_count' => $likes,
                'comment_count' => $comments,
                'collect_count' => $saves,
                'share_count' => $shares,
            ]);

            // Handle -1 values (API returns -1 when data is not publicly available)
            $followersCount = max(0, (int) ($author['followerCount'] ?? $author['follower_count'] ?? 0));

            $result = [
                'username' => $author['unique_id'] ?? $author['uniqueId'] ?? null,
                'followers_count' => $followersCount,
                'views' => max(0, $views),
                'likes' => max(0, $likes),
                'comments' => max(0, $comments),
                'saves' => max(0, $saves),
                'shares' => max(0, $shares),
            ];

            // Calculate total engagement
            $result['total_engagement'] = $result['likes'] + $result['comments'] + $result['saves'] + $result['shares'];

            Log::info('✅ TikTok Post Stats Retrieved', $result);

            return $result;

        } catch (Exception $e) {
            Log::error('💥 TikTok Post Stats Error', [
                'url' => $postUrl,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}