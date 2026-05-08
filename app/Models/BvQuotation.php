<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BvQuotation extends Model
{
    protected $guarded = [];

    public function internalBudget(): BelongsTo
    {
        return $this->belongsTo(InternalBudget::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
