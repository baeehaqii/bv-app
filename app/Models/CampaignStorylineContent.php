<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu versi konten storyline (kiriman tim KOL internal ke client).
 * revision_number 0 = versi awal, 1..3 = hasil perbaikan.
 */
class CampaignStorylineContent extends Model
{
    protected $table = 'campaign_storyline_contents';

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function storyline(): BelongsTo
    {
        return $this->belongsTo(CampaignStoryline::class, 'campaign_storyline_id');
    }

    public function getLabelAttribute(): string
    {
        return $this->revision_number === 0
            ? 'Versi Awal'
            : "Revisi ke-{$this->revision_number}";
    }
}
