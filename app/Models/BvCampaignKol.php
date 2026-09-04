<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BvCampaignKol extends Model
{
    protected $table = 'bv_campaign_kols';

    protected $guarded = [];

    protected $casts = [
        'price'            => 'double',
        'engagement_rate'  => 'decimal:4',
        'posted_at'        => 'datetime',
        'last_fetched_at'  => 'datetime',
        'visit_date'       => 'date',
        'posting_date'     => 'date',
        'event_attendance' => 'boolean',
        'comments_data'    => 'array',
        'comments_fetched_at' => 'datetime',
    ];

    public const PLATFORMS = [
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
        'youtube'   => 'YouTube',
        'threads'   => 'Threads',
    ];

    /**
     * Pilihan tier di brief KOL — sumbernya sama dengan Media Plan Internal,
     * KOL Data, dan scraping: master data "Tier KOL". Dulu daftar sendiri
     * dengan kunci huruf kecil, jadi tier yang sama tertulis dua rupa.
     */
    public static function tierOptions(): array
    {
        return MediaPlanCalcSetting::current()->tierOptions();
    }

    public const VISIT_STATUSES = [
        'scheduled' => 'Scheduled',
        'done'      => 'Done',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Status brief sebelum konten di-approve dan masuk KOL Performance.
     */
    public const BRIEF_STATUSES = [
        'draft'          => 'Draft',
        'waiting_review' => 'Waiting Review',
        'revision'       => 'Revision',
        'approved'       => 'Approved',
    ];

    /**
     * Content type options per platform
     */
    public const CONTENT_TYPES = [
        'instagram' => [
            'reels' => 'Reels',
            'feed' => 'Feed',
            'story' => 'Story',
        ],
        'tiktok' => [
            'video' => 'Video',
            'photos' => 'Photos',
        ],
        'youtube' => [
            'short' => 'Short',
            'video' => 'Video',
        ],
        'threads' => [
            'post' => 'Post',
        ],
    ];

    /**
     * Status options
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'posted' => 'Posted',
        'completed' => 'Completed',
        'canceled' => 'Canceled', // KOL batal (acuan: "KOL Cancel" di sheet Tracker)
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BvCampign::class, 'campaign_id');
    }

    /**
     * Riwayat revisi konten (storyline/video/caption) untuk KOL ini.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(CampaignKolRevision::class, 'bv_campaign_kol_id');
    }

    /**
     * Approve brief KOL — otomatis masuk KOL Performance.
     * Set brief_status = approved dan status = posted.
     */
    public function approveBrief(): void
    {
        $this->update([
            'brief_status' => 'approved',
            'status'       => 'posted',
            'posted_at'    => $this->posted_at ?? now(),
        ]);
    }

    /**
     * Get platform label
     */
    public function getPlatformLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? $this->platform;
    }

    /**
     * Get content type label
     */
    public function getContentTypeLabelAttribute(): string
    {
        return self::CONTENT_TYPES[$this->platform][$this->content_type] ?? $this->content_type;
    }

    /**
     * Get total engagement
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->likes + $this->comments + $this->shares + $this->saves;
    }

    /**
     * Calculate engagement rate from current metrics
     * 
     * ER by Views (Reels/Video): (Like + Comment) / Views × 100
     * ER by Followers (Photo): (Like + Comment) / Followers × 100
     * 
     * @param string|null $type Force calculation type ('views' or 'followers')
     * @return float
     */
    public function calculateEngagementRate(?string $type = null): float
    {
        $totalEngagement = $this->likes + $this->comments;

        // Determine which type to use
        $erType = $type ?? $this->er_type ?? 'views';

        if ($erType === 'views' && $this->views > 0) {
            return round(($totalEngagement / $this->views) * 100, 4);
        }

        if ($erType === 'followers' && $this->followers_count > 0) {
            return round(($totalEngagement / $this->followers_count) * 100, 4);
        }

        return 0;
    }

    /** Riwayat harian postingan ini — sumber tabel Retrieve History. */
    public function snapshots(): HasMany
    {
        return $this->hasMany(CampaignKolSnapshot::class, 'bv_campaign_kol_id')->orderBy('captured_on');
    }

    /**
     * Catat kondisi postingan hari ini. Dipanggil setiap kali performa di-fetch.
     * Satu baris per tanggal — fetch berulang dalam sehari memperbarui, bukan menumpuk.
     */
    public function recordSnapshot(): void
    {
        $angka = [
            'followers' => (int) $this->followers_count,
            'cost' => (float) $this->price,
            'views' => (int) $this->views,
            'likes' => (int) $this->likes,
            'comments' => (int) $this->comments,
            'shares' => (int) $this->shares,
            'saves' => (int) $this->saves,
            'engagement' => (int) $this->total_engagement,
        ];

        // whereDate(), bukan updateOrCreate(['captured_on' => ...]): cast `date`
        // menyimpan bentuk datetime sementara query builder tidak ikut meng-cast.
        $hariIni = $this->snapshots()->whereDate('captured_on', now()->toDateString())->first();

        $hariIni
            ? $hariIni->update($angka)
            : $this->snapshots()->create([...$angka, 'captured_on' => now()->toDateString()]);
    }

    /** Sudah tayang? Dipakai menghitung "Total Content yang sudah ter-posting". */
    public function isPublished(): bool
    {
        return filled($this->post_url);
    }

    /** @return array<int, string> teks komentar yang tersimpan. */
    public function commentTexts(): array
    {
        return array_values(array_filter((array) ($this->comments_data ?? []), 'is_string'));
    }

    public function cpe(): float
    {
        return $this->total_engagement > 0 ? round((float) $this->price / $this->total_engagement, 2) : 0.0;
    }

    public function cpv(): float
    {
        return $this->views > 0 ? round((float) $this->price / $this->views, 2) : 0.0;
    }

    public function cpm(): float
    {
        return $this->views > 0 ? round((float) $this->price / $this->views * 1000, 2) : 0.0;
    }
}
