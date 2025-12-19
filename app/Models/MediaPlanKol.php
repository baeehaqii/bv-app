<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaPlanKol extends Model
{
    protected $table = 'media_plan_kols';

    protected $guarded = [];

    protected $casts = [
        'is_selected' => 'boolean',
        'row_number' => 'integer',
        'links' => 'array', // JSON array for multiple links
        'scope_items' => 'array', // JSON array for scope of work items
        'followers' => 'integer',
        'impression' => 'integer',
        'engagement' => 'integer',
        'er_percent' => 'decimal:4',
        'cpi_cpv' => 'float',
        'cpe' => 'float',
        'rate' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status options as per instruction
     */
    const STATUS_OPTIONS = [
        'New List' => 'New List',
        'Approaching' => 'Approaching',
        'Locked' => 'Locked',
        'Canceled' => 'Canceled',
    ];

    /**
     * Get the media plan this KOL belongs to
     */
    public function mediaPlan(): BelongsTo
    {
        return $this->belongsTo(MediaPlan::class);
    }

    /**
     * Get the original KOL data if selected from database
     */
    public function dataKol(): BelongsTo
    {
        return $this->belongsTo(DataKol::class);
    }

    /**
     * Get all internal budget items for this KOL
     */
    public function internalBudgetItems(): HasMany
    {
        return $this->hasMany(InternalBudgetItem::class);
    }

    /**
     * Calculate Tier based on followers
     */
    public static function calculateTier(int $followers): string
    {
        if ($followers >= 1000000) {
            return 'Mega';
        } elseif ($followers >= 100000) {
            return 'Macro';
        } elseif ($followers >= 10000) {
            return 'Micro';
        } else {
            return 'Nano';
        }
    }

    /**
     * Calculate engagement from followers and ER
     */
    public function calculateEngagement(): int
    {
        return intval($this->followers * ($this->er_percent / 100));
    }

    /**
     * Get total rate from all internal budget items (Rounded value)
     */
    public function getTotalRoundedAttribute(): float
    {
        return $this->internalBudgetItems()->sum('rounded');
    }

    /**
     * Calculate CPI/CPV (Cost Per Impression/View)
     * Formula: Rate (Rounded) / Impression
     */
    public function calculateCpiCpv(): float
    {
        if ($this->impression == 0) {
            return 0;
        }

        return $this->total_rounded / $this->impression;
    }

    /**
     * Calculate CPE (Cost Per Engagement)
     * Formula: Rate (Rounded) / Engagement
     */
    public function calculateCpe(): float
    {
        if ($this->engagement == 0) {
            return 0;
        }

        return $this->total_rounded / $this->engagement;
    }

    /**
     * Update rate from internal budget items
     */
    public function syncRateFromBudget(): void
    {
        $this->rate = $this->total_rounded;
        $this->cpi_cpv = $this->calculateCpiCpv();
        $this->cpe = $this->calculateCpe();
        $this->saveQuietly();
    }

    /**
     * Add a link to the links array
     */
    public function addLink(string $link): void
    {
        $links = $this->links ?? [];
        if (!in_array($link, $links)) {
            $links[] = $link;
            $this->links = $links;
            $this->save();
        }
    }

    /**
     * Remove a link from the links array
     */
    public function removeLink(string $link): void
    {
        $links = $this->links ?? [];
        $this->links = array_values(array_filter($links, fn($l) => $l !== $link));
        $this->save();
    }
}
