<?php

namespace App\Models;

use App\Enums\SalesStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BvSales extends Model
{
    protected $table = 'bv_sales';

    protected $fillable = [
        'bv_sales_list_id',
        'event_name',
        'company_name',
        'related_client_name',
        'campaign_items',
        'budget_propose',
        'deal_value',
        'margin',
        'campaign_year',
        'close_date',
        'comments',
        'detail',
        'brief_files',
        'brief_link',
        'brief_submit_date',
        'status',
        'position',
        'form_brief_id',
        'campaign_month',
        'campaign_date',
        'start_date',
        'end_date',
        'pic_media_plan',
        'meeting_notes',
        'quotation_sign',
    ];

    protected $casts = [
        'campaign_items' => 'array',
        'budget_propose' => 'decimal:2',
        'deal_value' => 'decimal:2',
        'margin' => 'decimal:2',
        'brief_files' => 'array',
        'brief_submit_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'campaign_year' => 'integer',
        'close_date' => 'date',
        'status' => SalesStatus::class,
        'campaign_month' => 'integer',
        'campaign_date' => 'date',
        'quotation_sign' => 'array',
    ];

    // -------------------------------------------------------
    // Boot: trigger otomatis berdasarkan perubahan status
    // Flow:
    //   BRIEFING      → buat FormBrief + MediaPlan Internal
    //   CAMPAIGN_LIVE → buat Campaign Ongoing Internal (BvCampign)
    // -------------------------------------------------------

    protected static function booted(): void
    {
        static::updated(function (BvSales $sales) {
            if ($sales->wasChanged('status')) {
                if ($sales->status === SalesStatus::BRIEFING) {
                    $sales->ensureFormBriefExists();
                    $sales->ensureMediaPlanExists();
                }

                if ($sales->status === SalesStatus::CAMPAIGN_LIVE) {
                    $sales->ensureCampaignOngoingExists();
                }
            }

            if ($sales->wasChanged(['status', 'quotation_sign'])) {
                $sales->syncCampaignOngoingStatus();
            }
        });

        static::saved(function (BvSales $sales) {
            $sales->syncBriefStatus();
        });
    }

    /**
     * Apakah campaign ini sudah memiliki brief (upload dokumen, link, atau brief client tersubmit).
     * FormBrief berstatus draft (dibuat otomatis saat briefing) tidak dihitung.
     */
    public function hasBrief(): bool
    {
        return filled($this->brief_link)
            || filled($this->brief_files)
            || $this->briefHistories()->exists()
            || $this->formBrief()->where('status', '!=', 'draft')->exists();
    }

    /**
     * Saat brief sudah terisi, otomatis maju ke Proposal Building.
     * Hanya memajukan dari tahap awal (Not Started / Pitching / Briefing).
     */
    public function syncBriefStatus(): void
    {
        if (! $this->hasBrief()) {
            return;
        }

        $earlyStages = [
            SalesStatus::NOT_STARTED->value,
            SalesStatus::PITCHING->value,
            SalesStatus::BRIEFING->value,
        ];

        if (in_array($this->status?->value, $earlyStages, true)) {
            // Update langsung via query builder agar aman dipanggil dari dalam event model
            // (menghindari re-entrant save) dan tidak memicu ulang hook status.
            $this->newQuery()->whereKey($this->getKey())->update([
                'status' => SalesStatus::PROPOSAL_BUILDING->value,
            ]);

            $this->setAttribute('status', SalesStatus::PROPOSAL_BUILDING);
            $this->syncOriginalAttribute('status');
        }
    }

    /**
     * Buat FormBrief jika belum ada saat status = BRIEFING.
     */
    public function ensureFormBriefExists(): FormBrief
    {
        if ($this->formBrief) {
            return $this->formBrief;
        }

        return $this->formBrief()->create([
            'title' => 'Brief — '.$this->event_name,
            'brand' => $this->company_name,
            'campaign_name' => $this->event_name,
        ]);
    }

    /**
     * Buat MediaPlan Internal jika belum ada saat status = BRIEFING.
     */
    public function ensureMediaPlanExists(): void
    {
        if ($this->mediaPlan()->exists()) {
            return;
        }

        $picInternal = $this->salesList?->nama_sales;
        $year = now()->year;
        $count = MediaPlan::whereYear('created_at', $year)->count() + 1;
        $quotationNumber = 'BV-'.$year.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

        MediaPlan::create([
            'bv_sales_id' => $this->id,
            'brand' => $this->company_name,
            'pic_client' => $this->pic_media_plan ?? $picInternal ?? '-',
            'quotation_number' => $quotationNumber,
            'campaign_name' => $this->event_name,
            'campaign_period_start' => $this->start_date ? Carbon::parse($this->start_date)->format('Y-m-d') : null,
            'campaign_period_end' => $this->end_date ? Carbon::parse($this->end_date)->format('Y-m-d') : null,
            'status' => 'Planning',
            'pic_sales_bd_id' => $this->bv_sales_list_id,
        ]);
    }

    /**
     * Buat Campaign Ongoing Internal (BvCampign) jika belum ada saat status = CAMPAIGN_LIVE.
     */
    public function ensureCampaignOngoingExists(): void
    {
        if ($this->campaign()->exists()) {
            return;
        }

        $client = DataClient::where('nama_brand', $this->company_name)->first();
        $picInternal = $this->salesList?->nama_sales;
        $agencyName = $client?->agency_name;

        BvCampign::create([
            'bv_sales_id' => $this->id,
            'form_brief_id' => $this->form_brief_id,
            'client_id' => $client?->id,
            'client_type' => $client?->type ?? 'direct',
            'agency_name' => is_array($agencyName) ? implode(', ', $agencyName) : $agencyName,
            'campaign_name' => $this->event_name,
            'campaign_description' => $this->detail ?? '-',
            'campaign_month' => $this->campaign_month,
            'campaign_date' => $this->campaign_date,
            'deal_value' => $this->deal_value ?? 0,
            'total_cost' => $this->budget_propose ?? 0,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'close_date' => $this->close_date,
            'brief_received_date' => $this->brief_submit_date,
            'pic_internal' => $picInternal,
            'pic_media_plan' => $this->pic_media_plan,
            'status' => 'ongoing',
        ]);

        // Jika InternalBudget sudah di-approve AM, langsung sync KOL entries + seed
        // draft storyline ke campaign. (Status valid InternalBudget = 'approve_am',
        // bukan 'approved' — perbaikan kondisi mati yang membuat sync tak pernah jalan.)
        $internalBudget = $this->mediaPlan?->internalBudget;
        if ($internalBudget && $internalBudget->status === 'approve_am') {
            $internalBudget->syncCampaignKolsFromApprovedBudget();
        }
    }

    /**
     * Sync media_platforms campaign ketika status live + quotation_sign sudah diupload.
     */
    public function syncCampaignOngoingStatus(): void
    {
        $campaign = $this->campaign;
        if (! $campaign) {
            return;
        }

        $isLive = $this->status === SalesStatus::CAMPAIGN_LIVE;
        $hasQuotationSign = ! empty($this->quotation_sign);

        if ($isLive && $hasQuotationSign) {
            $platforms = $campaign->kols()
                ->distinct()
                ->pluck('platform')
                ->filter()
                ->values()
                ->toArray();

            $campaign->update([
                'status' => 'ongoing',
                'media_platforms' => $platforms ?: $campaign->media_platforms,
            ]);
        }
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function client(): BelongsTo
    {
        return $this->belongsTo(DataClient::class, 'company_name', 'nama_brand');
    }

    public function salesList(): BelongsTo
    {
        return $this->belongsTo(BvSalesList::class, 'bv_sales_list_id');
    }

    public function salesComments(): HasMany
    {
        return $this->hasMany(BvSalesComment::class, 'bv_sales_id')
            ->whereNull('parent_id')
            ->with(['user', 'replies'])
            ->latest();
    }

    public function formBrief(): HasOne
    {
        return $this->hasOne(FormBrief::class, 'bv_sales_id');
    }

    public function briefHistories(): HasMany
    {
        return $this->hasMany(BriefHistory::class, 'bv_sales_id')->latest();
    }

    public function campaign(): HasOne
    {
        return $this->hasOne(BvCampign::class, 'bv_sales_id');
    }

    public function mediaPlan(): HasOne
    {
        return $this->hasOne(MediaPlan::class, 'bv_sales_id');
    }

    // -------------------------------------------------------
    // Accessors
    // -------------------------------------------------------

    public function getFormattedBudgetProposeAttribute(): string
    {
        return 'Rp '.number_format((float) $this->budget_propose, 0, ',', '.');
    }

    public function getFormattedDealValueAttribute(): string
    {
        return 'Rp '.number_format((float) $this->deal_value, 0, ',', '.');
    }

    public function getFormattedMarginAttribute(): string
    {
        return number_format((float) $this->margin, 2).'%';
    }
}
