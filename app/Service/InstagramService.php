<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class InstagramService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.scrapecreators.com/v1/instagram';

    public function __construct()
    {
        $apiKey = config('services.scrapecreators.api_key') ?? env('SCRAPECREATORS_API_KEY');

        if (empty($apiKey)) {
            throw new Exception('ScrapeCreators API key is not configured');
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Get Instagram profile data from username or profile URL
     * 
     * @param string $linkUserProfile Instagram username or profile URL
     * @return array
     * @throws Exception
     */
    public function getProfile(string $linkUserProfile): array
    {
        try {
            // Extract username from URL if needed
            $username = $this->extractUsername($linkUserProfile);

            // Make API request
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/profile", [
                        'handle' => $username,
                    ]);

            // Check if request was successful
            if (!$response->successful()) {
                Log::error('Instagram API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Failed to fetch Instagram profile: ' . $response->body());
            }

            $data = $response->json();

            // Check if API returned success
            if (!isset($data['success']) || !$data['success']) {
                throw new Exception('API returned unsuccessful response');
            }

            // Parse and return formatted data
            return $this->parseProfileData($data['data']['user'] ?? []);

        } catch (Exception $e) {
            Log::error('Instagram Service Error', [
                'message' => $e->getMessage(),
                'username' => $username ?? $linkUserProfile,
            ]);

            throw $e;
        }
    }

    /**
     * Extract username from Instagram URL or return as-is if already username
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
        // https://www.instagram.com/username/
        // https://instagram.com/username
        // www.instagram.com/username
        // instagram.com/username

        $pattern = '/(?:https?:\/\/)?(?:www\.)?instagram\.com\/([a-zA-Z0-9._]+)\/?/';

        if (preg_match($pattern, $linkUserProfile, $matches)) {
            return $matches[1];
        }

        // If no pattern matches, assume it's already a username
        return trim($linkUserProfile, '/@');
    }

    /**
     * Parse raw API data into structured format
     * 
     * @param array $userData
     * @return array
     */
    protected function parseProfileData(array $userData): array
    {
        $followersCount = $userData['edge_followed_by']['count'] ?? 0;
        $recentMedia = $userData['edge_owner_to_timeline_media']['edges'] ?? [];

        // Calculate engagement metrics
        $engagementMetrics = $this->calculateEngagementMetrics($recentMedia, $followersCount);

        return [
            // Basic Info
            'id' => $userData['id'] ?? null,
            'username' => $userData['username'] ?? null,
            'full_name' => $userData['full_name'] ?? null,
            'biography' => $userData['biography'] ?? null,
            'external_url' => $userData['external_url'] ?? null,

            // Profile Images
            'profile_pic_url' => $userData['profile_pic_url'] ?? null,
            'profile_pic_url_hd' => $userData['profile_pic_url_hd'] ?? null,

            // Stats
            'followers_count' => $followersCount,
            'following_count' => $userData['edge_follow']['count'] ?? 0,
            'media_count' => $userData['edge_owner_to_timeline_media']['count'] ?? 0,

            // Tier (calculated from followers)
            'tier' => $this->calculateTier($followersCount),

            // Engagement Metrics (calculated from recent posts)
            'engagement_rate' => $engagementMetrics['engagement_rate'],
            'total_engagements' => $engagementMetrics['total_engagements'],
            'average_likes' => $engagementMetrics['average_likes'],
            'average_comments' => $engagementMetrics['average_comments'],
            'average_impressions' => $engagementMetrics['average_impressions'],

            // Account Type
            'is_private' => $userData['is_private'] ?? false,
            'is_verified' => $userData['is_verified'] ?? false,
            'is_business_account' => $userData['is_business_account'] ?? false,
            'is_professional_account' => $userData['is_professional_account'] ?? false,

            // Business Info
            'business_category_name' => $userData['business_category_name'] ?? null,
            'category_name' => $userData['category_name'] ?? null,
            'business_email' => $userData['business_email'] ?? null,
            'business_phone_number' => $userData['business_phone_number'] ?? null,
            'business_address' => $this->parseBusinessAddress($userData['business_address_json'] ?? null),

            // Bio Links
            'bio_links' => $this->parseBioLinks($userData['bio_links'] ?? []),

            // Additional Info
            'eimu_id' => $userData['eimu_id'] ?? null,
            'fbid' => $userData['fbid'] ?? null,
            'has_clips' => $userData['has_clips'] ?? false,
            'has_guides' => $userData['has_guides'] ?? false,
            'has_channel' => $userData['has_channel'] ?? false,
            'highlight_reel_count' => $userData['highlight_reel_count'] ?? 0,

            // Recent Media (first post if available)
            'recent_media' => $this->parseRecentMedia($recentMedia),

            // Raw data (for debugging or additional processing)
            'raw_data' => $userData,
        ];
    }

    /**
     * Calculate engagement metrics from recent posts
     * Formula: Engagement Rate = Average(Likes + Comments per Post) / Followers × 100%
     * Also calculates average impressions from video views
     * 
     * @param array $mediaEdges
     * @param int $followersCount
     * @return array
     */
    protected function calculateEngagementMetrics(array $mediaEdges, int $followersCount): array
    {
        if (empty($mediaEdges) || $followersCount === 0) {
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
        $totalViews = 0;
        $postCount = 0;
        $videoCount = 0;

        // Calculate from recent posts (max 9 posts as requested)
        // Note: Public API only provides Likes and Comments. Saves/Shares are not available.
        foreach (array_slice($mediaEdges, 0, 9) as $edge) {
            $node = $edge['node'] ?? [];

            $likes = $node['edge_liked_by']['count'] ?? 0;
            $comments = $node['edge_media_to_comment']['count'] ?? 0;

            $totalLikes += $likes;
            $totalComments += $comments;
            $postCount++;

            // Track video views
            if (isset($node['video_view_count']) && $node['video_view_count'] > 0) {
                $totalViews += $node['video_view_count'];
                $videoCount++;
            }
        }

        if ($postCount === 0) {
            return [
                'engagement_rate' => 0,
                'total_engagements' => 0,
                'average_likes' => 0,
                'average_comments' => 0,
                'average_impressions' => 0,
            ];
        }

        // Calculate averages
        $averageLikes = $totalLikes / $postCount;
        $averageComments = $totalComments / $postCount;
        $averageEngagement = $averageLikes + $averageComments;

        // Engagement Rate Formula: (Average Engagement / Followers) * 100
        // Using Average Engagement ensures the rate is per-post standard
        $engagementRate = ($averageEngagement / $followersCount) * 100;

        // Average Impressions / Views
        // Formula: Average of total views from 9 posts
        // If no videos found, fallback to estimated impressions (2.5x engagement)
        $averageImpressions = 0;
        if ($videoCount > 0) {
            // Calculate average views across all posts (treating non-videos as 0 views or just averaging videos?)
            // Request says "average(total views 9 postingan)"
            // Usually this implies averaging only available views or treating all as potential viewable content
            // Here we average views based on video count to be accurate for "Views", 
            // or we can divide by $postCount if we assume photos have "0 views" (which lowers avg significantly).
            // Let's use average of videos found to be more representative of "View" metric.
            $averageImpressions = $totalViews / $videoCount;
        } else {
            // Fallback if no videos in last 9 posts
            $averageImpressions = $averageEngagement * 2.5;
        }

        return [
            'engagement_rate' => round($engagementRate, 2),
            'total_engagements' => round($averageEngagement), // User requested "Total engagement", usually implies Avg per post in this context
            'average_likes' => round($averageLikes),
            'average_comments' => round($averageComments),
            'average_impressions' => round($averageImpressions),
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
     * Parse business address JSON
     * 
     * @param string|array|null $addressData
     * @return array|null
     */
    protected function parseBusinessAddress(string|array|null $addressData): ?array
    {
        if (empty($addressData)) {
            return null;
        }

        try {
            // If it's a string, decode it
            if (is_string($addressData)) {
                $address = json_decode($addressData, true);
            } else {
                // It's already an array
                $address = $addressData;
            }

            return [
                'city_name' => $address['city_name'] ?? null,
                'city_id' => $address['city_id'] ?? null,
                'latitude' => $address['latitude'] ?? null,
                'longitude' => $address['longitude'] ?? null,
                'street_address' => $address['street_address'] ?? null,
                'zip_code' => $address['zip_code'] ?? null,
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Parse bio links
     * 
     * @param array $bioLinks
     * @return array
     */
    protected function parseBioLinks(array $bioLinks): array
    {
        return array_map(function ($link) {
            return [
                'title' => $link['title'] ?? null,
                'url' => $link['url'] ?? null,
                'link_type' => $link['link_type'] ?? null,
            ];
        }, $bioLinks);
    }

    /**
     * Parse recent media posts
     * 
     * @param array $mediaEdges
     * @return array
     */
    protected function parseRecentMedia(array $mediaEdges): array
    {
        return array_map(function ($edge) {
            $node = $edge['node'] ?? [];

            return [
                'id' => $node['id'] ?? null,
                'shortcode' => $node['shortcode'] ?? null,
                'type' => $node['__typename'] ?? null,
                'is_video' => $node['is_video'] ?? false,
                'display_url' => $node['display_url'] ?? null,
                'thumbnail_src' => $node['thumbnail_src'] ?? null,
                'video_url' => $node['video_url'] ?? null,
                'video_view_count' => $node['video_view_count'] ?? null,
                'caption' => $node['edge_media_to_caption']['edges'][0]['node']['text'] ?? '',
                'likes_count' => $node['edge_liked_by']['count'] ?? 0,
                'comments_count' => $node['edge_media_to_comment']['count'] ?? 0,
                'taken_at_timestamp' => $node['taken_at_timestamp'] ?? null,
                'taken_at' => isset($node['taken_at_timestamp'])
                    ? date('Y-m-d H:i:s', $node['taken_at_timestamp'])
                    : null,
            ];
        }, array_slice($mediaEdges, 0, 12)); // Limit to first 12 posts
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
