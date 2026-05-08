<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataKol extends Model
{
    protected $guarded = [];

    protected $casts = [
        'category' => 'array',
    ];

    public function rateCards(): HasMany
    {
        return $this->hasMany(KolRateCard::class)->orderByDesc('valid_from');
    }
}
