<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FormBrief extends Model
{
    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
        'content_deadline' => 'date',
        'posting_date' => 'date',
        'budget' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    // -------------------------------------------------------
    // Boot: auto-generate token saat create
    // -------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (FormBrief $brief) {
            if (empty($brief->token)) {
                $brief->token = Str::random(48);
            }
        });
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BvCampign::class, 'campaign_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(DataClient::class, 'client_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /** URL public untuk client mengisi brief */
    public function getPublicUrl(): string
    {
        return route('form-brief.public', $this->token);
    }

    /** Apakah brief sudah disubmit oleh client */
    public function isSubmitted(): bool
    {
        return $this->status !== 'draft';
    }

    /** Status label yang readable */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'reviewed' => 'Reviewed',
            'approved' => 'Approved',
            'revision' => 'Perlu Revisi',
            default => ucfirst($this->status),
        };
    }

    /** Status color untuk badge */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'submitted' => 'info',
            'reviewed' => 'warning',
            'approved' => 'success',
            'revision' => 'danger',
            default => 'gray',
        };
    }
}
