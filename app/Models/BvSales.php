<?php

namespace App\Models;

use App\Enums\SalesStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BvSales extends Model
{
    protected $table = 'bv_sales';

    protected $fillable = [
        'bv_sales_list_id',
        'event_name',
        'company_name',
        'campaign_items',
        'budget_propose',
        'deal_value',
        'margin',
        'campaign_periode',
        'campaign_year',
        'close_date',
        'comments',
        'detail',
        'brief_files',
        'brief_link',
        'brief_submit_date',
        'status',
        'position',
    ];

    protected $casts = [
        'campaign_items' => 'array',
        'budget_propose' => 'decimal:2',
        'deal_value' => 'decimal:2',
        'margin' => 'decimal:2',
        'brief_files' => 'array',
        'brief_submit_date' => 'date',
        'campaign_year' => 'integer',
        'close_date' => 'date',
        'status' => SalesStatus::class,
    ];

    public function salesList(): BelongsTo
    {
        return $this->belongsTo(BvSalesList::class, 'bv_sales_list_id');
    }

    public function salesComments(): HasMany
    {
        return $this->hasMany(BvSalesComment::class, 'bv_sales_id')->whereNull('parent_id')->with(['user', 'replies'])->latest();
    }

    public function getFormattedBudgetProposeAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->budget_propose, 0, ',', '.');
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
