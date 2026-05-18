<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BvCampaignKol extends Model
{
    protected $table = 'bv_campaign_kols';

    protected $guarded = [];

    protected $casts = [
        'price'           => 'double',
        'engagement_rate' => 'decimal:4',
        'posted_at'       => 'datetime',
        'last_fetched_at' => 'datetime',
        'visit_date'      => 'date',
        'posting_date'    => 'date',
    ];

    public const PLATFORMS = [
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
        'youtube'   => 'YouTube',
        'threads'   => 'Threads',
    ];

    public const TIERS = [
        'nano'  => 'Nano (< 10K)',
        'micro' => 'Micro (10K–100K)',
        'macro' => 'Macro (100K–1M)',
        'mega'  => 'Mega (> 1M)',
    ];

    public const VISIT_STATUSES = [
        'scheduled' => 'Scheduled',
        'done'      => 'Done',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Status brief sebelum konten di-approve dan masuk KOL Performance.
     */
    public const BRIEF_STATUSES = [
        'draft'          => 'Draft',
        'waiting_review' => 'Waiting Review',
        'revision'       => 'Revision',
        'approved'       => 'Approved',
    ];

    /**
     * Content type options per platform
     */
    public const CONTENT_TYPES = [
        'instagram' => [
            'reels' => 'Reels',
            'feed' => 'Feed',
            'story' => 'Story',
        ],
        'tiktok' => [
            'video' => 'Video',
            'photos' => 'Photos',
        ],
        'youtube' => [
            'short' => 'Short',
            'video' => 'Video',
        ],
        'threads' => [
            'post' => 'Post',
        ],
    ];

    /**
     * Status options
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'posted' => 'Posted',
        'completed' => 'Completed',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BvCampign::class, 'campaign_id');
    }

    /**
     * Approve brief KOL — otomatis masuk KOL Performance.
     * Set brief_status = approved dan status = posted.
     */
    public function approveBrief(): void
    {
        $this->update([
            'brief_status' => 'approved',
            'status'       => 'posted',
            'posted_at'    => $this->posted_at ?? now(),
        ]);
    }

    /**
     * Scope: hanya KOL yang sudah approved (untuk KOL Performance).
     */
    public function scopeApproved($query)
    {
        return $query->where('brief_status', 'approved');
    }

    /**
     * Scope: KOL yang masih dalam proses brief (belum approved).
     */
    public function scopeInBrief($query)
    {
        return $query->where('brief_status', '!=', 'approved');
    }

    /**
     * Get platform label
     */
    public function getPlatformLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? $this->platform;
    }

    /**
     * Get content type label
     */
    public function getContentTypeLabelAttribute(): string
    {
        return self::CONTENT_TYPES[$this->platform][$this->content_type] ?? $this->content_type;
    }

    /**
     * Get total engagement
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->likes + $this->comments + $this->shares + $this->saves;
    }

    /**
     * Calculate engagement rate from current metrics
     * 
     * ER by Views (Reels/Video): (Like + Comment) / Views × 100
     * ER by Followers (Photo): (Like + Comment) / Followers × 100
     * 
     * @param string|null $type Force calculation type ('views' or 'followers')
     * @return float
     */
    public function calculateEngagementRate(?string $type = null): float
    {
        $totalEngagement = $this->likes + $this->comments;

        // Determine which type to use
        $erType = $type ?? $this->er_type ?? 'views';

        if ($erType === 'views' && $this->views > 0) {
            return round(($totalEngagement / $this->views) * 100, 4);
        }

        if ($erType === 'followers' && $this->followers_count > 0) {
            return round(($totalEngagement / $this->followers_count) * 100, 4);
        }

        return 0;
    }

    /**
     * Get formatted engagement rate with type indicator
     */
    public function getFormattedEngagementRateAttribute(): string
    {
        if ($this->engagement_rate <= 0) {
            return 'N/A';
        }

        $suffix = $this->er_type === 'followers' ? ' (F)' : '';
        return number_format((float) $this->engagement_rate, 2) . '%' . $suffix;
    }
}

