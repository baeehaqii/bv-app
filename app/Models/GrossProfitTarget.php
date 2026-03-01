<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrossProfitTarget extends Model
{
    protected $guarded = [];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'year' => 'integer',
        'month' => 'integer',
    ];

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
     * Nomor quarter dari bulan tertentu.
     */
    public static function quarterFromMonth(int $month): int
    {
        return (int) ceil($month / 3);
    }

    // -------------------------------------------------------
    // Accessors
    // -------------------------------------------------------

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
