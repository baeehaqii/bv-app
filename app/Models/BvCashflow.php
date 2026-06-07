<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BvCashflow extends Model
{
    protected $guarded = [];

    public function dataClient(): BelongsTo
    {
        return $this->belongsTo(DataClient::class);
    }
}
