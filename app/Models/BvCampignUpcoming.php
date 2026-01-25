<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class BvCampignUpcoming extends Model
{
    protected $table = 'bv_campign_upcomings';

    protected $guarded = [];

    protected $casts = [
        'budget_allocated' => 'decimal:2',
        'pot_cpv' => 'decimal:2',
        'pot_cpe' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(DataClient::class, 'client_id');
    }
}
