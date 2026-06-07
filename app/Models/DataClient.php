<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataClient extends Model
{
    protected $guarded = [];

    protected $casts = [
        'has_agency' => 'boolean',
    ];

    /** Transaksi keuangan yang terhubung ke client ini */
    public function cashflows(): HasMany
    {
        return $this->hasMany(BvCashflow::class);
    }

    private static function decodeJsonField(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_null($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function getPicsAttribute(mixed $value): array
    {
        return self::decodeJsonField($value);
    }

    public function setPicsAttribute(mixed $value): void
    {
        $this->attributes['pics'] = json_encode(is_array($value) ? $value : []);
    }

    public function getAgencyBrandsAttribute(mixed $value): array
    {
        return self::decodeJsonField($value);
    }

    public function setAgencyBrandsAttribute(mixed $value): void
    {
        $this->attributes['agency_brands'] = json_encode(is_array($value) ? $value : []);
    }

    public function getAgencyNameAttribute(mixed $value): array
    {
        return self::decodeJsonField($value);
    }

    public function setAgencyNameAttribute(mixed $value): void
    {
        $this->attributes['agency_name'] = json_encode(is_array($value) ? $value : []);
    }

    public function getPicClientsAttribute(mixed $value): array
    {
        return self::decodeJsonField($value);
    }

    public function setPicClientsAttribute(mixed $value): void
    {
        $this->attributes['pic_clients'] = json_encode(is_array($value) ? $value : []);
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /** Semua campaign yang dimiliki client ini */
    public function campaigns(): HasMany
    {
        return $this->hasMany(BvCampign::class, 'client_id');
    }

    /** Campaign terbaru milik client (untuk kolom "Last Campaign") */
    public function latestCampaign(): HasOne
    {
        return $this->hasOne(BvCampign::class, 'client_id')->latestOfMany();
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
