<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            if ($budget->status === 'approved' && $budget->wasChanged('status')) {

                // Load MediaPlan relationship if not loaded
                if (!$budget->relationLoaded('mediaPlan')) {
                    $budget->load('mediaPlan.selectedKols.dataKol');
                }

                $mediaPlan = $budget->mediaPlan;
                if (!$mediaPlan)
                    return;

                // Find Client
                $client = \App\Models\DataClient::where('nama_brand', $mediaPlan->brand)->first();

                // Check if campaign already exists to prevent duplicates
                $exists = \App\Models\BvCampign::where('campaign_name', $mediaPlan->campaign_name)
                    ->where('client_id', $client?->id)
                    ->exists();

                if ($exists) {
                    return;
                }

                // Parse Dates
                $startDate = null;
                $endDate = null;
                try {
                    if ($mediaPlan->campaign_period_start) {
                        $startDate = \Carbon\Carbon::parse($mediaPlan->campaign_period_start);
                    }
                    if ($mediaPlan->campaign_period_end) {
                        $endDate = \Carbon\Carbon::parse($mediaPlan->campaign_period_end);
                    }
                } catch (\Exception $e) {
                    // keep null
                }

                // Determine Status
                $campaignStatus = 'upcoming';
                if ($startDate && $endDate) {
                    if (now()->between($startDate, $endDate)) {
                        $campaignStatus = 'ongoing';
                    } elseif (now()->gt($endDate)) {
                        $campaignStatus = 'completed';
                    }
                }

                // Create Campaign
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

                // Process KOLs
                $selectedKols = $mediaPlan->selectedKols;
                $platforms = [];

                foreach ($selectedKols as $kol) {
                    $scopes = $kol->scope_items ?? [];

                    if (empty($scopes)) {
                        continue;
                    }

                    foreach ($scopes as $scope) {
                        $platform = 'instagram'; // defaults
                        $contentType = 'feed';

                        // Instagram
                        if (stripos($scope, 'IG Reels') !== false || stripos($scope, 'Reels') !== false) {
                            $platform = 'instagram';
                            $contentType = 'reels';
                        } elseif (stripos($scope, 'IG Post') !== false || stripos($scope, 'IG Feed') !== false || stripos($scope, 'Feed') !== false) {
                            $platform = 'instagram';
                            $contentType = 'feed';
                        } elseif (stripos($scope, 'IG Story') !== false || stripos($scope, 'IG Stori') !== false || stripos($scope, 'Story') !== false || stripos($scope, 'Stories') !== false) {
                            $platform = 'instagram';
                            $contentType = 'story';
                        }
                        // TikTok
                        elseif (stripos($scope, 'TikTok Video') !== false || stripos($scope, 'VT') !== false) {
                            $platform = 'tiktok';
                            $contentType = 'video';
                        } elseif (stripos($scope, 'TikTok Story') !== false) {
                            $platform = 'tiktok';
                            $contentType = 'story';
                        } elseif (stripos($scope, 'TikTok Post') !== false || stripos($scope, 'TikTok Photo') !== false) {
                            $platform = 'tiktok';
                            $contentType = 'photos';
                        }
                        // YouTube
                        elseif (stripos($scope, 'YouTube Video') !== false || stripos($scope, 'YT Video') !== false) {
                            $platform = 'youtube';
                            $contentType = 'video';
                        } elseif (stripos($scope, 'YouTube Shorts') !== false || stripos($scope, 'YT Short') !== false) {
                            $platform = 'youtube';
                            $contentType = 'short';
                        }

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

                // Update media_platforms
                $campaign->update([
                    'media_platforms' => array_values(array_unique($platforms))
                ]);
            }
        });
    }
}
