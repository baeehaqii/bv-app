<?php

namespace App\Models;

use App\Enums\DivisionSyncType;
use App\Observers\DivisionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(DivisionObserver::class)]
class Division extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sync_type' => DivisionSyncType::class,
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
