<?php

namespace App\Service;

use App\Service\KolPostNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class InstagramService
{
    use \App\Service\Concerns\CalculatesEngagementRate;

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

            Log::info('🔍 Instagram API Request', [
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

            Log::info('📡 Instagram Profile API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            // Check if request was successful
            if (!$response->successful()) {
                Log::error('❌ Instagram API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Failed to fetch Instagram profile: ' . $response->body());
            }

            $data = $response->json();

            // Check if API returned success
            if (!isset($data['success']) || !$data['success']) {
                Log::error('❌ API returned unsuccessful', [
                    'response' => $data,
                ]);
                throw new Exception('API returned unsuccessful response');
            }

            $userData = $data['data']['user'] ?? [];

            // Get posts for engagement calculation
            // NOTE: enrichWithDetails DISABLED - causes timeout (each post = 3-5s API call)
            // v2 API provides: likes, views (for videos) - sufficient for engagement calculation
            // For accurate comments data, use background job: EnrichInstagramPostsJob
            $postsData = $this->getUserPosts($username, KolPostNormalizer::LIMIT, false);

            Log::info('📊 Instagram Posts Fetched', [
                'username' => $username,
                'posts_count' => count($postsData),
                'enriched' => false,
            ]);

            // Parse and return formatted data
            $parsedData = $this->parseProfileData($userData, $postsData);

            Log::info('✅ Instagram Profile Parsed Successfully', [
                'username' => $parsedData['username'],
                'followers' => $parsedData['followers_count'],
                'posts' => $parsedData['media_count'],
                'engagement_rate' => $parsedData['engagement_rate'],
            ]);

            return $parsedData;

        } catch (Exception $e) {
            Log::error('💥 Instagram Service Error', [
                'message' => $e->getMessage(),
                'username' => $username ?? $linkUserProfile,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get user posts using v2 API for engagement metrics
     * 
     * @param string $username
     * @param int $count Number of posts to fetch (default 9)
     * @param bool $enrichWithDetails Whether to fetch detailed metrics for each post (costs more credits)
     * @return array
     */
    public function getUserPosts(string $username, int $count = KolPostNormalizer::LIMIT, bool $enrichWithDetails = false): array
    {
        try {
            Log::info('📡 Fetching Instagram Posts via v2 API', [
                'username' => $username,
                'count' => $count,
                'enrich_details' => $enrichWithDetails,
                'endpoint' => 'https://api.scrapecreators.com/v2/instagram/user/posts',
            ]);

            /*
             * trim=true memangkas bagian paling gemuk dari respons: info encoding
             * video, sprite sheet, metadata audio, dan belasan varian ukuran gambar.
             * Yang dipakai calculateEngagementMetrics() semuanya penghitung skalar
             * di level atas (like_count, comment_count, play_count, taken_at, dst.)
             * dan itu tetap ada. Tanpa trim, respons radenrauf ~1 MB dan kena timeout.
             *
             * `count` sengaja TIDAK dikirim — parameter itu tidak ada di dokumentasi
             * (yang ada cuma handle, next_max_id, trim), jadi diam-diam diabaikan.
             * Pembatasan jumlah post dilakukan di sini.
             */
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("https://api.scrapecreators.com/v2/instagram/user/posts", [
                        'handle' => $username,
                        'trim' => 'true',
                    ]);

            Log::info('📡 Instagram Posts API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'has_body' => !empty($response->body()),
            ]);

            if (!$response->successful()) {
                Log::warning('❌ Instagram Posts API Error', [
                    'status' => $response->status(),
                    'username' => $username,
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();

            Log::info('📊 Instagram Posts API Data Structure', [
                'has_success_key' => isset($data['success']),
                'success_value' => $data['success'] ?? null,
                'has_data_key' => isset($data['data']),
                'has_posts_key' => isset($data['data']['posts']),
                'posts_count' => isset($data['data']['posts']) ? count($data['data']['posts']) : 0,
                'data_keys' => array_keys($data),
            ]);

            if (!isset($data['success']) || !$data['success']) {
                Log::warning('⚠️ Instagram Posts API returned unsuccessful', [
                    'username' => $username,
                    'response' => $data,
                ]);
                return [];
            }

            $posts = array_slice($data['items'] ?? [], 0, $count);

            // Optionally enrich with detailed metrics per post
            if ($enrichWithDetails && !empty($posts)) {
                $posts = $this->enrichPostsWithDetails($posts);
            }

            Log::info('✅ Instagram Posts Retrieved Successfully', [
                'username' => $username,
                'posts_count' => count($posts),
                'enriched' => $enrichWithDetails,
            ]);

            return $posts;

        } catch (Exception $e) {
            Log::error('💥 Failed to fetch user posts', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Get detailed info for a single post/reel
     * Endpoint: /v1/instagram/post
     * Response structure: data.xdt_shortcode_media or data directly contains the media
     * 
     * @param string $shortcode Post shortcode (e.g., "DRh6_WXjRBc")
     * @return array|null Returns normalized post data
     */
    protected function getPostDetails(string $shortcode): ?array
    {
        try {
            $postUrl = "https://www.instagram.com/p/{$shortcode}/";

            $response = Http::timeout(10)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                ])->get("{$this->baseUrl}/post", [
                        'url' => $postUrl,
                    ]);

            if (!$response->successful()) {
                $status = $response->status();
                $message = match ($status) {
                    402 => 'Payment Required - API credits may be exhausted',
                    429 => 'Rate limited - too many requests',
                    404 => 'Post not found',
                    default => 'API error'
                };

                Log::warning("❌ Post details API error: {$message}", [
                    'shortcode' => $shortcode,
                    'status' => $status,
                ]);
                return null;
            }

            $responseData = $response->json();

            if (!isset($responseData['success']) || !$responseData['success']) {
                return null;
            }

            $data = $responseData['data'] ?? [];

            // ScrapeCreators may return data in different structures:
            // 1. data.xdt_shortcode_media (newer format)
            // 2. data directly contains the media (older format)
            $media = $data['xdt_shortcode_media'] ?? $data;

            // Log raw response structure for debugging
            Log::info('📋 Post API Response Structure', [
                'shortcode' => $shortcode,
                'has_xdt_shortcode_media' => isset($data['xdt_shortcode_media']),
                'media_keys' => array_keys($media),
            ]);

            // Extract and normalize the metrics
            // Comments: edge_media_to_comment.count or comment_count
            $commentCount = $media['edge_media_to_comment']['count']
                ?? $media['edge_media_to_parent_comment']['count']
                ?? $media['comment_count']
                ?? 0;

            // Likes: edge_media_preview_like.count or like_count
            $likeCount = $media['edge_media_preview_like']['count']
                ?? $media['like_count']
                ?? 0;

            // Views: video_view_count or play_count
            $viewCount = $media['video_view_count']
                ?? $media['video_play_count']
                ?? $media['play_count']
                ?? 0;

            // Log extracted values
            Log::info('📋 Extracted Post Metrics', [
                'shortcode' => $shortcode,
                'likes' => $likeCount,
                'comments' => $commentCount,
                'views' => $viewCount,
            ]);

            return [
                'like_count' => $likeCount,
                'comment_count' => $commentCount,
                'play_count' => $viewCount,
                'is_video' => $media['is_video'] ?? false,
                'media_type' => $media['media_type'] ?? 1,
            ];

        } catch (Exception $e) {
            Log::warning('Failed to fetch post details', [
                'shortcode' => $shortcode,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Enrich posts with detailed metrics from individual post API
     * Note: This costs additional API credits (1 per post)
     * Limited to first 5 posts to avoid timeout
     * 
     * @param array $posts
     * @param int $maxEnrich Maximum number of posts to enrich (default 3 to avoid timeout)
     * @return array
     */
    protected function enrichPostsWithDetails(array $posts, int $maxEnrich = 3): array
    {
        $enrichedPosts = [];
        $enrichedCount = 0;
        $errorCount = 0;
        $maxErrors = 2; // Stop enriching after 2 consecutive errors

        foreach ($posts as $post) {
            // Only enrich first N posts to save time and credits
            if ($enrichedCount < $maxEnrich && $errorCount < $maxErrors) {
                $shortcode = $post['code'] ?? ($post['shortcode'] ?? null);

                if ($shortcode) {
                    $details = $this->getPostDetails($shortcode);

                    if ($details) {
                        $errorCount = 0; // Reset error count on success

                        // Merge normalized metrics from getPostDetails
                        $post['comment_count'] = $details['comment_count'];
                        $post['like_count'] = $details['like_count'];
                        $post['play_count'] = $details['play_count'];

                        Log::info('✅ Post enriched successfully', [
                            'shortcode' => $shortcode,
                            'likes' => $post['like_count'],
                            'comments' => $post['comment_count'],
                            'views' => $post['play_count'],
                        ]);

                        $enrichedCount++;
                    } else {
                        $errorCount++;
                        Log::warning('⚠️ Skipping enrichment due to error', [
                            'shortcode' => $shortcode,
                            'error_count' => $errorCount,
                        ]);
                    }
                }
            }

            $enrichedPosts[] = $post;
        }

        Log::info('📊 Enrichment Summary', [
            'total_posts' => count($posts),
            'enriched_posts' => $enrichedCount,
            'skipped_due_to_errors' => $errorCount >= $maxErrors,
        ]);

        return $enrichedPosts;
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
     * @param array $userData User profile data from profile endpoint
     * @param array $postsData Posts data from v2 API for accurate engagement
     * @return array
     */
    protected function parseProfileData(array $userData, array $postsData = []): array
    {
        $followersCount = $userData['edge_followed_by']['count'] ?? 0;
        $recentMedia = $userData['edge_owner_to_timeline_media']['edges'] ?? [];

        // Calculate engagement metrics from v2 posts data if available, otherwise fallback to profile data
        if (!empty($postsData)) {
            $engagementMetrics = $this->calculateEngagementMetrics($postsData, $followersCount);
        } else {
            // Fallback to old method using profile data
            $engagementMetrics = $this->calculateEngagementMetricsFromProfile($recentMedia, $followersCount);
        }

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

            // Endpoint profil v2 tidak lagi mengirim edge_owner_to_timeline_media, jadi
            // postingan yang dipakai KOL Analyzer diambil dari hasil /user/posts (v2).
            'recent_media' => $postsData
                ? $this->parseRecentMediaV2($postsData)
                : $this->parseRecentMedia($recentMedia),

            // Raw data (for debugging or additional processing)
            'raw_data' => $userData,
        ];
    }

    /**
     * Calculate engagement metrics from recent posts using v2 API data
     * This method provides ACCURATE engagement including saves, shares, and reposts
     * 
     * Requirements from user:
     * - Avg views: Average of 9 posts (>24 hours old) = AVERAGE(total views 9 postingan)
     * - Total Engagement: Total engagement dari 9 postingan (likes + comments + saves + shares + reposts)
     * - ER%: (Average Engagement per Post / Followers) × 100
     * 
     * @param array $posts Array of post data from v2 API
     * @param int $followersCount Follower count for ER calculation
     * @return array
     */
    protected function calculateEngagementMetrics(array $posts, int $followersCount): array
    {
        if (empty($posts) || $followersCount === 0) {
            return [
                'engagement_rate' => 0,
                'total_engagements' => 0,
                'average_likes' => 0,
                'average_comments' => 0,
                'average_impressions' => 0,
            ];
        }

        // Use up to 9 most recent posts (no 24h filter)
        $validPosts = array_slice($posts, 0, 9);

        if (empty($validPosts)) {
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
        $totalEngagement = 0;
        $postCount = count($validPosts);
        $videoPostCount = 0;
        $photoPostCount = 0;
        $carouselPostCount = 0;
        $totalPhotoImpressions = 0;
        $perPostEngagement = [];

        Log::info('📈 Starting Instagram Engagement Calculation', [
            'posts_count' => $postCount,
            'followersCount' => $followersCount,
        ]);

        foreach ($validPosts as $index => $post) {
            // Basic metrics
            $likes = $post['like_count'] ?? 0;
            $comments = $post['comment_count'] ?? 0;

            // Determine media type
            // Instagram media_type: 1 = Photo, 2 = Video/Reel, 8 = Carousel
            $mediaType = $post['media_type'] ?? 1;
            $productType = $post['product_type'] ?? '';

            // Check if video/reel
            // Note: product_type='feed' is NOT a video indicator, it just means regular feed post
            // product_type='clips' = Reels, product_type='igtv' = IGTV
            $isVideo = $mediaType == 2
                || $productType === 'clips'  // Reels
                || $productType === 'igtv'   // IGTV
                || ($post['is_video'] ?? false) === true;

            // Check if photo
            $isPhoto = $mediaType == 1 && !$isVideo;

            // Check if carousel (can contain both photos and videos)
            $isCarousel = $mediaType == 8;

            // Views - check multiple possible field names
            // play_count is common for reels, video_view_count for videos
            $views = 0;
            if ($isVideo || $isCarousel) {
                $views = $post['play_count']
                    ?? $post['video_play_count']
                    ?? $post['video_view_count']
                    ?? $post['ig_play_count']
                    ?? $post['view_count']
                    ?? 0;
            }

            // Additional engagement metrics
            $saves = $post['save_count'] ?? 0;
            $shares = $post['share_count'] ?? 0;
            $reposts = $post['reshare_count'] ?? $post['repost_count'] ?? 0;

            // Determine media type string for logging
            $mediaTypeStr = match (true) {
                $isCarousel => 'Carousel',
                $isVideo => 'Video/Reel',
                $isPhoto => 'Photo',
                default => 'Unknown'
            };

            Log::info("📊 Post #{$index} Stats", [
                'post_id' => $post['id'] ?? 'unknown',
                'shortcode' => $post['code'] ?? ($post['shortcode'] ?? 'N/A'),
                'taken_at' => isset($post['taken_at']) ? date('Y-m-d H:i:s', $post['taken_at']) : 'N/A',
                'media_type' => $mediaType,
                'media_type_str' => $mediaTypeStr,
                'product_type' => $productType,
                'is_photo' => $isPhoto,
                'is_video' => $isVideo,
                'is_carousel' => $isCarousel,
                'likes' => $likes,
                'comments' => $comments,
                'shares' => $shares,
                'saves' => $saves,
                'reposts' => $reposts,
                'views' => $views,
            ]);

            $totalLikes += $likes;
            $totalComments += $comments;

            // Track post types and views
            if ($isVideo) {
                $videoPostCount++;
                if ($views > 0) {
                    $totalViews += $views;
                }
            } elseif ($isCarousel) {
                $carouselPostCount++;
                if ($views > 0) {
                    $totalViews += $views;
                    $videoPostCount++; // Count carousel with views as video for impression calc
                } else {
                    // Carousel without views - estimate impressions
                    $photoPostCount++;
                    // Estimated impressions for photo = likes * multiplier (industry standard ~3-5x)
                    $estimatedPhotoImpressions = ($likes + $comments) * 4;
                    $totalPhotoImpressions += $estimatedPhotoImpressions;
                }
            } else {
                // Photo post
                $photoPostCount++;
                // Estimated impressions for photo = (likes + comments) * multiplier (industry standard ~3-5x)
                $estimatedPhotoImpressions = ($likes + $comments) * 4;
                $totalPhotoImpressions += $estimatedPhotoImpressions;
            }

            // Total engagement = likes + comments + saves + shares + reposts
            $postEngagement = $likes + $comments + $saves + $shares + $reposts;
            $totalEngagement += $postEngagement;

            // Per-post: views dipakai sebagai basis ER bila konten video; foto pakai followers
            $perPostEngagement[] = [
                'engagement' => $postEngagement,
                'views' => ($isVideo || $isCarousel) ? $views : 0,
            ];
        }

        Log::info('📊 Post Type Summary', [
            'total_posts' => $postCount,
            'photo_posts' => $photoPostCount,
            'video_posts' => $videoPostCount,
            'carousel_posts' => $carouselPostCount,
            'total_views' => $totalViews,
            'total_photo_impressions' => $totalPhotoImpressions,
        ]);

        // Calculate averages
        $averageLikes = $postCount > 0 ? round($totalLikes / $postCount) : 0;
        $averageComments = $postCount > 0 ? round($totalComments / $postCount) : 0;

        // Average Impressions calculation:
        // Combine video views + estimated photo impressions
        $totalImpressions = $totalViews + $totalPhotoImpressions;

        if ($postCount > 0 && $totalImpressions > 0) {
            $averageImpressions = round($totalImpressions / $postCount);
        } else {
            // Fallback: estimate based on followers if no data available
            $averageImpressions = match (true) {
                $followersCount > 1000000 => round($followersCount * 0.10), // Mega: 10%
                $followersCount > 100000 => round($followersCount * 0.15),  // Macro: 15%
                $followersCount > 10000 => round($followersCount * 0.20),   // Micro: 20%
                default => round($followersCount * 0.25),                    // Nano: 25%
            };

            Log::info('📊 Using Fallback Impressions (no data)', [
                'method' => 'follower_reach_estimate',
                'follower_count' => $followersCount,
                'estimated_impressions' => $averageImpressions,
            ]);
        }

        Log::info('📊 Impressions Calculation', [
            'total_video_views' => $totalViews,
            'total_photo_impressions_estimated' => $totalPhotoImpressions,
            'total_combined_impressions' => $totalImpressions,
            'average_impressions' => $averageImpressions,
        ]);

        // Average Engagement per Post
        $averageEngagementPerPost = $postCount > 0 ? $totalEngagement / $postCount : 0;

        // ER% standar: basis views untuk video, basis followers untuk foto (per-post lalu dirata-rata)
        $engagementRate = $this->averageEngagementRate($perPostEngagement, $followersCount);

        Log::info('✅ Final Instagram Engagement Metrics', [
            'postCount' => $postCount,
            'videoPostCount' => $videoPostCount,
            'photoPostCount' => $photoPostCount,
            'totalEngagements' => $totalEngagement,
            'averageEngagementPerPost' => round($averageEngagementPerPost),
            'totalLikes' => $totalLikes,
            'totalComments' => $totalComments,
            'totalViews' => $totalViews,
            'engagementRate' => $engagementRate,
            'averageImpressions' => $averageImpressions,
        ]);

        return [
            'engagement_rate' => $engagementRate,
            'total_engagements' => $totalEngagement,
            'average_likes' => $averageLikes,
            'average_comments' => $averageComments,
            'average_impressions' => $averageImpressions,
        ];
    }    /**
         * Calculate engagement metrics from recent posts (FALLBACK method using profile data)
         * Used when v2 API posts are not available
         * Formula: Engagement Rate = Average(Likes + Comments per Post) / Followers × 100%
         * Also calculates average impressions from video views
         * 
         * @param array $mediaEdges
         * @param int $followersCount
         * @return array
         */
    protected function calculateEngagementMetricsFromProfile(array $mediaEdges, int $followersCount): array
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
        $perPostEngagement = [];

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
            $postViews = 0;
            if (isset($node['video_view_count']) && $node['video_view_count'] > 0) {
                $postViews = (int) $node['video_view_count'];
                $totalViews += $postViews;
                $videoCount++;
            }

            $perPostEngagement[] = [
                'engagement' => $likes + $comments,
                'views' => $postViews,
            ];
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

        // ER% standar: basis views untuk video, basis followers untuk foto
        $engagementRate = $this->averageEngagementRate($perPostEngagement, $followersCount);

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
    /**
     * Versi untuk item dari endpoint /v2/instagram/user/posts — bentuknya datar
     * (like_count, comment_count, code) bukan bersarang di node GraphQL.
     *
     * @param  array<int, array<string, mixed>>  $posts
     */
    protected function parseRecentMediaV2(array $posts): array
    {
        return array_map(function (array $post) {
            $mediaType = $post['media_type'] ?? 1;
            $productType = $post['product_type'] ?? '';
            $isVideo = $mediaType == 2 || in_array($productType, ['clips', 'igtv'], true);

            return [
                'id' => $post['id'] ?? ($post['pk'] ?? null),
                'shortcode' => $post['code'] ?? null,
                'type' => $productType ?: null,
                'is_video' => $isVideo,
                'display_url' => $post['image_versions2']['candidates'][0]['url'] ?? $post['thumbnail_url'] ?? null,
                'thumbnail_src' => $post['image_versions2']['candidates'][0]['url'] ?? $post['thumbnail_url'] ?? null,
                'video_url' => $post['video_url'] ?? null,
                'video_view_count' => $post['play_count'] ?? $post['ig_play_count'] ?? 0,
                'caption' => $post['caption']['text'] ?? '',
                'likes_count' => max(0, (int) ($post['like_count'] ?? 0)),
                'comments_count' => max(0, (int) ($post['comment_count'] ?? 0)),
                'taken_at_timestamp' => $post['taken_at'] ?? null,
                'taken_at' => isset($post['taken_at']) ? date('Y-m-d H:i:s', (int) $post['taken_at']) : null,
            ];
        }, array_slice(array_values($posts), 0, 12));
    }

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

    /**
     * Get post/reel stats from Instagram URL
     * Returns engagement metrics and content type for proper ER calculation
     * 
     * ER Formula (from user requirements):
     * - Reels/Video (has views): ER = (Like + Comment) / Views × 100
     * - Photo/Carousel (no views): ER = (Like + Comment) / Followers × 100
     * 
     * @param string $postUrl Instagram post or reel URL
     * @return array
     * @throws Exception
     */
    public function getPostStats(string $postUrl): array
    {
        try {
            Log::info('🔍 Instagram Post Stats Request', [
                'url' => $postUrl,
            ]);

            // Call API to get post details
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                ])->get("{$this->baseUrl}/post", [
                        'url' => $postUrl,
                    ]);

            if (!$response->successful()) {
                Log::error('❌ Instagram Post API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('Failed to fetch Instagram post: ' . $response->body());
            }

            $responseData = $response->json();

            if (!isset($responseData['success']) || !$responseData['success']) {
                throw new Exception('API returned unsuccessful response');
            }

            $data = $responseData['data'] ?? [];
            $media = $data['xdt_shortcode_media'] ?? $data;

            // Extract owner info
            $owner = $media['owner'] ?? [];
            $username = $owner['username'] ?? null;

            // Get followers count from owner (needed for ER_followers calculation)
            $followersCount = $owner['edge_followed_by']['count']
                ?? $owner['follower_count']
                ?? 0;

            // Determine content type
            // product_type: 'clips' = Reels, 'feed' = Feed post, 'igtv' = IGTV
            // is_video: true for video content
            $productType = $media['product_type'] ?? '';
            $isVideo = $media['is_video'] ?? false;

            // Determine if this is Reels/Video (has views) or Photo/Carousel (no views)
            $isReelsOrVideo = $productType === 'clips' || $productType === 'igtv' || $isVideo;
            $contentType = $isReelsOrVideo ? 'reels' : 'feed';

            // Extract metrics
            $likeCount = $media['edge_media_preview_like']['count']
                ?? $media['like_count']
                ?? 0;

            $commentCount = $media['edge_media_to_comment']['count']
                ?? $media['edge_media_to_parent_comment']['count']
                ?? $media['comment_count']
                ?? 0;

            // Views - only available for Reels/Video
            // Use play_count or video_view_count
            $viewCount = 0;
            if ($isReelsOrVideo) {
                $viewCount = $media['video_play_count']
                    ?? $media['video_view_count']
                    ?? $media['play_count']
                    ?? $media['ig_play_count']
                    ?? 0;
            }

            // Instagram doesn't always provide saves/shares publicly
            $saveCount = $media['save_count'] ?? 0;
            $shareCount = $media['share_count'] ?? 0;

            // Handle -1 values (API returns -1 when data is not publicly available)
            $likeCount = max(0, $likeCount);
            $commentCount = max(0, $commentCount);
            $viewCount = max(0, $viewCount);
            $saveCount = max(0, $saveCount);
            $shareCount = max(0, $shareCount);

            // Calculate total engagement (likes + comments only for ER)
            $totalEngagement = $likeCount + $commentCount;

            // Determine ER type and calculate
            // - Reels/Video: Use views as denominator
            // - Photo/Carousel: Use followers as denominator
            $erType = ($isReelsOrVideo && $viewCount > 0) ? 'views' : 'followers';

            $engagementRate = 0;
            if ($erType === 'views' && $viewCount > 0) {
                // ER by Views = (Like + Comment) / Views × 100
                $engagementRate = round(($totalEngagement / $viewCount) * 100, 4);
            } elseif ($erType === 'followers' && $followersCount > 0) {
                // ER by Followers = (Like + Comment) / Followers × 100
                $engagementRate = round(($totalEngagement / $followersCount) * 100, 4);
            }

            $result = [
                'username' => $username,
                'views' => $viewCount,
                'likes' => $likeCount,
                'comments' => $commentCount,
                'saves' => $saveCount,
                'shares' => $shareCount,
                'total_engagement' => $totalEngagement,
                'followers_count' => $followersCount,
                'content_type' => $contentType,
                'is_video' => $isReelsOrVideo,
                'er_type' => $erType,
                'engagement_rate' => $engagementRate,
            ];

            Log::info('✅ Instagram Post Stats Retrieved', [
                'username' => $username,
                'content_type' => $contentType,
                'er_type' => $erType,
                'views' => $viewCount,
                'likes' => $likeCount,
                'comments' => $commentCount,
                'followers_count' => $followersCount,
                'engagement_rate' => $engagementRate,
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('💥 Instagram Post Stats Error', [
                'url' => $postUrl,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
