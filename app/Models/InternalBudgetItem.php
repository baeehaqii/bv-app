<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalBudgetItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'qty' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // Note: Removed decimal casts - mutators handle number parsing
    ];

    /**
     * Parse formatted number string to float
     * Converts "2.000.000" or "2.000.000,50" to 2000000.50
     */
    private static function parseFormattedNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove dots (thousand separator) and replace comma with dot (decimal)
            $cleaned = str_replace('.', '', $value);
            $cleaned = str_replace(',', '.', $cleaned);
            return (float) $cleaned;
        }

        return 0;
    }

    /**
     * Mutator for rate_base
     */
    public function setRateBaseAttribute($value): void
    {
        $this->attributes['rate_base'] = self::parseFormattedNumber($value);
    }

    /**
     * Mutator for published_rate
     */
    public function setPublishedRateAttribute($value): void
    {
        $this->attributes['published_rate'] = $value ? self::parseFormattedNumber($value) : null;
    }

    /**
     * Mutator for mu_pph
     */
    public function setMuPphAttribute($value): void
    {
        $this->attributes['mu_pph'] = self::parseFormattedNumber($value);
    }

    /**
     * Mutator for rounded
     */
    public function setRoundedAttribute($value): void
    {
        $this->attributes['rounded'] = self::parseFormattedNumber($value);
    }

    /**
     * Mutator for subtotal
     */
    public function setSubtotalAttribute($value): void
    {
        $this->attributes['subtotal'] = self::parseFormattedNumber($value);
    }

    /**
     * Mutator for mu_target
     */
    public function setMuTargetAttribute($value): void
    {
        $this->attributes['mu_target'] = self::parseFormattedNumber($value);
    }

    /**
     * Mutator for actual_margin_percent
     */
    public function setActualMarginPercentAttribute($value): void
    {
        $this->attributes['actual_margin_percent'] = self::parseFormattedNumber($value);
    }

    /**
     * Mutator for target_margin_percent
     */
    public function setTargetMarginPercentAttribute($value): void
    {
        $this->attributes['target_margin_percent'] = self::parseFormattedNumber($value);
    }

    /**
     * Vendor Tax Type options
     */
    const VENDOR_TAX_TYPES = [
        'Pribadi' => 'Pribadi (PPh 2.5%/21)',
        'PT Non PKP' => 'PT Non PKP (PPh 23 2%)',
        'PT PKP' => 'PT PKP (PPh 23 + PPN 11%)',
        'CV' => 'CV (PPh Final 0.5%)',
    ];

    /**
     * Gross Up Coefficients per Tax Type
     */
    const GROSS_UP_COEFFICIENTS = [
        'Pribadi' => 0.975,     // PPh 2.5% / 21
        'PT Non PKP' => 0.98,   // PPh 23 2%
        'PT PKP' => 0.98,       // PPh 23 2% (PPN calculated separately)
        'CV' => 0.995,          // PPh Final 0.5%
    ];

    /**
     * Get the internal budget this item belongs to
     */
    public function internalBudget(): BelongsTo
    {
        return $this->belongsTo(InternalBudget::class);
    }

    /**
     * Get the KOL this item belongs to (if applicable)
     */
    public function mediaPlanKol(): BelongsTo
    {
        return $this->belongsTo(MediaPlanKol::class);
    }

    /**
     * Calculate Subtotal
     * Formula: qty * rate_base
     */
    public function calculateSubtotal(): float
    {
        return ($this->qty ?? 1) * ($this->rate_base ?? 0);
    }

    /**
     * Get Gross Up Coefficient based on Vendor Tax Type
     * Formula H
     */
    public function getGrossUpCoeff(): float
    {
        return self::GROSS_UP_COEFFICIENTS[$this->vendor_tax_type] ?? 0.975;
    }

    /**
     * Calculate MU PPh* (Real Cost) - Total uang keluar dari perusahaan
     * Formula I - Dynamic Tax Calculation
     * 
     * Pribadi: baseRate / 0.975
     * PT Non PKP: baseRate / 0.98
     * PT PKP: (baseRate / 0.98) + (baseRate * 11%)
     * CV: baseRate / 0.995
     */
    public function calculateMuPph(): float
    {
        $baseRate = $this->subtotal ?? 0;

        if ($baseRate <= 0) {
            return 0;
        }

        switch ($this->vendor_tax_type) {
            case 'Pribadi':
                return $baseRate / 0.975;

            case 'PT Non PKP':
                return $baseRate / 0.98;

            case 'PT PKP':
                // Logic: (Base / 0.98) + (Base * 11%)
                $grossUpValue = $baseRate / 0.98;
                $ppnValue = $baseRate * 0.11;
                return $grossUpValue + $ppnValue;

            case 'CV':
                return $baseRate / 0.995;

            default:
                return $baseRate / 0.975; // Default to Pribadi
        }
    }

    /**
     * Get Tax Value display text
     */
    public function getTaxValueDisplay(): string
    {
        return match ($this->vendor_tax_type) {
            'Pribadi' => 'PPh 2.5%',
            'PT Non PKP' => 'PPh 23 2%',
            'PT PKP' => 'PPh 23 2% + PPN 11%',
            'CV' => 'PPh Final 0.5%',
            default => 'PPh 2.5%',
        };
    }

    /**
     * Calculate Auto Target Margin based on subtotal
     * Formula J
     * 
     * Range 1: 100,000 - 2,999,999 -> 80%
     * Range 2: 3,000,000 - 50,000,000 -> 40%
     * Range 3: > 50,000,000 -> 30%
     */
    public function calculateAutoTargetMargin(): float
    {
        $subtotal = $this->subtotal ?? 0;

        if ($subtotal > 50000000) {
            return 30.0;
        } elseif ($subtotal >= 3000000) {
            return 40.0;
        } else {
            return 80.0;
        }
    }

    /**
     * Calculate MU** Target (guideline price based on auto margin)
     * Formula: mu_pph / (1 - target_margin_percent/100)
     */
    public function calculateMuTarget(): float
    {
        if (empty($this->mu_pph)) {
            return 0;
        }

        $marginDecimal = $this->target_margin_percent / 100;

        if ($marginDecimal >= 1) {
            return $this->mu_pph; // Prevent division by zero
        }

        return $this->mu_pph / (1 - $marginDecimal);
    }

    /**
     * Calculate Rounded Price
     * Formula K: Rounds up to nearest 100,000
     */
    public function calculateRounded(): float
    {
        $price = $this->published_rate ?? $this->mu_target ?? 0;

        if ($price <= 0) {
            return 0;
        }

        return ceil($price / 100000) * 100000;
    }

    /**
     * Calculate Actual Margin Percent
     * Formula: (rounded - mu_pph) / rounded * 100
     */
    public function calculateActualMargin(): float
    {
        if (empty($this->rounded) || $this->rounded == 0) {
            return 0;
        }

        $profit = $this->rounded - $this->mu_pph;
        return ($profit / $this->rounded) * 100;
    }

    /**
     * Auto-calculate all fields
     */
    public function recalculate(): void
    {
        // Step 1: Calculate subtotal
        $this->subtotal = $this->calculateSubtotal();

        // Step 2: Get gross up coefficient
        $this->gross_up_coeff = $this->getGrossUpCoeff();
        $this->tax_value = $this->getTaxValueDisplay();

        // Step 3: Calculate MU PPh (Real Cost)
        $this->mu_pph = $this->calculateMuPph();

        // Step 4: Calculate auto target margin
        $this->target_margin_percent = $this->calculateAutoTargetMargin();

        // Step 5: Calculate MU Target
        $this->mu_target = $this->calculateMuTarget();

        // Step 6: Set published rate default if not provided
        if (empty($this->published_rate) && $this->mu_target > 0) {
            $this->published_rate = $this->mu_target;
        }

        // Step 7: Calculate rounded
        $this->rounded = $this->calculateRounded();

        // Step 8: Calculate actual margin
        $this->actual_margin_percent = $this->calculateActualMargin();
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate before saving
        static::saving(function ($item) {
            $item->recalculate();
        });

        // Recalculate parent budget totals and sync rate to KOL after save/delete
        static::saved(function ($item) {
            $item->internalBudget->recalculateTotals();

            // Sync rate back to MediaPlanKol
            if ($item->mediaPlanKol) {
                $item->mediaPlanKol->syncRateFromBudget();
            }
        });

        static::deleted(function ($item) {
            $item->internalBudget->recalculateTotals();

            // Sync rate back to MediaPlanKol
            if ($item->mediaPlanKol) {
                $item->mediaPlanKol->syncRateFromBudget();
            }
        });
    }
}
