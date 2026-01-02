<?php

namespace App\Models;

use App\Enums\SalesStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BvSales extends Model
{
    protected $table = 'bv_sales';

    protected $fillable = [
        'bv_sales_list_id',
        'event_name',
        'company_name',
        'campaign_items',
        'deal_value',
        'margin',
        'campaign_periode',
        'campaign_year',
        'close_date',
        'comments',
        'detail',
        'status',
        'position',
    ];

    protected $casts = [
        'campaign_items' => 'array',
        'deal_value' => 'decimal:2',
        'margin' => 'decimal:2',
        'campaign_year' => 'integer',
        'close_date' => 'date',
        'status' => SalesStatus::class,
    ];

    public function salesList(): BelongsTo
    {
        return $this->belongsTo(BvSalesList::class, 'bv_sales_list_id');
    }

    public function getFormattedDealValueAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->deal_value, 0, ',', '.');
    }

    public function getFormattedMarginAttribute(): string
    {
        return number_format((float) $this->margin, 2) . '%';
    }
}
