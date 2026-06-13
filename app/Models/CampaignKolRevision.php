<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu ronde revisi konten pada satu tahap (storyline | video | caption) untuk satu KOL
 * dalam Campaign Ongoing Internal. Menggantikan kolom fixed feedback/revision_link/feedback_2.
 */
class CampaignKolRevision extends Model
{
    protected $table = 'campaign_kol_revisions';

    protected $guarded = [];

    protected $casts = [
        'round'        => 'integer',
        'is_final'     => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public const STAGES = [
        'storyline' => 'Storyline',
        'video'     => 'Video',
        'caption'   => 'Caption',
    ];

    public const STATUSES = [
        'waiting_review' => 'Waiting Review',
        'approved'       => 'Approved',
        'revision'       => 'Revision',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BvCampign::class, 'bv_campaign_id');
    }

    public function kol(): BelongsTo
    {
        return $this->belongsTo(BvCampaignKol::class, 'bv_campaign_kol_id');
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? ucfirst((string) $this->stage);
    }
}
