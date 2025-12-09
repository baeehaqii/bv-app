<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MediaPlan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all KOLs for this media plan
     */
    public function kols(): HasMany
    {
        return $this->hasMany(MediaPlanKol::class)->orderBy('row_number');
    }

    /**
     * Get only selected KOLs (for shortlist/quotation)
     */
    public function selectedKols(): HasMany
    {
        return $this->hasMany(MediaPlanKol::class)
            ->where('is_selected', true)
            ->orderBy('row_number');
    }

    /**
     * Get the internal budget for this media plan (1:1)
     */
    public function internalBudget(): HasOne
    {
        return $this->hasOne(InternalBudget::class);
    }

    /**
     * Get total budget amount (from selected KOLs only)
     */
    public function getTotalBudgetAttribute(): float
    {
        return $this->internalBudget?->total_rounded ?? 0;
    }

    /**
     * Get total cost (from selected KOLs only)
     */
    public function getTotalCostAttribute(): float
    {
        return $this->internalBudget?->total_mu_pph ?? 0;
    }

    /**
     * Get average margin percentage
     */
    public function getAverageMarginAttribute(): float
    {
        return $this->internalBudget?->average_margin_percent ?? 0;
    }

    /**
     * Calculate live header summary for selected KOLs only
     * Returns: totalRate, totalImpression, totalEngagement
     */
    public function calculateHeaderSummary(): array
    {
        $selectedKols = $this->kols()->where('is_selected', true)->get();

        $totalRate = 0;
        $totalImpression = 0;
        $totalEngagement = 0;

        foreach ($selectedKols as $kol) {
            $totalRate += $kol->rate;
            $totalImpression += $kol->impression;
            $totalEngagement += $kol->engagement;
        }

        return [
            'total_rate' => $totalRate,
            'total_impression' => $totalImpression,
            'total_engagement' => $totalEngagement,
            'selected_count' => $selectedKols->count(),
        ];
    }

    /**
     * Get next row number for a new KOL
     */
    public function getNextRowNumber(): int
    {
        return ($this->kols()->max('row_number') ?? 0) + 1;
    }
}
