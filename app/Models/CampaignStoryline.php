<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignStoryline extends Model
{
    protected $table = 'campaign_storylines';

    protected $guarded = [];

    protected $casts = [
        'posting_deadline' => 'date',
    ];

    public const PLATFORMS = [
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
        'youtube'   => 'YouTube',
        'threads'   => 'Threads',
        'x'         => 'X (Twitter)',
    ];

    public const STATUSES = [
        'draft'            => 'Draft',
        'waiting_approval' => 'Waiting Approval',
        'revision'         => 'Revision',
        'approved'         => 'Approved',
        'posted'           => 'Posted',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BvCampign::class, 'bv_campaign_id');
    }
}
