<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BvSalesList extends Model
{
    protected $guarded = [];

    /** Pipeline deal milik sales ini */
    public function sales(): HasMany
    {
        return $this->hasMany(BvSales::class, 'bv_sales_list_id');
    }

    /** Target bulanan yang diset untuk sales ini */
    public function salesTargets(): HasMany
    {
        return $this->hasMany(SalesTarget::class, 'bv_sales_list_id');
    }
}
