<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignStoryline extends Model
{
    protected $table = 'campaign_storylines';

    protected $guarded = [];

    protected $casts = [
        'posting_deadline' => 'date',
        'images' => 'array',
    ];

    /** Maksimal perbaikan konten setelah client minta revisi. */
    public const MAX_REVISIONS = 3;

    /** Maksimal gambar per storyline. */
    public const MAX_IMAGES = 10;

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

    /** Riwayat versi konten (urut versi awal → revisi terakhir). */
    public function contents(): HasMany
    {
        return $this->hasMany(CampaignStorylineContent::class, 'campaign_storyline_id')
            ->orderBy('revision_number');
    }

    public function latestContent(): ?CampaignStorylineContent
    {
        // reorder(): relasi contents() sudah ORDER BY asc — tanpa ini versi terlama yang terambil.
        return $this->contents()->reorder()->orderByDesc('revision_number')->first();
    }

    /** Sudah berapa kali diperbaiki (versi awal tidak dihitung). */
    public function revisionCount(): int
    {
        return $this->contents()->where('revision_number', '>', 0)->count();
    }

    public function remainingRevisions(): int
    {
        return max(0, self::MAX_REVISIONS - $this->revisionCount());
    }

    /**
     * Boleh dikirim ke client? Versi awal selalu boleh; perbaikan dibatasi
     * MAX_REVISIONS. Kiriman yang belum di-review client cukup diperbarui,
     * jadi tidak memakan jatah revisi.
     */
    public function canSubmitToClient(): bool
    {
        $latest = $this->latestContent();

        if ($latest && $latest->client_choice === null) {
            return true; // versi berjalan belum diputuskan client → update saja
        }

        return $latest === null || $this->revisionCount() < self::MAX_REVISIONS;
    }

    /**
     * Kirim konten versi terbaru ke client. Kalau versi terakhir sudah diminta
     * revisi oleh client, kiriman ini jadi versi perbaikan berikutnya.
     */
    public function submitToClient(): CampaignStorylineContent
    {
        if (! $this->canSubmitToClient()) {
            throw new \RuntimeException(
                'Batas ' . self::MAX_REVISIONS . 'x revisi sudah tercapai untuk storyline ini.'
            );
        }

        $latest = $this->latestContent();
        $snapshot = [
            'images' => $this->images ?? [],
            'content_link' => $this->content_link,
            'caption_draft' => $this->caption_draft,
            'notes' => $this->notes,
            'submitted_at' => now(),
        ];

        // Versi berjalan belum di-review → cukup perbarui isinya.
        if ($latest && $latest->client_choice === null) {
            $latest->update($snapshot);
            $content = $latest;
        } else {
            $content = $this->contents()->create([
                'revision_number' => $latest ? $latest->revision_number + 1 : 0,
                ...$snapshot,
            ]);
        }

        $this->update(['status' => 'waiting_approval']);

        return $content;
    }

    /**
     * Catat keputusan client pada versi konten terakhir + status storyline.
     */
    public function recordClientDecision(string $choice, ?string $feedback = null): void
    {
        $this->latestContent()?->update([
            'client_choice' => $choice,
            'client_feedback' => $feedback,
            'reviewed_at' => now(),
        ]);

        $this->update([
            'client_choice' => $choice,
            'client_feedback' => $feedback,
            'status' => $choice === 'approved' ? 'approved' : 'revision',
        ]);
    }
}
