<?php

namespace App\Models;

use App\Enums\DivisionSyncType;
use App\Observers\DepartmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(DepartmentObserver::class)]
class Department extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sync_type' => DivisionSyncType::class,
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}
