<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BvCampaignKol extends Model
{
    protected $table = 'bv_campaign_kols';

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'engagement_rate' => 'decimal:4',
        'posted_at' => 'datetime',
        'last_fetched_at' => 'datetime',
    ];

    /**
     * Platform options
     */
    public const PLATFORMS = [
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
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
    ];

    /**
     * Status options
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'posted' => 'Posted',
        'completed' => 'Completed',
    ];

    /**
     * Get the campaign for this KOL
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BvCampign::class, 'campaign_id');
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
     */
    public function calculateEngagementRate(): float
    {
        if ($this->views <= 0) {
            return 0;
        }

        return round(($this->total_engagement / $this->views) * 100, 4);
    }
}
