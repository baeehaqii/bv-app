<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaPlanKol extends Model
{
    protected $table = 'media_plan_kols';

    protected $guarded = [];

    protected $casts = [
        'is_selected' => 'boolean',
        'row_number' => 'integer',
        'tipe_pajak_kol' => 'integer',
        'links' => 'array', // JSON array for multiple links
        'scope_items' => 'array', // JSON array for scope of work items
        'qty' => 'integer', // berapa kali SOW baris ini di-request
        'followers' => 'integer',
        'impression' => 'integer',
        'engagement' => 'integer',
        'er_percent' => 'decimal:4',
        'cpi_cpv' => 'float',
        'cpe' => 'float',
        'rate' => 'float',
        'after_nego' => 'float',
        'payment_date' => 'date',
        // Terisi berarti baris ini datang dari migrasi spreadsheet, bukan form.
        'imported_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status options KOL. Sumber kanonik: App\Enums\MediaPlanKolStatus.
     * Gunakan MediaPlanKolStatus::toArray() (internal) atau toArrayExternal() (tanpa Payment Gateway).
     */
    const STATUS_OPTIONS = [
        'Move to Client' => 'Move to Client',
        'Approved by Client' => 'Approved by Client',
        'Unavail' => 'Unavail',
        'New List' => 'New List',
        'HOLD' => 'HOLD',
        'Rejected' => 'Rejected',
        'AVAILABLE' => 'AVAILABLE',
        'Approaching' => 'Approaching',
        'Req Client' => 'Req Client',
        'Need Confirmation' => 'Need Confirmation',
        'Need Rate Nego' => 'Need Rate Nego',
        'Payment Gateway' => 'Payment Gateway',
        'Referensi' => 'Referensi',
        'Replied' => 'Replied',
    ];

    /**
     * Get the media plan this KOL belongs to
     */
    public function mediaPlan(): BelongsTo
    {
        return $this->belongsTo(MediaPlan::class);
    }

    /**
     * Get the original KOL data if selected from database
     */
    public function dataKol(): BelongsTo
    {
        return $this->belongsTo(DataKol::class);
    }

    /**
     * Get the tax type (PPh) for this KOL
     */
    public function tipePajakKol(): BelongsTo
    {
        return $this->belongsTo(MasterPph::class, 'tipe_pajak_kol');
    }

    /**
     * Get all internal budget items for this KOL
     */
    public function internalBudgetItems(): HasMany
    {
        return $this->hasMany(InternalBudgetItem::class);
    }

    /**
     * Kolom I sheet KOL List. Ambangnya diatur di
     * "Masterdata Media Plan Internal", bukan ditulis di kode.
     */
    public static function calculateTier(int $followers): string
    {
        return MediaPlanCalcSetting::current()->tierFor($followers);
    }

    /**
     * Calculate engagement from followers and ER
     */
    public function calculateEngagement(): int
    {
        return intval($this->followers * ($this->er_percent / 100));
    }

    /**
     * Get total rate from all internal budget items (Rounded value)
     */
    public function getTotalRoundedAttribute(): float
    {
        return $this->internalBudgetItems()->sum('rounded');
    }

    /**
     * Calculate CPI/CPV (Cost Per Impression/View)
     * Formula: Rate (Rounded) / Impression
     */
    public function calculateCpiCpv(): float
    {
        if ($this->impression == 0) {
            return 0;
        }

        return $this->total_rounded / $this->impression;
    }

    /**
     * Calculate CPE (Cost Per Engagement)
     * Formula: Rate (Rounded) / Engagement
     */
    public function calculateCpe(): float
    {
        if ($this->engagement == 0) {
            return 0;
        }

        return $this->total_rounded / $this->engagement;
    }

    /**
     * Update rate from internal budget items
     */
    public function syncRateFromBudget(): void
    {
        $this->rate = $this->total_rounded;
        $this->cpi_cpv = $this->calculateCpiCpv();
        $this->cpe = $this->calculateCpe();
        $this->saveQuietly();
    }
}
