<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KolRateCard extends Model
{
    protected $guarded = [];

    protected $casts = [
        'valid_from' => 'date',
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
}
