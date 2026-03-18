<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BvSalesList extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(function (BvSalesList $salesList) {
            $directorIds = array_filter([
                $salesList->bv_bussines_director_id,
                $salesList->getOriginal('bv_bussines_director_id'),
            ]);

            if (empty($directorIds)) {
                return;
            }

            BvBussinesDirector::query()
                ->whereIn('id', array_unique($directorIds))
                ->get()
                ->each(fn(BvBussinesDirector $director) => $director->syncListSalesSnapshot());
        });

        static::deleted(function (BvSalesList $salesList) {
            $directorId = $salesList->getOriginal('bv_bussines_director_id');

            if (!$directorId) {
                return;
            }

            $director = BvBussinesDirector::find($directorId);

            if ($director) {
                $director->syncListSalesSnapshot();
            }
        });
    }

    public function bussinesDirector(): BelongsTo
    {
        return $this->belongsTo(BvBussinesDirector::class, 'bv_bussines_director_id');
    }

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
