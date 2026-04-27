<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BriefHistory extends Model
{
    protected $guarded = [];

    public function bvSales(): BelongsTo
    {
        return $this->belongsTo(BvSales::class, 'bv_sales_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
