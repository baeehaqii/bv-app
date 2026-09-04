<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterMargin extends Model
{
    protected $fillable = [
        'name',
        'min_amount',
        'max_amount',
        'margin_percent',
        'order',
        'is_active',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get margin percentage for a given subtotal amount
     */
    /**
     * Baris aktif di-cache per request: fungsi ini dipanggil sekali per baris
     * budget saat KOL List dirender — pada media plan besar itu ribuan query ke
     * tabel yang isinya cuma beberapa baris dan tidak berubah selama request.
     *
     * @var \Illuminate\Support\Collection<int, self>|null
     */
    private static ?\Illuminate\Support\Collection $aktif = null;

    protected static function booted(): void
    {
        // Tabelnya diedit lewat panel admin, dan test mengisinya di tengah jalan.
        // Tanpa ini cache-nya basi dan margin lama masih dipakai.
        static::saved(fn() => self::$aktif = null);
        static::deleted(fn() => self::$aktif = null);
    }

    /** Mass-delete/mass-update tidak memicu event model, jadi cache dilepas manual. */
    public static function forgetCached(): void
    {
        self::$aktif = null;
    }

    public static function getMarginForAmount(float $subtotal): float
    {
        self::$aktif ??= self::query()->active()->ordered()->get();

        $margin = self::$aktif->first(fn(self $m) => (float) $m->min_amount <= $subtotal
            && ($m->max_amount === null || (float) $m->max_amount >= $subtotal));

        // Jatuhan kalau tak ada baris yang cocok: diatur di
        // "Masterdata Media Plan Internal" (bawaan 50% = kolom AA Z / 0.5).
        return $margin
            ? (float) $margin->margin_percent
            : (float) MediaPlanCalcSetting::current()->default_margin_percent;
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
        return $query->orderBy('order')->orderBy('min_amount');
    }
}

