<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Arus kas perusahaan, disajikan mengikuti Standar Akuntansi Keuangan.
 *
 * Kolom `category` menyimpan KODE pos akun (kunci mesin, lihat ACCOUNTS),
 * `activity` menyimpan klasifikasi arus kas PSAK 2. Keduanya diturunkan
 * otomatis dari pos akun saat menyimpan, jadi tidak bisa saling bertentangan.
 *
 * Baris ber-`source_type` = hasil auto-posting dari transaksi lain
 * (pembayaran KOL, pelunasan quotation) dan tidak boleh diubah manual —
 * jejak audit harus mengikuti dokumen sumbernya.
 */
class BvCashflow extends Model
{
    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
    ];

    /** Klasifikasi arus kas PSAK 2 "Laporan Arus Kas". */
    public const ACTIVITIES = [
        'operasi'   => 'Aktivitas Operasi',
        'investasi' => 'Aktivitas Investasi',
        'pendanaan' => 'Aktivitas Pendanaan',
    ];

    /**
     * Pos akun SAK: kode => [label, jenis kas, aktivitas].
     * Penamaan mengikuti pos penyajian laporan arus kas metode langsung.
     */
    public const ACCOUNTS = [
        // — Aktivitas operasi
        'penerimaan_dari_pelanggan' => ['Penerimaan dari Pelanggan', 'income', 'operasi'],
        'penerimaan_operasi_lain'   => ['Penerimaan Operasi Lain-lain', 'income', 'operasi'],
        'beban_pokok_pendapatan'    => ['Beban Pokok Pendapatan', 'expense', 'operasi'],
        'pembayaran_pajak'          => ['Pembayaran Pajak', 'expense', 'operasi'],
        'beban_gaji'                => ['Beban Gaji dan Tunjangan', 'expense', 'operasi'],
        'beban_pemasaran'           => ['Beban Pemasaran', 'expense', 'operasi'],
        'beban_umum_administrasi'   => ['Beban Umum dan Administrasi', 'expense', 'operasi'],
        // — Aktivitas investasi
        'pelepasan_aset_tetap'      => ['Pelepasan Aset Tetap', 'income', 'investasi'],
        'perolehan_aset_tetap'      => ['Perolehan Aset Tetap', 'expense', 'investasi'],
        // — Aktivitas pendanaan
        'setoran_modal'             => ['Setoran Modal', 'income', 'pendanaan'],
        'penerimaan_pinjaman'       => ['Penerimaan Pinjaman', 'income', 'pendanaan'],
        'pembayaran_pinjaman'       => ['Pembayaran Pinjaman', 'expense', 'pendanaan'],
        'pembagian_dividen'         => ['Pembagian Dividen / Prive', 'expense', 'pendanaan'],
    ];

    public const PAYMENT_METHODS = [
        'cash'     => 'Kas',
        'transfer' => 'Transfer Bank',
        'e-wallet' => 'E-Wallet',
    ];

    /** Jenis & aktivitas selalu ikut pos akun — tidak dipercaya dari input. */
    protected static function booted(): void
    {
        static::saving(function (self $row) {
            if ($meta = self::ACCOUNTS[$row->category] ?? null) {
                $row->type = $meta[1];
                $row->activity = $meta[2];
            }

            // Tanpa akun kas/bank, saldo tidak bisa dihitung — jatuhkan ke akun default.
            $row->finance_account_id ??= FinanceAccount::defaultId();
        });
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function dataClient(): BelongsTo
    {
        return $this->belongsTo(DataClient::class);
    }

    public function financeAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    // -------------------------------------------------------
    // Auto-posting
    // -------------------------------------------------------

    /**
     * Catat (atau perbarui) satu baris kas dari transaksi sumber.
     * Idempoten: dikunci per (sumber, pos akun) sehingga aman dipanggil ulang
     * setiap kali dokumen sumber disimpan. Nominal <= 0 berarti tidak ada arus
     * kas → barisnya dihapus, bukan disimpan sebagai nol.
     */
    public static function post(
        Model $source,
        string $account,
        float $amount,
        mixed $date,
        string $description,
        ?string $reference = null,
        ?int $dataClientId = null,
        string $paymentMethod = 'transfer',
        ?int $financeAccountId = null,
    ): ?self {
        if (! isset(self::ACCOUNTS[$account])) {
            throw new \InvalidArgumentException("Pos akun SAK tidak dikenal: {$account}");
        }

        $key = [
            'source_type' => $source->getMorphClass(),
            'source_id'   => $source->getKey(),
            'category'    => $account,
        ];

        if (round($amount, 2) <= 0) {
            static::query()->where($key)->delete();

            return null;
        }

        return static::query()->updateOrCreate($key, [
            'transaction_date' => $date,
            'amount'           => round($amount, 2),
            'description'      => $description,
            'reference_no'     => $reference,
            'data_client_id'   => $dataClientId,
            'payment_method'   => $paymentMethod,
            'finance_account_id' => $financeAccountId ?? FinanceAccount::defaultId(),
        ]);
    }

    /** Hapus baris kas milik satu sumber (semua pos, atau satu pos saja). */
    public static function unpost(Model $source, ?string $account = null): void
    {
        static::query()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->when($account, fn(Builder $q) => $q->where('category', $account))
            ->delete();
    }

    public function isAutoPosted(): bool
    {
        return filled($this->source_type);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /** Pos akun yang valid untuk satu jenis kas (income / expense). */
    public static function optionsFor(?string $type): array
    {
        return collect(self::ACCOUNTS)
            ->when($type, fn($accounts) => $accounts->filter(fn($meta) => $meta[1] === $type))
            ->map(fn($meta) => $meta[0])
            ->all();
    }

    public static function activityOf(?string $account): ?string
    {
        return self::ACCOUNTS[$account][2] ?? null;
    }

    public function getAccountLabelAttribute(): string
    {
        return self::ACCOUNTS[$this->category][0] ?? (string) $this->category;
    }

    public function getActivityLabelAttribute(): string
    {
        return self::ACTIVITIES[$this->activity] ?? (string) $this->activity;
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopePeriod(Builder $query, mixed $start, mixed $end): Builder
    {
        return $query->whereBetween('transaction_date', [$start, $end]);
    }
}
