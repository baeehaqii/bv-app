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

    /** Status bayar berubah → arus kas ikut menyesuaikan (SAK: kas dicatat saat cair). */
    protected static function booted(): void
    {
        static::saved(fn(self $payment) => $payment->syncCashflow());
        static::deleted(fn(self $payment) => BvCashflow::unpost($payment));
    }

    /**
     * Auto-posting pembayaran KOL ke arus kas, dipecah sesuai penyajian SAK:
     * jasa KOL masuk Beban Pokok Pendapatan, selisih gross-up (PPh 23 / PPN)
     * masuk Pembayaran Pajak — di laporan arus kas metode langsung keduanya
     * memang pos terpisah.
     *
     * `cost_tax` = mu_pph = total kas keluar (sudah gross-up),
     * `real_cost` = rate_base = netto jasa KOL.
     */
    public function syncCashflow(): void
    {
        if ($this->payment_status !== 'paid') {
            BvCashflow::unpost($this);

            return;
        }

        // cost_tax di-cast decimal:2 → "0.00" itu truthy, jadi bandingkan sebagai float.
        $gross = (float) $this->cost_tax ?: (float) $this->real_cost;
        $netto = min((float) $this->real_cost, $gross);
        $date  = $this->invoice_date_received ?? $this->updated_at ?? now();
        $ref   = "KOL/{$this->campaign_id}/{$this->id}";
        $kol   = $this->kol_name . ($this->platform ? " ({$this->platform})" : '');

        BvCashflow::post($this, 'beban_pokok_pendapatan', $netto, $date,
            "Pembayaran jasa KOL {$kol}", $ref);

        BvCashflow::post($this, 'pembayaran_pajak', $gross - $netto, $date,
            "PPh/PPN atas pembayaran KOL {$kol}", $ref);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BvCampign::class, 'campaign_id');
    }

    public function kol(): BelongsTo
    {
        return $this->belongsTo(BvCampaignKol::class, 'bv_campaign_kol_id');
    }
}
