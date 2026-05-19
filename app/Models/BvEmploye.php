<?php

namespace App\Models;

use App\Observers\BvEmployeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(BvEmployeObserver::class)]
class BvEmploye extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(BvEmploye::class, 'reports_to_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(BvEmploye::class, 'reports_to_id');
    }
}
