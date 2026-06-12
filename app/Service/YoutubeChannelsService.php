<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class YoutubeChannelsService
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
     * Get YouTube Channel profile data from channel ID, handle, or URL
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

            Log::info('🔍 YouTube Channel API Request', [
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

            Log::info('📡 YouTube Channel API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            // Check if request was successful
            if (!$response->successful()) {
                Log::error('❌ YouTube Channel API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Failed to fetch YouTube channel: ' . $response->body());
            }

            $channelData = $response->json();

            // Get channel videos for accurate engagement calculation
            $videosData = $this->getChannelVideos($channelIdentifier);

            Log::info('📊 YouTube Channel Videos Fetched', [
                'channel' => $channelData['name'] ?? 'Unknown',
                'videos_count' => count($videosData),
            ]);

            // Parse and return formatted data
            $parsedData = $this->parseProfileData($channelData, $videosData);

            Log::info('✅ YouTube Channel Profile Parsed Successfully', [
                'channel' => $parsedData['username'],
                'subscribers' => $parsedData['followers_count'],
                'videos' => $parsedData['media_count'],
                'engagement_rate' => $parsedData['engagement_rate'],
            ]);

            return $parsedData;

        } catch (Exception $e) {
            Log::error('💥 YouTube Channel Service Error', [
                'message' => $e->getMessage(),
                'input' => $linkUserProfile,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get channel videos for accurate engagement metrics
     * 
     * @param string $channelIdentifier Channel ID or handle
     * @param int $count Number of videos to fetch (default 9)
     * @return array
     */
    protected function getChannelVideos(string $channelIdentifier, int $count = 9): array
    {
        try {
            Log::info('📡 Fetching YouTube Channel Videos', [
                'channel' => $channelIdentifier,
                'count' => $count,
                'endpoint' => "{$this->baseUrl}/channel-videos",
            ]);

            $params = $this->buildChannelParams($channelIdentifier);
            $params['sort'] = 'latest';
            $params['includeExtras'] = 'true'; // Get like and comment counts

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/channel-videos", $params);

            Log::info('📡 YouTube Channel Videos API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                Log::warning('❌ YouTube Channel Videos API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();
            $videos = $data['videos'] ?? [];

            // Limit to requested count
            $videos = array_slice($videos, 0, $count);

            Log::info('✅ YouTube Channel Videos Retrieved Successfully', [
                'channel' => $channelIdentifier,
                'videos_count' => count($videos),
            ]);

            return $videos;

        } catch (Exception $e) {
            Log::error('💥 Failed to fetch channel videos', [
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
     * @param array $videosData Videos data for accurate engagement
     * @return array
     */
    protected function parseProfileData(array $channelData, array $videosData = []): array
    {
        $subscriberCount = $channelData['subscriberCount'] ?? 0;

        Log::info('🔍 Parsing YouTube Channel Data', [
            'has_channel' => !empty($channelData),
            'has_videos' => !empty($videosData),
            'videos_count' => count($videosData),
            'subscriberCount' => $subscriberCount,
        ]);

        // Calculate engagement metrics from videos data if available
        if (!empty($videosData)) {
            $engagementMetrics = $this->calculateEngagementMetrics($videosData, $subscriberCount);
        } else {
            // No videos data available
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
            'media_count' => $this->parseVideoCount($channelData['videoCountText'] ?? '0'),

            // Tier (calculated from subscribers)
            'tier' => $this->calculateTier($subscriberCount),

            // Engagement Metrics (calculated from recent videos)
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

            // Recent Media (first video if available)
            'recent_media' => $this->parseRecentMedia($videosData),

            // Raw data (for debugging or additional processing)
            'raw_data' => $channelData,
        ];
    }

    /**
     * Calculate engagement metrics from recent videos
     * This method provides ACCURATE engagement from video data
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
     * Requirements from user:
     * - Avg views: Average of 9 videos (>24 hours old) = AVERAGE(total views 9 videos)
     * - Total Engagement: Total engagement dari 9 videos (likes + comments + saves + shares + reposts)
     * - ER%: (Average Engagement per Video / Followers) × 100
     * 
     * @param array $videos Array of video data
     * @param int $subscriberCount Subscriber count for ER calculation
     * @return array
     */
    protected function calculateEngagementMetrics(array $videos, int $subscriberCount): array
    {
        if (empty($videos) || $subscriberCount === 0) {
            return [
                'engagement_rate' => 0,
                'total_engagements' => 0,
                'average_likes' => 0,
                'average_comments' => 0,
                'average_impressions' => 0,
            ];
        }

        Log::info('📈 Starting YouTube Engagement Calculation', [
            'videos_count' => count($videos),
            'subscriberCount' => $subscriberCount,
        ]);

        // Filter videos older than 24 hours
        $now = time();
        $oneDayAgo = $now - (24 * 60 * 60);

        $validVideos = array_filter($videos, function ($video) use ($oneDayAgo) {
            $publishTime = isset($video['publishedTime']) ? strtotime($video['publishedTime']) : 0;
            return $publishTime > 0 && $publishTime < $oneDayAgo;
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
        $totalSaves = 0;    // Not available in YouTube API
        $totalShares = 0;   // Not available in YouTube API
        $totalReposts = 0;  // YouTube doesn't have repost feature
        $totalViews = 0;
        $videoCount = count($validVideos);
        $perPostEngagement = [];

        foreach ($validVideos as $index => $video) {
            $likes = $video['likeCountInt'] ?? 0;
            $comments = $video['commentCountInt'] ?? 0;
            $views = $video['viewCountInt'] ?? 0;

            $perPostEngagement[] = [
                'engagement' => $likes + $comments,
                'views' => $views,
            ];

            // These fields are not currently provided by YouTube API
            // but we include them for future-proofing and consistency with other platforms
            $saves = $video['saveCountInt'] ?? $video['bookmarkCountInt'] ?? 0;
            $shares = $video['shareCountInt'] ?? 0;
            $reposts = $video['repostCountInt'] ?? 0;

            Log::info("📊 Video #{$index} Stats", [
                'video_id' => $video['id'] ?? 'unknown',
                'title' => substr($video['title'] ?? 'N/A', 0, 50),
                'publishedTime' => $video['publishedTimeText'] ?? 'N/A',
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
        $averageLikes = $videoCount > 0 ? round($totalLikes / $videoCount) : 0;
        $averageComments = $videoCount > 0 ? round($totalComments / $videoCount) : 0;

        // Total Engagement = Sum of (likes + comments + saves + shares + reposts) dari 9 videos
        // Note: Currently saves, shares, reposts will be 0 as YouTube API doesn't provide them
        $totalEngagements = $totalLikes + $totalComments + $totalSaves + $totalShares + $totalReposts;

        // Average Engagement per Video (untuk ER% calculation)
        $averageEngagementPerVideo = $videoCount > 0 ? $totalEngagements / $videoCount : 0;

        // ER% standar: YouTube = konten video, basis views (reach), bukan subscribers
        $engagementRate = $this->averageEngagementRate($perPostEngagement, $subscriberCount);

        // Avg Views = AVERAGE dari total views 9 videos
        $averageImpressions = $videoCount > 0 ? round($totalViews / $videoCount) : 0;

        Log::info('✅ Final YouTube Engagement Metrics', [
            'videoCount' => $videoCount,
            'totalEngagements' => $totalEngagements,
            'averageEngagementPerVideo' => round($averageEngagementPerVideo),
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
            'total_engagements' => $totalEngagements, // Total dari 9 videos
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
        if ($subscriberCount >= 1000000) {
            return 'Mega';
        } elseif ($subscriberCount >= 100000) {
            return 'Macro';
        } elseif ($subscriberCount >= 10000) {
            return 'Micro';
        } elseif ($subscriberCount >= 1000) {
            return 'Nano';
        } else {
            return 'Mini'; // Below 1,000 subscribers
        }
    }

    /**
     * Parse video count text to integer
     * Example: "9,221 videos" -> 9221
     * 
     * @param string $videoCountText
     * @return int
     */
    protected function parseVideoCount(string $videoCountText): int
    {
        // Remove non-numeric characters except commas
        $cleaned = preg_replace('/[^0-9,]/', '', $videoCountText);
        // Remove commas
        $cleaned = str_replace(',', '', $cleaned);
        return (int) $cleaned;
    }

    /**
     * Parse recent media videos
     * 
     * @param array $videos Videos from channel-videos API
     * @return array
     */
    protected function parseRecentMedia(array $videos): array
    {
        return array_map(function ($video) {
            return [
                'id' => $video['id'] ?? null,
                'title' => $video['title'] ?? '',
                'description' => $video['description'] ?? '',
                'type' => $video['type'] ?? 'video',
                'is_video' => true,
                'display_url' => $video['thumbnail'] ?? null,
                'thumbnail_src' => $video['thumbnail'] ?? null,
                'video_url' => $video['url'] ?? null,
                'video_view_count' => $video['viewCountInt'] ?? 0,
                'likes_count' => $video['likeCountInt'] ?? 0,
                'comments_count' => $video['commentCountInt'] ?? 0,
                'view_count_text' => $video['viewCountText'] ?? null,
                'published_time_text' => $video['publishedTimeText'] ?? null,
                'published_time' => $video['publishedTime'] ?? null,
                'length_text' => $video['lengthText'] ?? null,
                'length_seconds' => $video['lengthSeconds'] ?? 0,
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
     * Get video stats from YouTube video URL (regular video or short)
     * 
     * @param string $postUrl YouTube video URL
     * @return array
     * @throws Exception
     */
    public function getVideoStats(string $postUrl): array
    {
        try {
            Log::info('🔍 YouTube Video Stats Request', [
                'url' => $postUrl,
            ]);

            // Call API to get video details
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                ])->get("{$this->baseUrl}/video", [
                        'url' => $postUrl,
                    ]);

            if (!$response->successful()) {
                Log::error('❌ YouTube Video API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('Failed to fetch YouTube video: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                throw new Exception('API returned unsuccessful response');
            }

            // YouTube video endpoint returns data at root level
            $videoData = $data;

            $result = [
                'username' => $videoData['channel']['title'] ?? $videoData['channelTitle'] ?? null,
                'followers_count' => $videoData['channel']['subscriberCount'] ?? 0,
                'views' => $videoData['viewCountInt'] ?? $videoData['view_count'] ?? 0,
                'likes' => $videoData['likeCountInt'] ?? $videoData['like_count'] ?? 0,
                'comments' => $videoData['commentCountInt'] ?? $videoData['comment_count'] ?? 0,
                'saves' => 0, // YouTube tidak menyediakan data saves secara publik
                'shares' => 0, // YouTube tidak menyediakan data shares secara publik
            ];

            // Calculate total engagement
            $result['total_engagement'] = $result['likes'] + $result['comments'] + $result['saves'] + $result['shares'];

            Log::info('✅ YouTube Video Stats Retrieved', $result);

            return $result;

        } catch (Exception $e) {
            Log::error('💥 YouTube Video Stats Error', [
                'url' => $postUrl,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
