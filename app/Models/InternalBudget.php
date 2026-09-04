<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class InternalBudget extends Model
{
    protected $guarded = [];

    protected $casts = [
        'review_is_public' => 'boolean',
        'review_submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status options (alur Media Plan External)
     *
     * draft           → BV menyusun Budget Items (hasil sync dari Media Plan Internal)
     * review_client   → dikirim ke client lewat Link Review Client
     * approve_client  → BV finalisasi hasil feedback client (Generate Quotation muncul)
     * approve_am      → Account Manager approve → auto-create Campaign On Going Internal
     * rejected        → ditolak
     */
    const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'review_client' => 'Review ke Client',
        'approve_client' => 'Approve Client',
        'approve_am' => 'Approve AM',
        'rejected' => 'Rejected',
    ];

    /**
     * Status yang menandai budget sudah final secara internal (siap quotation / campaign).
     */
    const STATUS_FINAL = ['approve_client', 'approve_am'];

    /**
     * Approve budget (Approve AM), sync deal_value ke BvSales, dan trigger aktivasi campaign
     */
    public function approve(): void
    {
        $this->update(['status' => 'approve_am', 'rejection_notes' => null]);

        // 4.4 — Sync deal_value ke BvSales berdasarkan total_rounded (harga client setelah markup)
        $bvSales = $this->mediaPlan?->bvSales;
        if ($bvSales && $this->total_rounded > 0) {
            $bvSales->update(['deal_value' => $this->total_rounded]);
        }

        if ($this->mediaPlan) {
            try {
                app(\App\Service\BvNotificationService::class)->budgetApproved($this->mediaPlan);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[InternalBudget] Notifikasi WA approve gagal: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reject budget dengan alasan penolakan
     */
    public function reject(string $rejectionNotes): void
    {
        $this->update(['status' => 'rejected', 'rejection_notes' => $rejectionNotes]);

        if ($this->mediaPlan) {
            try {
                app(\App\Service\BvNotificationService::class)->budgetRejected($this->mediaPlan, $rejectionNotes);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[InternalBudget] Notifikasi WA reject gagal: ' . $e->getMessage());
            }
        }
    }

    /**
     * Generate (atau ambil ulang) token publik untuk Link Review Client.
     */
    public function generateReviewToken(): string
    {
        if (!$this->review_token) {
            $this->review_token = Str::random(48);
        }
        $this->review_is_public = true;
        $this->saveQuietly();

        return $this->review_token;
    }

    /**
     * Cabut akses publik Link Review Client (token tetap disimpan untuk arsip).
     */
    public function revokeReviewToken(): void
    {
        $this->review_is_public = false;
        $this->saveQuietly();
    }

    /**
     * URL publik Link Review Client (null jika belum di-generate).
     */
    public function getReviewUrlAttribute(): ?string
    {
        if (!$this->review_token) {
            return null;
        }

        return route('media-plan-external.review', ['token' => $this->review_token]);
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
        $client = $mediaPlan?->bvSales?->client;

        $clientName = $client?->nama_brand
            ?? $mediaPlan?->brand
            ?? 'Client';

        // Tipe client, brand, email PIC & alamat diambil dari Database Client.
        $clientFields = $client?->quotationFields($mediaPlan?->brand) ?? [
            'client_type' => null,
            'client_brand' => $mediaPlan?->brand,
            'client_email' => null,
            'client_address' => null,
        ];

        $quotationNumber = \App\Helpers\QuotationNumberGenerator::generate();

        $quotation = $this->quotation()->updateOrCreate(
            ['internal_budget_id' => $this->id],
            [
                'quotation_number' => $this->quotation?->quotation_number ?? $quotationNumber,
                'quotation_date' => now()->toDateString(),
                'expiry_date' => now()->addDays(14)->toDateString(),
                'client_name' => $clientName,
                // filter: jangan menimpa data quotation yang sudah diisi manual dengan null.
                ...array_filter($clientFields, fn($v) => filled($v)),
                'subtotal' => $this->total_rounded ?? 0,
                'discount' => 0,
                'total_amount' => ($this->total_rounded ?? 0) + ($this->total_mu_pph ?? 0),
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
     * Ganti KOL pada satu budget item — dipakai saat KOL sudah di-ACC client tapi
     * ternyata tidak available / client minta ganti orang.
     *
     * Item lama TIDAK dihapus (di-reject) supaya jejak persetujuan client tetap ada.
     * Item pengganti dibuat status pending + link review client dibuka lagi agar
     * client bisa meng-ACC penggantinya. Campaign (kalau sudah jalan) dibangun ulang
     * dari item yang approved, jadi KOL lama otomatis keluar dari campaign.
     */
    public function replaceItemKol(InternalBudgetItem $item, int $dataKolId, ?string $reason = null): InternalBudgetItem
    {
        $dataKol = DataKol::findOrFail($dataKolId);
        $oldKol = $item->mediaPlanKol;
        $scope = (string) $item->scope_item;
        $qty = max(1, (int) ($item->qty ?: 1));
        $note = trim('Diganti ke @' . $dataKol->username . ($reason ? ' — ' . $reason : ''));

        $item->reject($note);
        $item->update(['client_choice' => 'rejected']);

        // KOL lama jadi Unavail bila tidak punya item aktif lagi.
        if ($oldKol && $oldKol->internalBudgetItems()->where('status', '!=', 'rejected')->doesntExist()) {
            $oldKol->update(['status' => \App\Enums\MediaPlanKolStatus::UNAVAIL->value]);
        }

        // Baris KOL pengganti di Media Plan Internal (SOW & qty sama dengan yang diganti).
        $newKol = $this->mediaPlan->kols()->create([
            'row_number' => ((int) $this->mediaPlan->kols()->max('row_number')) + 1,
            'data_kol_id' => $dataKol->id,
            'name' => (string) ($dataKol->username ?? ''),
            'channel' => (string) ($dataKol->channel ?? ''),
            'links' => array_filter([$dataKol->link_userprofile]),
            'followers' => (int) $dataKol->followers,
            'tier' => $dataKol->tier,
            'er_percent' => (float) $dataKol->engagement_rate,
            'impression' => (int) $dataKol->impressions,
            'engagement' => (int) $dataKol->engagements,
            'tipe_pajak_kol' => $dataKol->tipe_pajak_kol ?? $oldKol?->tipe_pajak_kol,
            'margin_percent' => $oldKol?->margin_percent,
            'pic' => $oldKol?->pic,
            'scope_items' => [$scope],
            'qty' => $qty,
            'status' => \App\Enums\MediaPlanKolStatus::MOVE_TO_CLIENT->value,
            'is_selected' => true,
        ]);

        // rate_base = rate 1x SOW (qty disimpan terpisah), diambil dari rate card KOL pengganti.
        $rateBase = \App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::computeRateFromSow(
            $dataKol->id,
            $dataKol->username,
            $dataKol->channel,
            [$scope],
        );

        $pphId = $newKol->tipe_pajak_kol ?? $item->master_pph_id ?? MasterPph::defaultId();
        $coeff = MasterPph::find($pphId)?->getCalculatedCoefficient() ?? MasterPph::defaultCalculatedCoefficient();
        $margin = $newKol->margin_percent !== null ? (float) $newKol->margin_percent : null;
        $figs = \App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::computeBudgetFigures($rateBase * $qty, $coeff, $margin);

        $newItem = $this->items()->create([
            'media_plan_kol_id' => $newKol->id,
            'scope_item' => $scope,
            'qty' => $qty,
            'rate_base' => $rateBase,
            'master_pph_id' => $pphId,
            'subtotal' => $figs['subtotal'],
            'mu_pph' => $figs['mu_pph'],
            'mu_target' => $figs['mu_target'],
            'published_rate' => $figs['mu_target'],
            'rounded' => $figs['rounded'],
            'actual_margin_percent' => $figs['actual_margin'],
            'use_flexible_margin' => $margin !== null,
            'margin_percent_override' => $margin,
            'status' => 'pending',
            'notes' => 'Pengganti ' . ($oldKol?->name ?: 'KOL sebelumnya'),
            'sort_order' => ((int) $this->items()->max('sort_order')) + 1,
        ]);

        // Client perlu meng-ACC pengganti → buka lagi form review (link tetap sama).
        if ($this->review_submitted_at) {
            $this->forceFill(['review_submitted_at' => null])->saveQuietly();
        }

        $this->refresh()->recalculateTotals();

        // Campaign yang sudah jalan: buang draft storyline KOL lama, lalu bangun ulang dari approved items.
        $campaign = $this->mediaPlan?->bvSales?->campaign()->first();
        if ($campaign && $oldKol) {
            $campaign->storylines()
                ->where('kol_name', $oldKol->name)
                ->where('sow', $scope)
                ->where('status', 'draft')
                ->delete();
            $this->syncCampaignKolsFromApprovedBudget();
        }

        return $newItem;
    }

    /**
     * Sync approved budget items → BvCampaignKol entries in the linked campaign.
     * Idempotent: deletes existing KOL entries then recreates from approved items.
     * No-op when no campaign exists yet (safe to call at any time).
     */
    public function syncCampaignKolsFromApprovedBudget(): void
    {
        // Resolusi campaign lewat query segar (bukan properti relasi yang bisa
        // ter-cache null sebelum campaign dibuat di alur Campaign Live).
        $bvSales = $this->mediaPlan?->bvSales;
        $campaign = $bvSales?->campaign()->first();
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

        // Update campaign totals & media platforms; tandai sebagai campaign internal
        $platforms = $approvedItems
            ->map(fn($item) => self::parseScopeItemToChannel($item->scope_item ?? '')['platform'])
            ->unique()
            ->values()
            ->toArray();

        $campaign->update([
            'campaign_type' => \App\Models\BvCampign::TYPE_INTERNAL,
            'deal_value' => $this->total_rounded ?? 0,
            'total_cost' => $this->total_mu_pph ?? 0,
            'media_platforms' => $platforms,
        ]);

        // Seed draft Content Planning (storylines) untuk tiap KOL/SOW yang di-approve.
        // PIC Campaign mengisi draft di sini sebelum dikirim ke client & sebelum KOL Performance terisi.
        $this->seedContentPlanningDrafts($campaign, $approvedItems);

        // Buat/relink baris pembayaran KOL (OFERO). Aman: tidak menimpa data bayar yang sudah ada.
        $campaign->load('kols');
        $campaign->syncPaymentRowsFromKols();
    }

    /**
     * Buat draft storyline (Content Planning) untuk tiap approved budget item bila belum ada.
     * Idempotent: tidak menduplikasi storyline untuk pasangan (KOL, SOW) yang sama.
     */
    protected function seedContentPlanningDrafts($campaign, $approvedItems): void
    {
        $existing = $campaign->storylines()
            ->get(['kol_name', 'sow'])
            ->map(fn($s) => $s->kol_name . '|' . $s->sow)
            ->flip()
            ->all();

        foreach ($approvedItems as $item) {
            $kolName = $item->mediaPlanKol?->name ?? '—';
            $sow = $item->scope_item ?? '';
            $key = $kolName . '|' . $sow;

            if (isset($existing[$key])) {
                continue;
            }

            ['platform' => $platform] = self::parseScopeItemToChannel($sow);

            \App\Models\CampaignStoryline::create([
                'bv_campaign_id' => $campaign->id,
                'kol_name' => $kolName,
                'platform' => $platform,
                'sow' => $sow,
                'status' => 'draft',
            ]);

            $existing[$key] = true; // hindari duplikat dalam loop yang sama
        }
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
     * Boot method to handle events
     */
    protected static function booted(): void
    {
        static::updated(function (InternalBudget $budget) {
            // Hanya lanjut jika status berubah menjadi "Approve AM"
            if ($budget->status !== 'approve_am' || !$budget->wasChanged('status')) {
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
                // Pastikan KOL + draft storyline campaign internal ter-seed dari approved
                // budget, apa pun urutannya (campaign bisa dibuat lebih dulu via Campaign Live).
                // No-op bila campaign belum ada; idempotent bila sudah.
                $budget->syncCampaignKolsFromApprovedBudget();
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
                'campaign_type' => \App\Models\BvCampign::TYPE_INTERNAL,
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
