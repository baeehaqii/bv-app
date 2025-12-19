<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterMargin extends Model
{
    protected $fillable = [
        'name',
        'min_amount',
        'max_amount',
        'margin_percent',
        'order',
        'is_active',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get margin percentage for a given subtotal amount
     */
    public static function getMarginForAmount(float $subtotal): float
    {
        $margin = self::where('is_active', true)
            ->where('min_amount', '<=', $subtotal)
            ->where(function ($query) use ($subtotal) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $subtotal);
            })
            ->orderBy('order')
            ->first();

        // Fallback to default if no margin found
        return $margin ? (float) $margin->margin_percent : 30.0;
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
        return $query->orderBy('order')->orderBy('min_amount');
    }
}

