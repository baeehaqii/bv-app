<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataClient extends Model
{
    protected $guarded = [];

    protected $casts = [
        'pics' => 'array',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /** Semua campaign yang dimiliki client ini */
    public function campaigns(): HasMany
    {
        return $this->hasMany(BvCampign::class, 'client_id');
    }

    /** PIC Internal (Sales) dari tim BV */
    public function picInternalSales(): BelongsTo
    {
        return $this->belongsTo(BvSalesList::class, 'pic_internal_sales_id');
    }

    // -------------------------------------------------------
    // Accessor
    // -------------------------------------------------------

    public function getFilamentRecordTitle(): ?string
    {
        return $this->nama_brand;
    }
}
