<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MediaPlan extends Model
{
    protected $guarded = [];

    public function internalBudget(): HasOne
    {
        return $this->hasOne(InternalBudget::class);
    }
}
