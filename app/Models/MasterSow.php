<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterSow extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel)->orWhereNull('channel');
    }

    public function rateCards(): HasMany
    {
        return $this->hasMany(KolRateCard::class);
    }

    /**
     * Label untuk select options: "IG Reels (Instagram)"
     */
    public function getOptionLabelAttribute(): string
    {
        return $this->channel
            ? "{$this->name} ({$this->channel})"
            : $this->name;
    }
}
