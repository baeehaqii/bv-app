<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Str;

class BvCampign extends Model
{
    protected $table = 'bv_campaigns';

    protected $guarded = [];

    protected $casts = [
        'media_platforms' => 'array',
        'client_brief_files' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'close_date' => 'date',
        'campaign_date' => 'date',
        'brief_received_date' => 'date',
        'total_cost' => 'decimal:2',
        'deal_value' => 'decimal:2',
        'campaign_month' => 'integer',
        'is_public' => 'boolean',
        'content_review_is_public' => 'boolean',
        'content_review_submitted_at' => 'datetime',
    ];

    /**
     * Tandai campaign sebagai internal (Campaign On Going Internal).
     */
    public const TYPE_INTERNAL = 'internal';

    public function isInternal(): bool
    {
        return $this->campaign_type === self::TYPE_INTERNAL;
    }

    public function generatePublicToken(): string
    {
        $token = Str::random(32);
        $this->update(['public_token' => $token, 'is_public' => true]);
        return $token;
    }

    public function revokePublicToken(): void
    {
        $this->update(['public_token' => null, 'is_public' => false]);
    }

    public function getPublicUrlAttribute(): ?string
    {
        if (!$this->public_token) {
            return null;
        }
        return route('campaign.public', $this->public_token);
    }

    /**
     * Link Approval Konten (internal) — token TERPISAH dari public_token external.
     */
    public function generateContentReviewToken(): string
    {
        if (!$this->content_review_token) {
            $this->content_review_token = Str::random(48);
        }
        $this->content_review_is_public = true;
        $this->saveQuietly();

        return $this->content_review_token;
    }

    public function revokeContentReviewToken(): void
    {
        $this->content_review_is_public = false;
        $this->saveQuietly();
    }

    public function getContentReviewUrlAttribute(): ?string
    {
        if (!$this->content_review_token) {
            return null;
        }
        return route('campaign-internal.content-review', ['token' => $this->content_review_token]);
    }

    /**
     * Get the linked Sales Activity
     */
    public function salesActivity(): BelongsTo
    {
        return $this->belongsTo(BvSales::class, 'bv_sales_id');
    }

    /**
     * Get the form brief for this campaign
     */
    public function formBrief(): BelongsTo
    {
        return $this->belongsTo(FormBrief::class, 'form_brief_id');
    }

    /**
     * Get the client (brand) for this campaign
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(DataClient::class, 'client_id');
    }

    /**
     * Get the Media Plan Internal linked via Sales Activity
     */
    public function mediaPlan(): HasOneThrough
    {
        return $this->hasOneThrough(
            MediaPlan::class,
            BvSales::class,
            'id',         // FK on bv_sales
            'bv_sales_id', // FK on media_plans
            'bv_sales_id', // local key on bv_campaigns
            'id'          // local key on bv_sales
        );
    }

    /**
     * Get all KOLs for this campaign
     */
    public function kols(): HasMany
    {
        return $this->hasMany(BvCampaignKol::class, 'campaign_id');
    }

    public function storylines(): HasMany
    {
        return $this->hasMany(CampaignStoryline::class, 'bv_campaign_id');
    }

    /**
     * Baris pembayaran KOL (Campaign Ongoing Internal — sheet OFERO).
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CampaignKolPayment::class, 'campaign_id');
    }

    /**
     * Buat/relink baris pembayaran dari daftar KOL aktif.
     * Pakai (campaign_id + kol_name) sebagai kunci agar data bayar (status/bukti transfer)
     * TIDAK hilang saat KOL di-wipe & dibuat ulang oleh sync InternalBudget.
     * Field snapshot hanya di-seed saat baris baru; baris lama hanya di-relink pointernya.
     */
    public function syncPaymentRowsFromKols(): void
    {
        // Biaya AKTUAL ke KOL diambil dari approved budget items (Real Cost = rate dasar,
        // Cost + Tax = mu_pph) — BUKAN harga client (price = sudah kena markup).
        $costByName = $this->resolveKolCostMap();

        foreach ($this->kols as $kol) {
            $name = $kol->creator_name ?: '—';

            $payment = CampaignKolPayment::firstOrNew([
                'campaign_id' => $this->id,
                'kol_name' => $name,
            ]);

            $payment->bv_campaign_kol_id = $kol->id;

            if (! $payment->exists) {
                $payment->username = $kol->username;
                $payment->platform = $kol->platform;
                $payment->real_cost = $costByName[$name]['real_cost'] ?? 0;
                $payment->cost_tax = $costByName[$name]['cost_tax'] ?? 0;
                $payment->payment_status = 'waiting_payment';
            }

            $payment->save();
        }
    }

    /**
     * Peta nama KOL → biaya aktual dari approved budget items (Media Plan External).
     * Diakumulasi bila satu KOL punya lebih dari satu SOW/item. Sekali query (hindari N+1).
     *
     * @return array<string, array{real_cost: float, cost_tax: float}>
     */
    protected function resolveKolCostMap(): array
    {
        $budget = $this->mediaPlan?->internalBudget;
        if (! $budget) {
            return [];
        }

        return $budget->items()
            ->where('status', 'approved')
            ->with('mediaPlanKol:id,name')
            ->get()
            ->reduce(function (array $map, $item): array {
                $name = $item->mediaPlanKol?->name;
                if (! $name) {
                    return $map;
                }

                $map[$name]['real_cost'] = ($map[$name]['real_cost'] ?? 0) + (float) ($item->rate_base ?? 0);
                $map[$name]['cost_tax'] = ($map[$name]['cost_tax'] ?? 0) + (float) ($item->mu_pph ?? 0);

                return $map;
            }, []);
    }

    /**
     * Riwayat revisi konten (storyline/video/caption) lintas KOL pada campaign ini.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(CampaignKolRevision::class, 'bv_campaign_id');
    }

    /**
     * Get KOLs by platform
     */
    public function kolsByPlatform(string $platform): HasMany
    {
        return $this->kols()->where('platform', $platform);
    }

    /**
     * Get total views across all KOLs
     */
    public function getTotalViewsAttribute(): int
    {
        return $this->kols->sum('views');
    }

    /**
     * Get total engagements across all KOLs
     */
    public function getTotalEngagementsAttribute(): int
    {
        return $this->kols->sum('likes') + $this->kols->sum('comments') + $this->kols->sum('shares');
    }

    /**
     * Get campaign progress (days elapsed / total days)
     */
    public function getProgressAttribute(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        $daysElapsed = $this->start_date->diffInDays(now());

        if ($totalDays <= 0) {
            return 100;
        }

        return min(100, round(($daysElapsed / $totalDays) * 100, 1));
    }

    /**
     * Check if campaign is ongoing
     */
    public function getIsOngoingAttribute(): bool
    {
        return $this->status === 'ongoing' ||
            ($this->start_date && $this->end_date &&
                now()->between($this->start_date, $this->end_date));
    }
}
