<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BvQuotation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quotation_date' => 'date',
        'expiry_date'    => 'date',
        'is_public'      => 'boolean',
    ];

    public function internalBudget(): BelongsTo
    {
        return $this->belongsTo(InternalBudget::class);
    }

    public function mediaPlan(): BelongsTo
    {
        return $this->belongsTo(MediaPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatePublicToken(): string
    {
        $token = Str::random(48);

        $this->update([
            'public_token' => $token,
            'is_public'    => true,
        ]);

        return $token;
    }

    public function revokePublicToken(): void
    {
        $this->update([
            'public_token' => null,
            'is_public'    => false,
        ]);
    }

    public function getPublicUrlAttribute(): ?string
    {
        if (!$this->public_token) {
            return null;
        }

        return route('quotation.public', ['token' => $this->public_token]);
    }

    /**
     * Ambil semua approved budget items untuk ditampilkan di public view.
     * Fallback ke semua items jika tidak ada yang approved.
     */
    public function getPublicItemsAttribute(): \Illuminate\Support\Collection
    {
        $budget = $this->internalBudget;
        if (!$budget) {
            return collect();
        }

        $approved = $budget->items()
            ->with('mediaPlanKol')
            ->where('status', 'approved')
            ->orderBy('sort_order')
            ->get();

        if ($approved->isNotEmpty()) {
            return $approved;
        }

        return $budget->items()
            ->with('mediaPlanKol')
            ->orderBy('sort_order')
            ->get();
    }
}
