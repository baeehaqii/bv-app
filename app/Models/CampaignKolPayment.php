<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris pembayaran KOL pada Campaign Ongoing Internal (acuan sheet "OFERO").
 * Menyimpan data SPK/invoice/rekening + status pencairan per KOL.
 */
class CampaignKolPayment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'real_cost' => 'decimal:2',
        'cost_tax' => 'decimal:2',
        'invoice_date_received' => 'date',
    ];

    public const PAYMENT_STATUSES = [
        'waiting_payment' => 'Waiting Payment',
        'paid' => 'Paid',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BvCampign::class, 'campaign_id');
    }

    public function kol(): BelongsTo
    {
        return $this->belongsTo(BvCampaignKol::class, 'bv_campaign_kol_id');
    }
}
