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
        static::updating(function (self $payment) {
            // Sekali PAID, arus kasnya sudah diposting → status dikunci supaya
            // posting kas tidak bisa dicabut diam-diam lewat form.
            // Pembatalan sungguhan: jurnal koreksi di modul Cashflow, atau
            // forceFill()->saveQuietly() dari tinker kalau memang salah input.
            if ($payment->getOriginal('payment_status') === 'paid') {
                $payment->payment_status = 'paid';
            }
        });

        static::saved(fn(self $payment) => $payment->syncCashflow());
        static::deleted(fn(self $payment) => BvCashflow::unpost($payment));
    }

    /**
     * Isi form edit: nilai tersimpan, dengan field yang MASIH KOSONG diambil dari
     * Database KOL. Baris lama (dibuat sebelum autofill ada) jadi ikut terisi begitu
     * dibuka — user tidak perlu klik "Sync dari KOL" dulu, dan tidak perlu mengetik
     * ulang data yang sudah ada di Database KOL.
     */
    public function formDefaults(): array
    {
        $data = $this->attributesToArray();
        $profile = $this->campaign?->resolveKolProfileMap()[$this->kol_name] ?? [];

        foreach ($profile as $field => $value) {
            if (blank($data[$field] ?? null) && filled($value)) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    /** Status sudah terkunci (sudah PAID & terposting ke arus kas)? */
    public function isPaymentLocked(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * SPK KOL ini dari modul SPK KOL. Tidak disimpan sebagai FK: SPK biasanya
     * dibuat SETELAH baris pembayaran ada, jadi FK hasil sync gampang basi.
     * Dicocokkan lewat kunci unik SPK sendiri: (internal_budget_id, media_plan_kol_id).
     */
    public function resolveSpk(): ?BvSPK
    {
        return once(function () {
            $budgetId = $this->campaign?->mediaPlan?->internalBudget?->id;

            if (! $budgetId) {
                return null;
            }

            return BvSPK::where('internal_budget_id', $budgetId)
                ->whereHas('mediaPlanKol', fn($q) => $q->where('name', $this->kol_name))
                ->first();
        });
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
