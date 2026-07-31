<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Invoice / piutang usaha (AR).
 *
 * Satu invoice = satu tagihan. Penerimaan kasnya di-posting ke arus kas hanya
 * saat invoice dibayar (SAK: kas dicatat saat diterima), pada pos
 * "Penerimaan dari Pelanggan".
 *
 * ponytail: pembayaran dicatat sekali per invoice (boleh kurang dari tagihan).
 * Termin/DP dibuat sebagai invoice terpisah. Kalau nanti butuh banyak cicilan
 * dalam SATU invoice, upgrade-nya tabel `invoice_payments` + posting per cicilan.
 */
class BvInvoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issue_date'  => 'date',
        'due_date'    => 'date',
        'paid_at'     => 'date',
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public const STATUSES = [
        'draft'          => 'Draft',
        'sent'           => 'Terkirim',
        'partially_paid' => 'Kurang Bayar',
        'paid'           => 'Lunas',
        'void'           => 'Dibatalkan',
    ];

    /** Status yang masih menghasilkan piutang. */
    public const OUTSTANDING_STATUSES = ['draft', 'sent', 'partially_paid'];

    protected static function booted(): void
    {
        static::saved(fn(self $invoice) => $invoice->syncCashflow());
        static::deleted(fn(self $invoice) => BvCashflow::unpost($invoice));
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(BvQuotation::class, 'bv_quotation_id');
    }

    public function dataClient(): BelongsTo
    {
        return $this->belongsTo(DataClient::class);
    }

    public function financeAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    // -------------------------------------------------------
    // Pembuatan
    // -------------------------------------------------------

    /**
     * Nomor invoice berjalan per bulan: BV/INV/2607/001.
     * ponytail: hitung-max sederhana, bukan sequence tabel — cukup untuk volume
     * puluhan invoice/bulan dengan satu operator finance. Kalau nanti ada
     * pembuatan invoice paralel, ganti ke tabel counter atau kunci baris.
     */
    public static function generateNumber(?Carbon $date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();
        $prefix = 'BV/INV/' . $date->format('ym') . '/';

        $last = static::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $next = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /** Terbitkan invoice dari quotation yang sudah ditandatangani lengkap. */
    public static function createFromQuotation(
        BvQuotation $quotation,
        float $amount,
        ?string $termLabel = null,
        mixed $dueDate = null,
        mixed $issueDate = null,
    ): self {
        if (! $quotation->isFullySigned()) {
            throw new \RuntimeException('Quotation belum ditandatangani lengkap — invoice belum bisa diterbitkan.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nilai invoice harus lebih dari nol.');
        }

        $issue = $issueDate ? Carbon::parse($issueDate) : now();

        return static::create([
            'invoice_number'   => static::generateNumber($issue),
            'bv_quotation_id'  => $quotation->id,
            'data_client_id'   => $quotation->dataClientId(),
            'client_name'      => $quotation->client_name,
            'term_label'       => $termLabel,
            'amount'           => $amount,
            'issue_date'       => $issue->toDateString(),
            'due_date'         => $dueDate ? Carbon::parse($dueDate)->toDateString() : $issue->copy()->addDays(14)->toDateString(),
            'status'           => 'sent',
        ]);
    }

    // -------------------------------------------------------
    // Pembayaran & piutang
    // -------------------------------------------------------

    public function getOutstandingAttribute(): float
    {
        if ($this->status === 'void') {
            return 0.0;
        }

        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /** Lewat jatuh tempo dan masih ada sisa tagihan. */
    public function isOverdue(): bool
    {
        return in_array($this->status, self::OUTSTANDING_STATUSES, true)
            && $this->due_date?->isPast()
            && $this->outstanding > 0;
    }

    /**
     * Catat penerimaan pembayaran. Boleh kurang dari nilai tagihan → status
     * jadi "Kurang Bayar" dan sisanya tetap tercatat sebagai piutang.
     */
    public function markPaid(float $amount, mixed $date = null, ?int $financeAccountId = null): void
    {
        if ($this->status === 'void') {
            throw new \RuntimeException('Invoice sudah dibatalkan.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal pembayaran harus lebih dari nol.');
        }

        if ($amount > (float) $this->amount) {
            throw new \InvalidArgumentException('Nominal pembayaran melebihi nilai invoice.');
        }

        $this->update([
            'paid_amount'        => $amount,
            'paid_at'            => $date ? Carbon::parse($date)->toDateString() : now()->toDateString(),
            'finance_account_id' => $financeAccountId ?? $this->finance_account_id ?? FinanceAccount::defaultId(),
            'status'             => $amount >= (float) $this->amount ? 'paid' : 'partially_paid',
        ]);
    }

    /** Batalkan catatan pembayaran (salah input) — baris kasnya ikut hilang. */
    public function unmarkPaid(): void
    {
        $this->update([
            'paid_amount' => null,
            'paid_at'     => null,
            'status'      => 'sent',
        ]);
    }

    /** Batalkan invoice: piutang nol, penerimaan kas ditarik kembali. */
    public function void(): void
    {
        $this->update([
            'status'      => 'void',
            'paid_amount' => null,
            'paid_at'     => null,
        ]);
    }

    /** Auto-posting penerimaan kas — hanya untuk uang yang benar-benar diterima. */
    public function syncCashflow(): void
    {
        if ($this->status === 'void' || (float) $this->paid_amount <= 0 || blank($this->paid_at)) {
            BvCashflow::unpost($this);

            return;
        }

        BvCashflow::post(
            source: $this,
            account: 'penerimaan_dari_pelanggan',
            amount: (float) $this->paid_amount,
            date: $this->paid_at,
            description: trim("Pembayaran invoice {$this->invoice_number} — {$this->client_name} " . ($this->term_label ? "({$this->term_label})" : '')),
            reference: $this->invoice_number,
            dataClientId: $this->data_client_id,
            financeAccountId: $this->finance_account_id,
        );
    }

    // -------------------------------------------------------
    // Scopes & agregat
    // -------------------------------------------------------

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', self::OUTSTANDING_STATUSES);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->outstanding()->whereDate('due_date', '<', now()->toDateString());
    }

    /** Total piutang usaha (AR) yang belum tertagih. */
    public static function totalReceivable(): float
    {
        return (float) static::query()->outstanding()
            ->selectRaw('COALESCE(SUM(amount - COALESCE(paid_amount, 0)), 0) as total')
            ->value('total');
    }
}
