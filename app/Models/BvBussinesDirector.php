<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BvBussinesDirector extends Model
{
    protected $fillable = [
        'nama_lengkap',
        'alamat_email',
        'no_wa',
        'tanggal_gabung',
        'list_sales',
        'status',
    ];

    protected $casts = [
        'tanggal_gabung' => 'date',
        'list_sales' => 'array',
    ];

    public function salesLists(): HasMany
    {
        return $this->hasMany(BvSalesList::class, 'bv_bussines_director_id');
    }

    public function syncListSalesSnapshot(): void
    {
        $salesNames = $this->salesLists()
            ->orderBy('nama_sales')
            ->pluck('nama_sales')
            ->values()
            ->all();

        $this->forceFill([
            'list_sales' => $salesNames,
        ])->saveQuietly();
    }
}
