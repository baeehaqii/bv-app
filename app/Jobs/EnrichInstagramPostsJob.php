<?php

namespace App\Jobs;

use App\Models\DataKol;
use App\Service\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnrichInstagramPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180; // 3 minutes timeout for this job

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $dataKolId,
        public string $username
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🔄 Starting Instagram enrichment job', [
            'data_kol_id' => $this->dataKolId,
            'username' => $this->username,
        ]);

        try {
            $service = app(InstagramService::class);

            // Fetch posts with enrichment enabled (background process, no timeout issue)
            // enrichWithDetails = true to get accurate comments data
            $posts = $service->getUserPosts($this->username, 9, true);

            if (empty($posts)) {
                Log::warning('No posts to enrich', ['username' => $this->username]);
                return;
            }

            // Recalculate engagement metrics
            $dataKol = DataKol::find($this->dataKolId);
            if (!$dataKol) {
                Log::warning('DataKol not found', ['id' => $this->dataKolId]);
                return;
            }

            // Calculate totals from enriched posts
            $totalEngagements = 0;
            $totalLikes = 0;
            $totalComments = 0;
            $totalViews = 0;
            $videoCount = 0;
            $postCount = 0;

            foreach (array_slice($posts, 0, 9) as $post) {
                $likes = $post['like_count'] ?? 0;
                $comments = $post['comment_count'] ?? 0;
                $views = $post['play_count'] ?? $post['video_view_count'] ?? 0;

                $totalLikes += $likes;
                $totalComments += $comments;
                $totalEngagements += $likes + $comments;

                if ($views > 0) {
                    $totalViews += $views;
                    $videoCount++;
                }

                $postCount++;
            }

            $followersCount = max($dataKol->followers ?? 1, 1);

            // Calculate average impressions
            // If we have video views, use average
            // Otherwise estimate based on follower count
            $avgImpressions = $videoCount > 0
                ? round($totalViews / $videoCount)
                : round($followersCount * 0.15);

            // Update DataKol with enriched data
            $dataKol->update([
                'total_engagements' => $totalEngagements,
                'impressions' => $avgImpressions,
            ]);

            Log::info('✅ Instagram enrichment completed', [
                'data_kol_id' => $this->dataKolId,
                'username' => $this->username,
                'total_likes' => $totalLikes,
                'total_comments' => $totalComments,
                'total_engagements' => $totalEngagements,
                'total_views' => $totalViews,
                'video_count' => $videoCount,
                'avg_impressions' => $avgImpressions,
                'posts_processed' => $postCount,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Instagram enrichment failed', [
                'data_kol_id' => $this->dataKolId,
                'username' => $this->username,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }
}
