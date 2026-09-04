<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPph extends Model
{
    protected $fillable = [
        'name',
        'entity_type',
        'coefficient',
        'include_ppn',
        'ppn_percent',
        'description',
        'order',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'coefficient' => 'decimal:3',
        'ppn_percent' => 'decimal:2',
        'include_ppn' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Tipe pajak default untuk KOL baru — diatur lewat toggle "Default" di
     * Master PPH, bukan ditulis di kode. Kalau tak ada yang ditandai, jatuh ke
     * baris aktif dengan order terkecil.
     */
    private static ?self $defaultRow = null;

    private static bool $defaultResolved = false;

    protected static function booted(): void
    {
        // Default itu tunggal: menandai satu baris otomatis melepas yang lain.
        static::saved(function (self $pph) {
            if ($pph->is_default) {
                static::query()->whereKeyNot($pph->getKey())->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            static::forgetCachedDefault();
        });

        static::deleted(fn() => static::forgetCachedDefault());
    }

    public static function forgetCachedDefault(): void
    {
        self::$defaultRow = null;
        self::$defaultResolved = false;
    }

    public static function defaultRow(): ?self
    {
        if (! self::$defaultResolved) {
            self::$defaultRow = static::query()->active()->where('is_default', true)->first()
                ?? static::query()->active()->ordered()->first();
            self::$defaultResolved = true;
        }

        return self::$defaultRow;
    }

    public static function defaultId(): ?int
    {
        return self::defaultRow()?->id;
    }

    /**
     * Pembagi MU PPh milik tipe default. 1.0 (tanpa gross-up) kalau master PPh
     * benar-benar kosong — lebih jujur daripada diam-diam memakai angka ajaib.
     */
    public static function defaultCalculatedCoefficient(): float
    {
        return self::defaultRow()?->getCalculatedCoefficient() ?? 1.0;
    }

    /** Koefisien MENTAH tipe default (kolom X sheet KOL List), untuk PDF. */
    public static function defaultCoefficient(): float
    {
        return (float) (self::defaultRow()?->coefficient ?? 1.0);
    }

    /**
     * Pembagi tunggal untuk menghitung MU PPh (real cost): `subtotal / coefficient`.
     *
     * PPN MENAMBAH uang yang keluar, bukan mengurangi. Rumus resminya (sheet KOL
     * List client, kolom Z; sama dengan InternalBudgetItem::calculateMuPph()):
     *
     *   PT PKP : (subtotal / 0.98) + (subtotal * 11%)
     *   lainnya: subtotal / koefisien
     *
     * Baris PPN dikembalikan sebagai pembagi ekuivalen supaya semua pemanggil
     * tetap memakai satu bentuk `subtotal / coefficient`.
     */
    public function getCalculatedCoefficient(): float
    {
        $coefficient = (float) $this->coefficient;

        if ($this->include_ppn && $this->ppn_percent) {
            // 1 / (1/0.98 + 0.11) = 0.884637 → subtotal / 0.884637 = subtotal * 1.130408
            return 1 / (1 / $coefficient + (float) $this->ppn_percent / 100);
        }

        return $coefficient;
    }

    /**
     * Get active PPH options for dropdown
     */
    public static function getActiveOptions(): array
    {
        return self::where('is_active', true)
            ->orderBy('order')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Scope: Active only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }
}

