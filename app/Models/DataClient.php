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

    protected static function booted(): void
    {
        // Brand yang di-handle agency disimpan sebagai JSON (agency_brands),
        // tapi harus ikut muncul di tab Database Brand + hitungan widget.
        // Jadi setiap perubahan daftar itu disinkronkan jadi baris direct brand asli.
        static::saved(function (self $client) {
            // wasChanged() selalu kosong setelah insert, jadi cek wasRecentlyCreated juga.
            if ($client->type === 'agency' && ($client->wasRecentlyCreated || $client->wasChanged('agency_brands'))) {
                $client->syncAgencyBrands();
            }
        });
    }

    /**
     * Buat/tautkan baris direct brand untuk tiap entri agency_brands.
     * Brand yang dilepas dari agency hanya diputus tautannya, tidak dihapus.
     */
    public function syncAgencyBrands(): void
    {
        $linked = [];

        foreach ($this->agency_brands as $brand) {
            $name = trim($brand['nama_brand'] ?? '');
            if ($name === '') {
                continue;
            }

            $row = static::firstOrNew(['type' => 'direct', 'nama_brand' => $name]);

            if (! $row->exists) {
                $row->category = $brand['category'] ?? null;
                $row->notes = $brand['description'] ?? null;
                $row->pic_internal_sales_id = $this->pic_internal_sales_id;
                $row->status = $this->status;
                $pic = array_filter([
                    'name' => $brand['nama_pic'] ?? null,
                    'email' => $brand['email'] ?? null,
                    'wa_number' => $brand['wa_number'] ?? null,
                ]);
                $row->pic_clients = $pic ? [$pic] : [];
            }

            $row->agency_client_id = $this->id;
            $row->has_agency = true;
            $row->save();

            $linked[] = $row->id;
        }

        static::where('agency_client_id', $this->id)
            ->whereNotIn('id', $linked)
            ->update(['agency_client_id' => null, 'has_agency' => false]);
    }

    /** Direct brand yang di-handle agency ini (baris hasil sync) */
    /**
     * Email PIC client (JSON pic_clients / pics). Prioritas: PIC leads, lalu PIC pertama.
     * Key-nya pernah dua versi ('email' & 'email_pic'), jadi dua-duanya dicek.
     */
    public function getPicEmailAttribute(): ?string
    {
        $pics = collect($this->pic_clients)->concat($this->pics);
        $pic = $pics->firstWhere('is_leads', true) ?? $pics->first();

        return $pic['email'] ?? $pic['email_pic'] ?? null;
    }

    /**
     * Data client untuk section "Detail Quotation" — dipakai bareng oleh form
     * Quotation & InternalBudget::generateQuotation() agar isinya konsisten.
     * $campaignBrand dipakai untuk agency: brand yang dikampanyekan, bukan nama agency-nya.
     *
     * @return array{client_type:?string, client_brand:?string, client_email:?string, client_address:?string}
     */
    public function quotationFields(?string $campaignBrand = null): array
    {
        return [
            'client_type' => $this->type,
            'client_brand' => $this->type === 'agency'
                ? ($campaignBrand ?: null)
                : $this->nama_brand,
            'client_email' => $this->pic_email,
            'client_address' => $this->alamat,
        ];
    }

    public function handledBrands(): HasMany
    {
        return $this->hasMany(self::class, 'agency_client_id');
    }

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

    // -------------------------------------------------------
    // Accessor
    // -------------------------------------------------------

    public function getFilamentRecordTitle(): ?string
    {
        return $this->nama_brand;
    }
}
