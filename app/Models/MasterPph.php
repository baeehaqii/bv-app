<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPph extends Model
{
    protected $fillable = [
        'name',
        'entity_type',
        'coefficient',
        'include_ppn',
        'ppn_percent',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'coefficient' => 'decimal:3',
        'ppn_percent' => 'decimal:2',
        'include_ppn' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get coefficient for selected PPH type
     * If include_ppn is true, calculate with PPN
     */
    public function getCalculatedCoefficient(): float
    {
        $coefficient = (float) $this->coefficient;

        if ($this->include_ppn && $this->ppn_percent) {
            // PT PKP = 0.98 + PPN 11% = 0.98 * 1.11 = 1.0878
            $ppnMultiplier = 1 + ($this->ppn_percent / 100);
            return $coefficient * $ppnMultiplier;
        }

        return $coefficient;
    }

    /**
     * Get active PPH options for dropdown
     */
    public static function getActiveOptions(): array
    {
        return self::where('is_active', true)
            ->orderBy('order')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Scope: Active only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }
}

