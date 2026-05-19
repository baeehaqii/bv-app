<?php

namespace App\Models;

use App\Enums\DivisionSyncType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
