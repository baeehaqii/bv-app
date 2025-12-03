<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalBudget extends Model
{
    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'gross_up_coeff' => 'decimal:2',
        'tax' => 'decimal:4',
        'mu_pph' => 'decimal:2',
        'mu_target' => 'decimal:2',
        'published_rate' => 'decimal:2',
        'rounded' => 'decimal:2',
        'margin_percent' => 'decimal:2',
    ];

    public function mediaPlan(): BelongsTo
    {
        return $this->belongsTo(MediaPlan::class);
    }

    /**
     * Calculate Real Cost (MU PPh)
     * Formula: rate / gross_up_coeff
     */
    public function calculateMuPph(): float
    {
        if (empty($this->rate)) {
            return 0;
        }

        return $this->rate / $this->gross_up_coeff;
    }

    /**
     * Calculate Subtotal
     * Formula: qty * rate
     */
    public function calculateSubtotal(): float
    {
        return ($this->qty ?? 1) * ($this->rate ?? 0);
    }

    /**
     * Calculate Rounded Price
     * Formula: ceil(published_rate / 100000) * 100000
     */
    public function calculateRounded(): float
    {
        if (empty($this->published_rate)) {
            return 0;
        }

        return ceil($this->published_rate / 100000) * 100000;
    }

    /**
     * Calculate Margin Percent
     * Formula: (rounded - mu_pph) / rounded * 100
     */
    public function calculateMargin(): float
    {
        if (empty($this->rounded) || $this->rounded == 0) {
            return 0;
        }

        $profit = $this->rounded - $this->mu_pph;
        return ($profit / $this->rounded) * 100;
    }

    /**
     * Calculate MU Target (guideline price)
     * Assuming 40% margin target
     */
    public function calculateMuTarget(): float
    {
        if (empty($this->mu_pph)) {
            return 0;
        }

        // For 40% margin: price = cost / (1 - margin)
        $marginTarget = 0.40;
        return $this->mu_pph / (1 - $marginTarget);
    }
}
