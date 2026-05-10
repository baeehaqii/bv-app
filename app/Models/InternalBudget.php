<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InternalBudget extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status options
     */
    const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    /**
     * Approve budget dan trigger aktivasi campaign
     */
    public function approve(): void
    {
        $this->update(['status' => 'approved', 'rejection_notes' => null]);
    }

    /**
     * Reject budget dengan alasan penolakan
     */
    public function reject(string $rejectionNotes): void
    {
        $this->update(['status' => 'rejected', 'rejection_notes' => $rejectionNotes]);
    }

    /**
     * Parse formatted number to float
     * Indonesian format: titik = ribuan, koma = desimal
     * "2.000.000" → 2000000
     * "2.000.000,50" → 2000000.50
     * "99,98" → 99.98
     */
    private static function parseNumber($value): float
    {
        if (empty($value))
            return 0;
        if (is_numeric($value))
            return (float) $value;

        $value = (string) $value;

        // Remove all dots (thousand separator in Indonesian format)
        $cleaned = str_replace('.', '', $value);
        // Replace comma with dot (decimal separator in Indonesian format)
        $cleaned = str_replace(',', '.', $cleaned);

        return (float) $cleaned;
    }

    /**
     * Get the media plan this budget belongs to
     */
    public function mediaPlan(): BelongsTo
    {
        return $this->belongsTo(MediaPlan::class);
    }

    /**
     * Get all budget items
     */
    public function items(): HasMany
    {
        return $this->hasMany(InternalBudgetItem::class)->orderBy('sort_order');
    }

    /**
     * Get the quotation generated from this budget
     */
    public function quotation(): HasOne
    {
        return $this->hasOne(BvQuotation::class);
    }

    /**
     * Generate (or regenerate) a BvQuotation from this budget's approved items.
     * Uses total_rounded as the billing amount (client-facing price after markup).
     */
    public function generateQuotation(): BvQuotation
    {
        $mediaPlan = $this->mediaPlan;
        $clientName = $mediaPlan?->bvSales?->dataClient?->nama_brand
            ?? $mediaPlan?->brand
            ?? 'Client';

        $clientEmail = $mediaPlan?->bvSales?->dataClient?->email ?? null;

        $quotationNumber = \App\Helpers\QuotationNumberGenerator::generate();

        $quotation = $this->quotation()->updateOrCreate(
            ['internal_budget_id' => $this->id],
            [
                'quotation_number' => $this->quotation?->quotation_number ?? $quotationNumber,
                'quotation_date' => now()->toDateString(),
                'expiry_date' => now()->addDays(14)->toDateString(),
                'client_name' => $clientName,
                'client_email' => $clientEmail,
                'subtotal' => $this->total_rounded ?? 0,
                'discount' => 0,
                'total_amount' => $this->total_rounded ?? 0,
                'status' => 'draft',
                'user_id' => auth()->id(),
            ]
        );

        return $quotation;
    }

    /**
     * Parse scope_item string → [platform, content_type] for BvCampaignKol.
     * Maps human-readable SOW labels (e.g. "IG Reels", "TT Video") to
     * the platform/content_type values used by BvCampaignKol.
     */
    public static function parseScopeItemToChannel(string $scopeItem): array
    {
        $scope = strtolower($scopeItem);

        // Instagram
        if (str_contains($scope, 'instagram') || preg_match('/\big\b/', $scope)) {
            if (str_contains($scope, 'reel'))
                return ['platform' => 'instagram', 'content_type' => 'reels'];
            if (str_contains($scope, 'story') || str_contains($scope, 'stories'))
                return ['platform' => 'instagram', 'content_type' => 'story'];
            return ['platform' => 'instagram', 'content_type' => 'feed']; // post / feed
        }

        // TikTok
        if (str_contains($scope, 'tiktok') || preg_match('/\btt\b/', $scope)) {
            if (str_contains($scope, 'story'))
                return ['platform' => 'tiktok', 'content_type' => 'story'];
            if (str_contains($scope, 'photo'))
                return ['platform' => 'tiktok', 'content_type' => 'photos'];
            return ['platform' => 'tiktok', 'content_type' => 'video'];
        }

        // YouTube
        if (str_contains($scope, 'youtube') || preg_match('/\byt\b/', $scope)) {
            if (str_contains($scope, 'short'))
                return ['platform' => 'youtube', 'content_type' => 'short'];
            return ['platform' => 'youtube', 'content_type' => 'video'];
        }

        // Threads
        if (str_contains($scope, 'thread')) {
            return ['platform' => 'threads', 'content_type' => 'post'];
        }

        return ['platform' => 'instagram', 'content_type' => 'feed'];
    }

    /**
     * Sync approved budget items → BvCampaignKol entries in the linked campaign.
     * Idempotent: deletes existing KOL entries then recreates from approved items.
     * No-op when no campaign exists yet (safe to call at any time).
     */
    public function syncCampaignKolsFromApprovedBudget(): void
    {
        $campaign = $this->mediaPlan?->bvSales?->campaign;
        if (!$campaign) {
            return;
        }

        $approvedItems = $this->items()
            ->where('status', 'approved')
            ->with('mediaPlanKol')
            ->orderBy('sort_order')
            ->get();

        if ($approvedItems->isEmpty()) {
            return;
        }

        // Replace all KOL entries with data from approved budget items
        $campaign->kols()->delete();

        foreach ($approvedItems as $item) {
            ['platform' => $platform, 'content_type' => $contentType] =
                self::parseScopeItemToChannel($item->scope_item ?? '');

            \App\Models\BvCampaignKol::create([
                'campaign_id' => $campaign->id,
                'creator_name' => $item->mediaPlanKol?->name ?? '—',
                'price' => (float) ($item->rounded ?? 0),
                'platform' => $platform,
                'content_type' => $contentType,
                'status' => 'pending',
            ]);
        }

        // Update campaign totals & media platforms
        $platforms = $approvedItems
            ->map(fn($item) => self::parseScopeItemToChannel($item->scope_item ?? '')['platform'])
            ->unique()
            ->values()
            ->toArray();

        $campaign->update([
            'deal_value' => $this->total_rounded ?? 0,
            'total_cost' => $this->total_mu_pph ?? 0,
            'media_platforms' => $platforms,
        ]);
    }

    /**
     * Recalculate all totals from items
     */
    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $totalRate = 0;
        $totalSubtotal = 0;
        $totalMuPph = 0;
        $totalPublishedRate = 0;
        $totalRounded = 0;
        $marginSum = 0;
        $marginCount = 0;

        foreach ($items as $item) {
            $totalRate += self::parseNumber($item->rate_base);
            $totalSubtotal += self::parseNumber($item->subtotal);
            $totalMuPph += self::parseNumber($item->mu_pph);
            $totalPublishedRate += self::parseNumber($item->published_rate);
            $totalRounded += self::parseNumber($item->rounded);

            $margin = self::parseNumber($item->actual_margin_percent);
            if ($margin > 0) {
                $marginSum += $margin;
                $marginCount++;
            }
        }

        $this->total_rate = $totalRate;
        $this->total_subtotal = $totalSubtotal;
        $this->total_mu_pph = $totalMuPph;
        $this->total_published_rate = $totalPublishedRate;
        $this->total_rounded = $totalRounded;
        $this->average_margin_percent = $marginCount > 0 ? $marginSum / $marginCount : 0;

        $this->generateWarnings();
        $this->saveQuietly();
    }

    /**
     * Check for margin warnings (< 30%) and budget warnings
     */
    public function generateWarnings(): void
    {
        $warnings = [];

        foreach ($this->items as $item) {
            $margin = self::parseNumber($item->actual_margin_percent);
            if ($margin > 0 && $margin < 30) {
                $warnings[] = "⚠️ {$item->scope_item}: Margin " . number_format($margin, 2) . "% < 30%";
            }
        }

        $muPph = self::parseNumber($this->total_mu_pph);
        if ($muPph > 97500000) {
            $warnings[] = "⚠️ MU PPh > IDR 97,500,000";
        }

        $this->warnings = empty($warnings) ? null : implode("\n", $warnings);
    }

    /**
     * Calculate summary only from selected KOLs items
     */
    public function calculateSelectedSummary(): array
    {
        $selectedKolIds = $this->mediaPlan?->selectedKols()->pluck('id') ?? collect([]);
        $selectedItems = $this->items()->whereIn('media_plan_kol_id', $selectedKolIds)->get();

        $totalRate = 0;
        $totalSubtotal = 0;
        $totalMuPph = 0;
        $totalRounded = 0;

        foreach ($selectedItems as $item) {
            $totalRate += self::parseNumber($item->rate_base);
            $totalSubtotal += self::parseNumber($item->subtotal);
            $totalMuPph += self::parseNumber($item->mu_pph);
            $totalRounded += self::parseNumber($item->rounded);
        }

        return [
            'total_rate' => $totalRate,
            'total_subtotal' => $totalSubtotal,
            'total_mu_pph' => $totalMuPph,
            'total_rounded' => $totalRounded,
            'item_count' => $selectedItems->count(),
        ];
    }

    /**
     * Boot method to handle events
     */
    protected static function booted(): void
    {
        static::updated(function (InternalBudget $budget) {
            // Only proceed if status is approved and was changed
            if ($budget->status !== 'approved' || !$budget->wasChanged('status')) {
                return;
            }

            if (!$budget->relationLoaded('mediaPlan')) {
                $budget->load('mediaPlan');
            }

            $mediaPlan = $budget->mediaPlan;
            if (!$mediaPlan) {
                return;
            }

            // Jika MediaPlan ini dibuat otomatis dari BvSales (ada bv_sales_id),
            // gunakan tryActivateCampaign() — akan aktifkan BvCampign jika kedua plan sudah approve
            if ($mediaPlan->bv_sales_id) {
                $mediaPlan->tryActivateCampaign();
                return;
            }

            // Fallback: MediaPlan dibuat manual (tanpa bv_sales_id)
            // Buat BvCampign baru dari data MediaPlan (perilaku lama)
            $client = \App\Models\DataClient::where('nama_brand', $mediaPlan->brand)->first();

            $exists = \App\Models\BvCampign::where('campaign_name', $mediaPlan->campaign_name)
                ->where('client_id', $client?->id)
                ->exists();

            if ($exists) {
                return;
            }

            $startDate = null;
            $endDate = null;
            try {
                if ($mediaPlan->campaign_period_start) {
                    $startDate = \Carbon\Carbon::parse($mediaPlan->campaign_period_start);
                }
                if ($mediaPlan->campaign_period_end) {
                    $endDate = \Carbon\Carbon::parse($mediaPlan->campaign_period_end);
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

            $mediaPlan->loadMissing('selectedKols.dataKol');
            $selectedKols = $mediaPlan->selectedKols;
            $platforms = [];

            $campaign = \App\Models\BvCampign::create([
                'client_id' => $client?->id,
                'campaign_name' => $mediaPlan->campaign_name,
                'campaign_description' => $mediaPlan->notes ?? 'Auto-generated from Media Plan',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $campaignStatus,
                'total_cost' => $budget->total_mu_pph,
                'pic_internal' => auth()->user()?->name ?? 'System',
            ]);

            foreach ($selectedKols as $kol) {
                $scopes = $kol->scope_items ?? [];
                if (empty($scopes)) {
                    continue;
                }

                foreach ($scopes as $scope) {
                    [$platform, $contentType] = \App\Models\MediaPlan::detectPlatformFromScope($scope);
                    $platforms[] = $platform;

                    \App\Models\BvCampaignKol::create([
                        'campaign_id' => $campaign->id,
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

            $campaign->update([
                'media_platforms' => array_values(array_unique($platforms)),
            ]);
        });
    }
}
