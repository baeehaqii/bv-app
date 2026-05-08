<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BvEmploye extends Model
{
    protected $guarded = [];

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
