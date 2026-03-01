<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTarget extends Model
{
    protected $guarded = [];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'year' => 'integer',
        'month' => 'integer',
    ];

    // -------------------------------------------------------
    // Relationships  (TS-02)
    // -------------------------------------------------------

    /** Sales person yang memiliki target ini */
    public function salesList(): BelongsTo
    {
        return $this->belongsTo(BvSalesList::class, 'bv_sales_list_id');
    }

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

    public function scopeForSales(Builder $query, int $salesListId): Builder
    {
        return $query->where('bv_sales_list_id', $salesListId);
    }

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

    public static function quarterFromMonth(int $month): int
    {
        return (int) ceil($month / 3);
    }

    /** Total target seluruh sales untuk bulan & tahun tertentu */
    public static function totalAllSalesForMonth(int $year, int $month): float
    {
        return (float) static::forMonth($year, $month)->sum('target_amount');
    }

    /** Total target satu sales untuk setahun */
    public static function totalForSalesYear(int $salesListId, int $year): float
    {
        return (float) static::forSales($salesListId)->forYear($year)->sum('target_amount');
    }

    /** Total target satu sales untuk satu quarter */
    public static function totalForSalesQuarter(int $salesListId, int $year, int $quarter): float
    {
        return (float) static::forSales($salesListId)->forQuarter($year, $quarter)->sum('target_amount');
    }
}
