<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Akun kas / bank / e-wallet. Saldo tidak disimpan sebagai kolom — selalu
 * dihitung dari saldo awal + arus kas, supaya tidak pernah basi terhadap
 * koreksi transaksi lama.
 */
class FinanceAccount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_date'    => 'date',
        'is_default'      => 'boolean',
        'is_active'       => 'boolean',
    ];

    public const TYPES = [
        'kas'      => 'Kas',
        'bank'     => 'Bank',
        'e-wallet' => 'E-Wallet',
    ];

    /** Hanya boleh ada satu akun default. */
    protected static function booted(): void
    {
        static::saved(function (self $account) {
            if ($account->is_default) {
                static::query()->whereKeyNot($account->getKey())->update(['is_default' => false]);
            }
        });
    }

    public function cashflows(): HasMany
    {
        return $this->hasMany(BvCashflow::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BvInvoice::class);
    }

    /** Saldo berjalan = saldo awal + penerimaan − pengeluaran. */
    public function getBalanceAttribute(): float
    {
        return (float) $this->opening_balance
            + (float) $this->cashflows()->income()->sum('amount')
            - (float) $this->cashflows()->expense()->sum('amount');
    }

    /** Akun tujuan default untuk baris auto-posting yang tidak menyebut akun. */
    public static function defaultId(): ?int
    {
        return static::query()->where('is_default', true)->value('id')
            ?? static::query()->where('is_active', true)->orderBy('id')->value('id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? (string) $this->type;
    }
}
