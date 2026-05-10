<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class MediaPlan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'margin_percent' => 'decimal:2',
        'use_global_margin' => 'boolean',
        'sub_pic_campaign_ids' => 'array',
    ];

    /**
     * Trigger saat MediaPlan (Internal) di-approve (status → Ongoing):
     * Jika InternalBudget (External) sudah approved → aktifkan BvCampign ke 'ongoing'
     *
     * Trigger saat status → "To Client":
     * Auto-generate InternalBudget + items dari selected KOLs jika belum ada.
     */
    protected static function booted(): void
    {
        static::updated(function (MediaPlan $mediaPlan) {
            if (!$mediaPlan->wasChanged('status')) {
                return;
            }

            if ($mediaPlan->status === 'Ongoing') {
                $mediaPlan->tryActivateCampaign();
            }

            if ($mediaPlan->status === 'To Client') {
                $mediaPlan->autoGenerateInternalBudget();
            }
        });
    }

    /**
     * Auto-generate InternalBudget + InternalBudgetItems dari selected KOLs.
     * Hanya dibuat jika belum ada. Setiap scope_item per KOL menjadi 1 item budget.
     */
    public function autoGenerateInternalBudget(): void
    {
        // Sudah ada → skip
        if ($this->internalBudget()->exists()) {
            return;
        }

        $selectedKols = $this->selectedKols()->with('dataKol')->get();
        if ($selectedKols->isEmpty()) {
            return;
        }

        $budget = InternalBudget::create([
            'media_plan_id' => $this->id,
            'status' => 'draft',
        ]);

        $sortOrder = 0;
        foreach ($selectedKols as $kol) {
            $scopes = $kol->scope_items ?? [];
            $rate = (float) ($kol->after_nego ?? $kol->rate ?? 0);
            $scopeCount = max(1, count($scopes));
            $ratePerScope = $scopeCount > 0 ? $rate / $scopeCount : $rate;

            foreach ($scopes as $scope) {
                InternalBudgetItem::create([
                    'internal_budget_id' => $budget->id,
                    'media_plan_kol_id' => $kol->id,
                    'master_pph_id' => $kol->tipe_pajak_kol ?? null,
                    'qty' => 1,
                    'scope_item' => $scope,
                    'rate_base' => $ratePerScope,
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Jika KOL tidak punya scope, tetap buat 1 item placeholder
            if (empty($scopes)) {
                InternalBudgetItem::create([
                    'internal_budget_id' => $budget->id,
                    'media_plan_kol_id' => $kol->id,
                    'master_pph_id' => $kol->tipe_pajak_kol ?? null,
                    'qty' => 1,
                    'scope_item' => $kol->name ?? 'KOL',
                    'rate_base' => $rate,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $budget->recalculateTotals();
    }

    /**
     * Sync ulang InternalBudgetItems dari MediaPlanKol yang sedang selected.
     * Hapus items lama lalu buat ulang dari KOLs terkini. Budget header tidak diubah.
     * Digunakan setelah perubahan di Media Plan Internal (KOL, scope, rate).
     */
    public function syncInternalBudgetItems(): void
    {
        $budget = $this->internalBudget;
        if (!$budget) {
            $this->autoGenerateInternalBudget();
            return;
        }

        // Jangan sync jika sudah approved — budget sudah final
        if ($budget->status === 'approved') {
            return;
        }

        $selectedKols = $this->selectedKols()->with('dataKol')->get();
        if ($selectedKols->isEmpty()) {
            return;
        }

        // Kumpulkan pasangan (media_plan_kol_id, scope_item) yang sudah approved/rejected
        // agar tidak tertimpa saat sync ulang
        $lockedPairs = $budget->items()
            ->whereIn('status', ['approved', 'rejected'])
            ->get(['media_plan_kol_id', 'scope_item'])
            ->map(fn($item) => $item->media_plan_kol_id . '|' . $item->scope_item)
            ->flip()
            ->all(); // pakai flip agar bisa pakai isset() O(1)

        // Hapus hanya item yang masih pending
        $budget->items()->where('status', 'pending')->delete();

        // Tentukan sort_order mulai setelah item locked yang sudah ada
        $maxSortOrder = $budget->items()->max('sort_order') ?? -1;
        $sortOrder = $maxSortOrder + 1;

        foreach ($selectedKols as $kol) {
            $scopes = $kol->scope_items ?? [];
            $rate = (float) ($kol->after_nego ?? $kol->rate ?? 0);
            $scopeCount = max(1, count($scopes));
            $ratePerScope = $scopeCount > 0 ? $rate / $scopeCount : $rate;

            foreach ($scopes as $scope) {
                // Lewati jika kombinasi kol+scope ini sudah approved/rejected
                if (isset($lockedPairs[$kol->id . '|' . $scope])) {
                    continue;
                }

                InternalBudgetItem::create([
                    'internal_budget_id' => $budget->id,
                    'media_plan_kol_id' => $kol->id,
                    'master_pph_id' => $kol->tipe_pajak_kol ?? null,
                    'qty' => 1,
                    'scope_item' => $scope,
                    'rate_base' => $ratePerScope,
                    'sort_order' => $sortOrder++,
                ]);
            }

            if (empty($scopes)) {
                $fallbackScope = $kol->name ?? 'KOL';
                if (!isset($lockedPairs[$kol->id . '|' . $fallbackScope])) {
                    InternalBudgetItem::create([
                        'internal_budget_id' => $budget->id,
                        'media_plan_kol_id' => $kol->id,
                        'master_pph_id' => $kol->tipe_pajak_kol ?? null,
                        'qty' => 1,
                        'scope_item' => $fallbackScope,
                        'rate_base' => $rate,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        $budget->recalculateTotals();
    }

    /**
     * Cek apakah kedua media plan (internal & external) sudah approve,
     * jika ya aktifkan BvCampign menjadi 'ongoing' dan sync KOL dari selected KOLs.
     */
    public function tryActivateCampaign(): void
    {
        if (!$this->bv_sales_id) {
            return;
        }

        $bvCampign = BvCampign::where('bv_sales_id', $this->bv_sales_id)->first();
        if (!$bvCampign) {
            return;
        }

        $internalBudget = $this->internalBudget;
        if ($internalBudget?->status !== 'approved' || $this->status !== 'Ongoing') {
            return;
        }

        // Parse campaign dates
        $startDate = null;
        $endDate = null;
        try {
            if ($this->campaign_period_start) {
                $startDate = \Carbon\Carbon::parse($this->campaign_period_start);
            }
            if ($this->campaign_period_end) {
                $endDate = \Carbon\Carbon::parse($this->campaign_period_end);
            }
        } catch (\Exception) {
            // tetap null
        }

        $campaignStatus = 'upcoming';
        if ($startDate && $endDate) {
            if (now()->between($startDate, $endDate)) {
                $campaignStatus = 'ongoing';
            } elseif (now()->gt($endDate)) {
                $campaignStatus = 'completed';
            }
        }

        // Sync KOLs dari MediaPlan selected KOLs (hanya jika belum ada KOL di campaign)
        $platforms = [];
        if ($bvCampign->kols()->count() === 0) {
            $this->loadMissing('selectedKols.dataKol');

            foreach ($this->selectedKols as $kol) {
                $scopes = $kol->scope_items ?? [];
                if (empty($scopes)) {
                    continue;
                }

                foreach ($scopes as $scope) {
                    [$platform, $contentType] = self::detectPlatformFromScope($scope);

                    $platforms[] = $platform;

                    BvCampaignKol::create([
                        'campaign_id' => $bvCampign->id,
                        'creator_name' => $kol->name ?? $kol->dataKol?->username ?? 'Unknown',
                        'username' => $kol->dataKol?->username,
                        'post_url' => $kol->links[0] ?? null,
                        'price' => $kol->rate,
                        'platform' => $platform,
                        'content_type' => $contentType,
                        'status' => 'pending',
                    ]);
                }
            }
        }

        $bvCampign->update([
            'status' => $campaignStatus,
            'total_cost' => $internalBudget->total_mu_pph,
            'start_date' => $startDate ?? $bvCampign->start_date,
            'end_date' => $endDate ?? $bvCampign->end_date,
            'media_platforms' => !empty($platforms)
                ? array_values(array_unique($platforms))
                : $bvCampign->media_platforms,
        ]);
    }

    /**
     * Deteksi platform & content_type dari nama scope item
     */
    public static function detectPlatformFromScope(string $scope): array
    {
        $scope = strtolower($scope);

        if (str_contains($scope, 'reels')) {
            return ['instagram', 'reels'];
        }
        if (str_contains($scope, 'ig story') || str_contains($scope, 'ig stori') || str_contains($scope, 'story') || str_contains($scope, 'stories')) {
            return ['instagram', 'story'];
        }
        if (str_contains($scope, 'ig post') || str_contains($scope, 'ig feed') || str_contains($scope, 'feed')) {
            return ['instagram', 'feed'];
        }
        if (str_contains($scope, 'tiktok video') || str_contains($scope, 'vt')) {
            return ['tiktok', 'video'];
        }
        if (str_contains($scope, 'tiktok story')) {
            return ['tiktok', 'story'];
        }
        if (str_contains($scope, 'tiktok')) {
            return ['tiktok', 'video'];
        }
        if (str_contains($scope, 'youtube short') || str_contains($scope, 'yt short')) {
            return ['youtube', 'short'];
        }
        if (str_contains($scope, 'youtube') || str_contains($scope, 'yt video')) {
            return ['youtube', 'video'];
        }
        if (str_contains($scope, 'threads')) {
            return ['threads', 'post'];
        }

        return ['instagram', 'feed'];
    }

    /**
     * Linked Sales Activity
     */
    public function bvSales(): BelongsTo
    {
        return $this->belongsTo(BvSales::class, 'bv_sales_id');
    }

    /**
     * Get PIC Campaign (Tugas Brief Assignee)
     */
    public function picCampaign(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BvSalesList::class, 'pic_campaign_id');
    }

    /**
     * Get all KOLs for this media plan
     */
    public function kols(): HasMany
    {
        return $this->hasMany(MediaPlanKol::class)->orderBy('row_number');
    }

    /**
     * Get only selected KOLs (for shortlist/quotation)
     */
    public function selectedKols(): HasMany
    {
        return $this->hasMany(MediaPlanKol::class)
            ->where('is_selected', true)
            ->orderBy('row_number');
    }

    /**
     * Get the internal budget for this media plan (1:1)
     */
    public function internalBudget(): HasOne
    {
        return $this->hasOne(InternalBudget::class);
    }

    /**
     * Get total budget amount (from selected KOLs only)
     */
    public function getTotalBudgetAttribute(): float
    {
        return $this->internalBudget?->total_rounded ?? 0;
    }

    /**
     * Get total cost (from selected KOLs only)
     */
    public function getTotalCostAttribute(): float
    {
        return $this->internalBudget?->total_mu_pph ?? 0;
    }

    /**
     * Get average margin percentage
     */
    public function getAverageMarginAttribute(): float
    {
        return $this->internalBudget?->average_margin_percent ?? 0;
    }

    /**
     * Calculate live header summary for selected KOLs only
     * Returns: totalRate, totalImpression, totalEngagement
     */
    public function calculateHeaderSummary(): array
    {
        $selectedKols = $this->kols()->where('is_selected', true)->get();

        $totalRate = 0;
        $totalImpression = 0;
        $totalEngagement = 0;

        foreach ($selectedKols as $kol) {
            $totalRate += $kol->rate;
            $totalImpression += $kol->impression;
            $totalEngagement += $kol->engagement;
        }

        return [
            'total_rate' => $totalRate,
            'total_impression' => $totalImpression,
            'total_engagement' => $totalEngagement,
            'selected_count' => $selectedKols->count(),
        ];
    }

    /**
     * Get next row number for a new KOL
     */
    public function getNextRowNumber(): int
    {
        return ($this->kols()->max('row_number') ?? 0) + 1;
    }
}
