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
        'agency_brands' => 'array',
        'agency_name' => 'array',
        'pic_clients' => 'array',
        'has_agency' => 'boolean',
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

    /** Agency yang menangani direct brand ini */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(self::class, 'agency_client_id');
    }

    /** Daftar direct brand yang di-handle oleh agency ini */
    public function brandClients(): HasMany
    {
        return $this->hasMany(self::class, 'agency_client_id');
    }

    // -------------------------------------------------------
    // Accessor
    // -------------------------------------------------------

    public function getFilamentRecordTitle(): ?string
    {
        return $this->nama_brand;
    }
}
