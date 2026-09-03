<?php

namespace App\Models;

use App\Enums\OkrStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Okr extends Model
{
    protected $guarded = [];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'month' => 'integer',
        'expected_score' => 'decimal:1',
        'objective_score' => 'decimal:1',
        'status' => OkrStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------
    // Periode
    // -------------------------------------------------------

    /** Label periode baris: nama bulan kalau ada, kalau tidak kuartalnya. */
    public function getPeriodeLabelAttribute(): string
    {
        return $this->month
            ? Carbon::create($this->year, $this->month, 1)->translatedFormat('F')
            : 'Q' . $this->quarter;
    }

    /**
     * Tiga bulan kuartalnya, berpasangan dengan status_month_1..3.
     *
     * @return array<int, array{nomor: int, nama: string, isi: ?string}>
     */
    public function getStatusBulananAttribute(): array
    {
        return collect(static::bulanQuarter($this->quarter))
            ->values()
            ->map(fn(int $bulan, int $i) => [
                'nomor' => $i + 1,
                'nama' => Carbon::create($this->year, $bulan, 1)->translatedFormat('F'),
                'isi' => $this->{'status_month_' . ($i + 1)},
            ])
            ->all();
    }

    public function scopeForQuarter(Builder $query, int $year, int $quarter): Builder
    {
        return $query->where('year', $year)->where('quarter', $quarter);
    }

    /** Kuartal kalender — sama dengan sheet, yang Q2-nya berakhir 30 Juni. */
    public static function quarterFromMonth(int $month): int
    {
        return (int) ceil($month / 3);
    }

    public static function bulanQuarter(int $quarter): array
    {
        $awal = ($quarter - 1) * 3 + 1;

        return [$awal, $awal + 1, $awal + 2];
    }
}
