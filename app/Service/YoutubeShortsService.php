<?php

namespace App\Service;

use App\Service\KolPostNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class YoutubeShortsService
{
    use \App\Service\Concerns\CalculatesEngagementRate;

    protected string $apiKey;
    protected string $baseUrl = 'https://api.scrapecreators.com/v1/youtube';

    public function __construct()
    {
        $apiKey = config('services.scrapecreators.api_key') ?? env('SCRAPECREATORS_API_KEY');

        if (empty($apiKey)) {
            throw new Exception('ScrapeCreators API key is not configured');
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Get YouTube Shorts Channel profile data from channel ID, handle, or URL
     * 
     * @param string $linkUserProfile YouTube channel ID, handle, or URL
     * @return array
     * @throws Exception
     */
    public function getProfile(string $linkUserProfile): array
    {
        try {
            // Extract channel identifier from URL if needed
            $channelIdentifier = $this->extractChannelIdentifier($linkUserProfile);

            Log::info('🔍 YouTube Shorts API Request', [
                'original_input' => $linkUserProfile,
                'extracted_identifier' => $channelIdentifier,
                'api_url' => "{$this->baseUrl}/channel",
            ]);

            // Determine if it's a channel ID, handle, or URL
            $params = $this->buildChannelParams($channelIdentifier);

            // Make API request to get channel profile
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/channel", $params);

            Log::info('📡 YouTube Shorts Channel API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            // Check if request was successful
            if (!$response->successful()) {
                Log::error('❌ YouTube Shorts Channel API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Failed to fetch YouTube channel: ' . $response->body());
            }

            $channelData = $response->json();

            // Get channel shorts for accurate engagement calculation
            $shortsData = $this->getChannelShorts($channelIdentifier);

            Log::info('📊 YouTube Channel Shorts Fetched', [
                'channel' => $channelData['name'] ?? 'Unknown',
                'shorts_count' => count($shortsData),
            ]);

            // Parse and return formatted data
            $parsedData = $this->parseProfileData($channelData, $shortsData);

            Log::info('✅ YouTube Shorts Profile Parsed Successfully', [
                'channel' => $parsedData['username'],
                'subscribers' => $parsedData['followers_count'],
                'shorts' => count($shortsData),
                'engagement_rate' => $parsedData['engagement_rate'],
            ]);

            return $parsedData;

        } catch (Exception $e) {
            Log::error('💥 YouTube Shorts Service Error', [
                'message' => $e->getMessage(),
                'input' => $linkUserProfile,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get channel shorts for accurate engagement metrics
     * Using regular shorts endpoint (not simple) to get likes and comments
     * 
     * @param string $channelIdentifier Channel ID or handle
     * @param int $amount Number of shorts to fetch (default 9)
     * @return array
     */
    protected function getChannelShorts(string $channelIdentifier, int $amount = KolPostNormalizer::LIMIT): array
    {
        try {
            Log::info('📡 Fetching YouTube Channel Shorts', [
                'channel' => $channelIdentifier,
                'amount' => $amount,
                'endpoint' => "{$this->baseUrl}/channel/shorts",
            ]);

            $params = $this->buildChannelParams($channelIdentifier);
            $params['sort'] = 'newest';

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/channel/shorts", $params);

            Log::info('📡 YouTube Channel Shorts API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                Log::warning('❌ YouTube Channel Shorts API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();

            // API returns object with 'shorts' array
            $shorts = $data['shorts'] ?? [];

            if (!is_array($shorts)) {
                Log::warning('⚠️ Unexpected response format', [
                    'type' => gettype($shorts),
                ]);
                return [];
            }

            // Limit to requested amount
            $shorts = array_slice($shorts, 0, $amount);

            Log::info('✅ YouTube Channel Shorts Retrieved Successfully', [
                'channel' => $channelIdentifier,
                'shorts_count' => count($shorts),
            ]);

            return $shorts;

        } catch (Exception $e) {
            Log::error('💥 Failed to fetch channel shorts', [
                'channel' => $channelIdentifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Extract channel identifier (ID or handle) from YouTube URL or return as-is
     * 
     * @param string $linkUserProfile
     * @return string
     */
    protected function extractChannelIdentifier(string $linkUserProfile): string
    {
        // If it's already a clean identifier (no slashes or dots), return as-is
        if (!str_contains($linkUserProfile, '/') && !str_contains($linkUserProfile, '.')) {
            return $linkUserProfile;
        }

        // Extract from URL patterns:
        // https://www.youtube.com/@handle
        // https://youtube.com/channel/UCxxx
        // www.youtube.com/@handle
        // youtube.com/@handle

        // Try to extract handle (@username)
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?youtube\.com\/@([a-zA-Z0-9._-]+)\/?/', $linkUserProfile, $matches)) {
            return $matches[1];
        }

        // Try to extract channel ID
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?youtube\.com\/channel\/([a-zA-Z0-9_-]+)\/?/', $linkUserProfile, $matches)) {
            return $matches[1];
        }

        // If no pattern matches, return cleaned version
        return trim($linkUserProfile, '/@');
    }

    /**
     * Build API parameters based on channel identifier type
     * 
     * @param string $identifier
     * @return array
     */
    protected function buildChannelParams(string $identifier): array
    {
        // Check if it's a channel ID (starts with UC and is 24 chars)
        if (preg_match('/^UC[a-zA-Z0-9_-]{22}$/', $identifier)) {
            return ['channelId' => $identifier];
        }

        // Otherwise, treat as handle
        return ['handle' => $identifier];
    }

    /**
     * Parse raw API data into structured format
     * 
     * @param array $channelData Channel profile data from channel endpoint
     * @param array $shortsData Shorts data for accurate engagement
     * @return array
     */
    protected function parseProfileData(array $channelData, array $shortsData = []): array
    {
        $subscriberCount = $channelData['subscriberCount'] ?? 0;

        Log::info('🔍 Parsing YouTube Shorts Data', [
            'has_channel' => !empty($channelData),
            'has_shorts' => !empty($shortsData),
            'shorts_count' => count($shortsData),
            'subscriberCount' => $subscriberCount,
        ]);

        // Calculate engagement metrics from shorts data if available
        if (!empty($shortsData)) {
            $engagementMetrics = $this->calculateEngagementMetrics($shortsData, $subscriberCount);
        } else {
            // No shorts data available
            $engagementMetrics = [
                'engagement_rate' => 0,
                'total_engagements' => 0,
                'average_likes' => 0,
                'average_comments' => 0,
                'average_impressions' => 0,
            ];
        }

        Log::info('📊 Engagement Metrics Calculated', [
            'engagement_rate' => $engagementMetrics['engagement_rate'],
            'total_engagements' => $engagementMetrics['total_engagements'],
            'average_impressions' => $engagementMetrics['average_impressions'],
        ]);

        // Extract email from channel data if available
        $email = $channelData['email'] ?? null;

        // Extract social links
        $links = $channelData['links'] ?? [];
        $twitter = null;
        $instagram = null;

        foreach ($links as $link) {
            if (str_contains($link, 'twitter.com') || str_contains($link, 'x.com')) {
                $twitter = $link;
            } elseif (str_contains($link, 'instagram.com')) {
                $instagram = $link;
            }
        }

        return [
            // Basic Info
            'id' => $channelData['channelId'] ?? null,
            'username' => $channelData['name'] ?? null,
            'full_name' => $channelData['name'] ?? null,
            'biography' => $channelData['description'] ?? null,
            'external_url' => $channelData['channel'] ?? null,

            // Profile Images
            'profile_pic_url' => $channelData['avatar']['image']['sources'][0]['url'] ?? null,
            'profile_pic_url_hd' => $channelData['avatar']['image']['sources'][2]['url'] ??
                $channelData['avatar']['image']['sources'][0]['url'] ?? null,

            // Stats
            'followers_count' => $subscriberCount,
            'following_count' => 0, // YouTube doesn't provide this
            'media_count' => count($shortsData), // Count of shorts fetched

            // Tier (calculated from subscribers)
            'tier' => $this->calculateTier($subscriberCount),

            // Engagement Metrics (calculated from recent shorts)
            'engagement_rate' => $engagementMetrics['engagement_rate'],
            'total_engagements' => $engagementMetrics['total_engagements'],
            'average_likes' => $engagementMetrics['average_likes'],
            'average_comments' => $engagementMetrics['average_comments'],
            'average_impressions' => $engagementMetrics['average_impressions'],

            // Account Type
            'is_private' => false, // YouTube channels are public
            'is_verified' => false, // Not directly available in API
            'is_business_account' => false,
            'is_professional_account' => false,

            // Business Info
            'business_category_name' => null,
            'category_name' => null,
            'business_email' => $email,
            'business_phone_number' => null,
            'business_address' => null,

            // Bio Links
            'bio_links' => $links,

            // Additional Info
            'subscriber_text' => $channelData['subscriberCountText'] ?? null,
            'video_count_text' => $channelData['videoCountText'] ?? null,
            'view_count_text' => $channelData['viewCountText'] ?? null,
            'joined_date' => $channelData['joinedDateText'] ?? null,
            'country' => $channelData['country'] ?? null,
            'tags' => $channelData['tags'] ?? null,
            'store' => $channelData['store'] ?? null,
            'twitter' => $twitter,
            'instagram' => $instagram,

            // Recent Media (shorts)
            'recent_media' => $this->parseRecentMedia($shortsData),

            // Raw data (for debugging or additional processing)
            'raw_data' => $channelData,
        ];
    }

    /**
     * Calculate engagement metrics from recent shorts
     * This method provides ACCURATE engagement from shorts data
     * 
     * Engagement Formula: likes + comments + saves + shares + reposts
     * 
     * Note: YouTube API currently only provides:
     * - likeCountInt: Likes count
     * - commentCountInt: Comments count  
     * - viewCountInt: View count
     * 
     * YouTube does NOT provide:
     * - saveCount: Save/Bookmark count (not exposed by YouTube)
     * - shareCount: Share count (not exposed by YouTube)
     * - repostCount: YouTube doesn't have repost feature
     * 
     * Requirements:
     * - Avg views: Average of 9 shorts = AVERAGE(total views 9 shorts)
     * - Total Engagement: Total engagement dari 9 shorts (likes + comments + saves + shares + reposts)
     * - ER%: (Average Engagement per Short / Subscribers) × 100
     * 
     * Note: YouTube Shorts API from /simple endpoint doesn't provide publish date,
     * so we can't filter by 24h. We'll use the most recent shorts returned.
     * 
     * @param array $shorts Array of shorts data
     * @param int $subscriberCount Subscriber count for ER calculation
     * @return array
     */
    protected function calculateEngagementMetrics(array $shorts, int $subscriberCount): array
    {
        if (empty($shorts) || $subscriberCount === 0) {
            return [
                'engagement_rate' => 0,
                'total_engagements' => 0,
                'average_likes' => 0,
                'average_comments' => 0,
                'average_impressions' => 0,
            ];
        }

        Log::info('📈 Starting YouTube Shorts Engagement Calculation', [
            'shorts_count' => count($shorts),
            'subscriberCount' => $subscriberCount,
        ]);

        // Limit to 9 shorts (most recent)
        $validShorts = array_slice($shorts, 0, 9);

        $totalLikes = 0;
        $totalComments = 0;
        $totalSaves = 0;    // Not available in YouTube API
        $totalShares = 0;   // Not available in YouTube API
        $totalReposts = 0;  // YouTube doesn't have repost feature
        $totalViews = 0;
        $shortsCount = count($validShorts);
        $perPostEngagement = [];

        foreach ($validShorts as $index => $short) {
            $likes = $short['likeCountInt'] ?? 0;
            $comments = $short['commentCountInt'] ?? 0;
            $views = $short['viewCountInt'] ?? 0;

            $perPostEngagement[] = [
                'engagement' => $likes + $comments,
                'views' => $views,
            ];

            // These fields are not currently provided by YouTube API
            // but we include them for future-proofing and consistency with other platforms
            $saves = $short['saveCountInt'] ?? $short['bookmarkCountInt'] ?? 0;
            $shares = $short['shareCountInt'] ?? 0;
            $reposts = $short['repostCountInt'] ?? 0;

            Log::info("📊 Short #{$index} Stats", [
                'short_id' => $short['id'] ?? 'unknown',
                'title' => substr($short['title'] ?? 'N/A', 0, 50),
                'likes' => $likes,
                'comments' => $comments,
                'saves' => $saves,
                'shares' => $shares,
                'reposts' => $reposts,
                'views' => $views,
            ]);

            $totalLikes += $likes;
            $totalComments += $comments;
            $totalSaves += $saves;
            $totalShares += $shares;
            $totalReposts += $reposts;
            $totalViews += $views;
        }

        // Calculate averages for individual metrics
        $averageLikes = $shortsCount > 0 ? round($totalLikes / $shortsCount) : 0;
        $averageComments = $shortsCount > 0 ? round($totalComments / $shortsCount) : 0;

        // Total Engagement = Sum of (likes + comments + saves + shares + reposts) dari 9 shorts
        // Note: Currently saves, shares, reposts will be 0 as YouTube API doesn't provide them
        $totalEngagements = $totalLikes + $totalComments + $totalSaves + $totalShares + $totalReposts;

        // Average Engagement per Short (untuk ER% calculation)
        $averageEngagementPerShort = $shortsCount > 0 ? $totalEngagements / $shortsCount : 0;

        // ER% standar: Shorts = konten video, basis views (reach), bukan subscribers
        $engagementRate = $this->averageEngagementRate($perPostEngagement, $subscriberCount);

        // Avg Views = AVERAGE dari total views 9 shorts
        $averageImpressions = $shortsCount > 0 ? round($totalViews / $shortsCount) : 0;

        Log::info('✅ Final YouTube Shorts Engagement Metrics', [
            'shortsCount' => $shortsCount,
            'totalEngagements' => $totalEngagements,
            'averageEngagementPerShort' => round($averageEngagementPerShort),
            'totalLikes' => $totalLikes,
            'totalComments' => $totalComments,
            'totalSaves' => $totalSaves,
            'totalShares' => $totalShares,
            'totalReposts' => $totalReposts,
            'totalViews' => $totalViews,
            'engagementRate' => $engagementRate,
            'averageImpressions' => $averageImpressions,
            'note' => 'saves, shares, reposts are 0 - YouTube API limitation',
        ]);

        return [
            'engagement_rate' => $engagementRate,
            'total_engagements' => $totalEngagements, // Total dari 9 shorts
            'average_likes' => $averageLikes,
            'average_comments' => $averageComments,
            'average_impressions' => $averageImpressions,
        ];
    }

    /**
     * Calculate tier based on subscriber count
     * 
     * @param int $subscriberCount
     * @return string
     */
    protected function calculateTier(int $subscriberCount): string
    {
        // Ambangnya master data (halaman Masterdata Media Plan Internal), bukan
        // tangga if di tiap service — dulu Threads bahkan memakai huruf kecil
        // dan band "mid" yang tidak dikenal modul lain.
        return \App\Models\MediaPlanCalcSetting::current()->tierFor($subscriberCount);
    }

    /**
     * Parse recent media shorts
     * 
     * @param array $shorts Shorts from channel/shorts/simple API
     * @return array
     */
    protected function parseRecentMedia(array $shorts): array
    {
        return array_map(function ($short) {
            return [
                'id' => $short['id'] ?? null,
                'title' => $short['title'] ?? '',
                'description' => null, // Simple endpoint doesn't provide description
                'type' => 'short',
                'is_video' => true,
                'display_url' => $short['thumbnail'] ?? null,
                'thumbnail_src' => $short['thumbnail'] ?? null,
                'video_url' => $short['url'] ?? null,
                'video_view_count' => $short['viewCountInt'] ?? 0,
                'likes_count' => $short['likeCountInt'] ?? 0,
                'comments_count' => $short['commentCountInt'] ?? 0,
                'view_count_text' => $short['viewCountText'] ?? null,
                'like_count_text' => $short['likeCountText'] ?? null,
                'comment_count_text' => $short['commentCountText'] ?? null,
            ];
        }, array_slice($shorts, 0, 12)); // Limit to first 12 shorts
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
}
