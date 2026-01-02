<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BvTrackerProgresKol extends Model
{
    protected $table = 'bv_tracker_progres_kols';

    protected $guarded = [];

    protected $casts = [
        'tanggal_posting' => 'date',
    ];

    public function mediaPlanKol(): BelongsTo
    {
        return $this->belongsTo(MediaPlanKol::class);
    }
}
