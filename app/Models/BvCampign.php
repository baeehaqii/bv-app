<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BvCampign extends Model
{
    protected $table = 'bv_campaigns';

    protected $guarded = [];

    protected $casts = [
        'media_platforms' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the client (brand) for this campaign
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(DataClient::class, 'client_id');
    }

    /**
     * Get all KOLs for this campaign
     */
    public function kols(): HasMany
    {
        return $this->hasMany(BvCampaignKol::class, 'campaign_id');
    }

    /**
     * Get KOLs by platform
     */
    public function kolsByPlatform(string $platform): HasMany
    {
        return $this->kols()->where('platform', $platform);
    }

    /**
     * Get total views across all KOLs
     */
    public function getTotalViewsAttribute(): int
    {
        return $this->kols->sum('views');
    }

    /**
     * Get total engagements across all KOLs
     */
    public function getTotalEngagementsAttribute(): int
    {
        return $this->kols->sum('likes') + $this->kols->sum('comments') + $this->kols->sum('shares');
    }

    /**
     * Get campaign progress (days elapsed / total days)
     */
    public function getProgressAttribute(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        $daysElapsed = $this->start_date->diffInDays(now());

        if ($totalDays <= 0) {
            return 100;
        }

        return min(100, round(($daysElapsed / $totalDays) * 100, 1));
    }

    /**
     * Check if campaign is ongoing
     */
    public function getIsOngoingAttribute(): bool
    {
        return $this->status === 'ongoing' ||
            ($this->start_date && $this->end_date &&
                now()->between($this->start_date, $this->end_date));
    }
}
