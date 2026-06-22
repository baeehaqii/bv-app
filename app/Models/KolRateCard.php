<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KolRateCard extends Model
{
    /**
     * Default masa berlaku rate card (hari) bila valid_until tidak diisi,
     * dihitung sejak valid_from.
     */
    public const DEFAULT_VALIDITY_DAYS = 90;

    protected $guarded = [];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'rate' => 'decimal:2',
    ];

    public function dataKol(): BelongsTo
    {
        return $this->belongsTo(DataKol::class);
    }

    public function masterSow(): BelongsTo
    {
        return $this->belongsTo(MasterSow::class);
    }

    /**
     * Label SOW yang ditampilkan: custom_sow_name jika SOW = custom, otherwise nama dari master
     */
    public function getSowLabelAttribute(): string
    {
        if ($this->masterSow?->is_custom && $this->custom_sow_name) {
            return $this->custom_sow_name;
        }
        return $this->masterSow?->name ?? $this->sow ?? '-';
    }

    /**
     * Tanggal kadaluarsa efektif: valid_until bila diisi, jika tidak
     * fallback ke valid_from + DEFAULT_VALIDITY_DAYS. Null bila valid_from kosong.
     */
    public function getEffectiveValidUntilAttribute(): ?CarbonInterface
    {
        if ($this->valid_until) {
            return $this->valid_until;
        }

        return $this->valid_from?->copy()->addDays(self::DEFAULT_VALIDITY_DAYS);
    }

    /**
     * True bila rate card sudah melewati masa berlaku efektif (perlu diperbarui).
     */
    public function isExpired(): bool
    {
        $until = $this->effective_valid_until;

        return $until !== null && $until->isPast();
    }

    /**
     * Sisa hari sampai kadaluarsa (negatif bila sudah lewat). Null bila tak ada tanggal acuan.
     */
    public function daysUntilExpiry(): ?int
    {
        $until = $this->effective_valid_until;

        return $until === null
            ? null
            : (int) now()->startOfDay()->diffInDays($until->copy()->startOfDay(), false);
    }
}
