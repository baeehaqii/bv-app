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
}
