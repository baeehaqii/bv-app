<?php

namespace App\Models;

use App\Enums\SalesStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrossProfitTarget extends Model
{
    protected $guarded = [];

    protected $casts = [
        'target_amount'            => 'decimal:2',
        'target_deal_revenue'      => 'decimal:2',
        'margin_benchmark_percent' => 'decimal:2',
        'year'                     => 'integer',
        'month'                    => 'integer',
    ];

    private ?array $actualsMemo = null;

    protected static function booted(): void
    {
        // Target GP selalu = target revenue x benchmark margin (sesuai sheet: 31%).
        static::saving(function (self $target) {
            $target->target_amount = round(
                (float) $target->target_deal_revenue * (float) ($target->margin_benchmark_percent ?? 31) / 100
            );
        });
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeForQuarter(Builder $query, int $year, int $quarter): Builder
    {
        $months = static::quarterMonths($quarter);
        return $query->where('year', $year)->whereIn('month', $months);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Kembalikan array bulan [start, end] untuk quarter tertentu.
     */
    public static function quarterMonths(int $quarter): array
    {
        return match ($quarter) {
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12],
            default => [],
        };
    }

    /**
     * Total target untuk satu quarter (tahun tertentu).
     */
    public static function totalForQuarter(int $year, int $quarter): float
    {
        return (float) static::forQuarter($year, $quarter)->sum('target_amount');
    }

    /**
     * Total target untuk satu tahun penuh.
     */
    public static function totalForYear(int $year): float
    {
        return (float) static::forYear($year)->sum('target_amount');
    }

    /**
     * Target Deal Revenue (omset) untuk bulan tertentu.
     */
    public static function dealRevenueForMonth(int $year, int $month): float
    {
        return (float) static::forMonth($year, $month)->value('target_deal_revenue') ?? 0;
    }

    /**
     * Total Target Deal Revenue untuk satu tahun penuh.
     */
    public static function dealRevenueForYear(int $year): float
    {
        return (float) static::forYear($year)->sum('target_deal_revenue');
    }

    /**
     * Nomor quarter dari bulan tertentu.
     */
    public static function quarterFromMonth(int $month): int
    {
        return (int) ceil($month / 3);
    }

    // -------------------------------------------------------
    // Realisasi (Actual) dari deal yang sudah won
    // -------------------------------------------------------

    /** Realisasi revenue & gross profit satu bulan, dari deal berstatus won. */
    public static function actualsForMonth(int $year, int $month): array
    {
        $row = BvSales::query()
            ->whereIn('status', SalesStatus::wonValues())
            ->whereYear('close_date', $year)
            ->whereMonth('close_date', $month)
            ->selectRaw('COALESCE(SUM(deal_value), 0) as revenue')
            ->selectRaw('COALESCE(SUM(deal_value * margin / 100), 0) as gp')
            ->first();

        return [
            'revenue' => (float) $row->revenue,
            'gp'      => (float) $row->gp,
        ];
    }

    /** ponytail: memo per instance — tabel target baca 4 kolom actual per baris, cukup 1 query */
    private function actuals(): array
    {
        return $this->actualsMemo ??= static::actualsForMonth($this->year, $this->month);
    }

    // -------------------------------------------------------
    // Accessors
    // -------------------------------------------------------

    public function getActualRevenueAttribute(): float
    {
        return $this->actuals()['revenue'];
    }

    public function getActualGpAttribute(): float
    {
        return $this->actuals()['gp'];
    }

    /** % realisasi GP terhadap target GP bulan ini */
    public function getGpAchievementPercentAttribute(): float
    {
        $target = (float) $this->target_amount;

        return $target > 0 ? round($this->actual_gp / $target * 100, 2) : 0.0;
    }

    /** % margin riil = actual GP / actual revenue */
    public function getProfitMarginPercentAttribute(): float
    {
        $revenue = $this->actual_revenue;

        return $revenue > 0 ? round($this->actual_gp / $revenue * 100, 2) : 0.0;
    }

    public function getMonthNameAttribute(): string
    {
        return Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F');
    }

    public function getQuarterAttribute(): int
    {
        return static::quarterFromMonth($this->month);
    }

    public function getQuarterTargetAttribute(): float
    {
        return static::totalForQuarter($this->year, $this->quarter);
    }

    public function getYearTargetAttribute(): float
    {
        return static::totalForYear($this->year);
    }
}
