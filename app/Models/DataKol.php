<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataKol extends Model
{
    protected $guarded = [];

    protected $casts = [
        'category' => 'array',
        'terakhir_update' => 'date',
    ];

    /**
     * Tabel ini menyimpan 1 BARIS PER CHANNEL; satu KOL dikenali dari `username`.
     * Relasi self-join ini yang menyatukan channel-channel milik KOL yang sama,
     * dan bisa di-eager-load / di-agregat (withSum, withMax) supaya daftar KOL
     * tidak N+1.
     */
    public function channels(): HasMany
    {
        return $this->hasMany(static::class, 'username', 'username');
    }

    /**
     * Satu baris per KOL untuk halaman daftar. Wakilnya = channel dengan followers
     * TERBANYAK (itu yang paling masuk akal dibuka saat klik Detail), seri dipecah
     * oleh id terbesar. Angka lintas channel diambil dari relasi `channels`.
     *
     * Subquery berkorelasi, bukan window function: harus jalan di MySQL (produksi)
     * maupun SQLite (test).
     */
    public function scopeOneRowPerKol(Builder $query): Builder
    {
        return $query->whereIn('id', static::query()
            ->selectRaw('MAX(id)')
            ->whereRaw('COALESCE(followers, 0) = (
                SELECT MAX(COALESCE(followers, 0)) FROM data_kols AS terbanyak
                WHERE terbanyak.username = data_kols.username
            )')
            ->groupBy('username'));
    }

    /**
     * Ambang tier resmi [min, max] followers (max null = tanpa batas atas), disalin
     * dari calculateTier() di service scraping — Instagram/Tiktok/Youtube pakai
     * angka yang sama. Urut menurun: tierFor() ambil kecocokan pertama.
     */
    public const TIER_RANGES = [
        'Mega' => [1_000_000, null],
        'Macro' => [100_000, 999_999],
        'Micro' => [10_000, 99_999],
        'Nano' => [1_000, 9_999],
        'Mini' => [0, 999],
    ];

    /** Tier dari total followers gabungan semua channel. */
    public static function tierFor(int $followers): string
    {
        foreach (self::TIER_RANGES as $tier => [$min, $max]) {
            if ($followers >= $min) {
                return $tier;
            }
        }

        return 'Mini';
    }

    /** Semua baris channel milik KOL ini, termasuk baris ini sendiri. */
    public function channelSiblings()
    {
        return $this->channels()->with('rateCards')->orderBy('channel')->get();
    }

    public function rateCards(): HasMany
    {
        return $this->hasMany(KolRateCard::class)->orderByDesc('valid_from');
    }

    /** Riwayat SPK/PKS KOL ini — campaign apa saja yang pernah dia tanda tangani. */
    public function spks(): HasMany
    {
        return $this->hasMany(BvSPK::class, 'data_kol_id')->latest('tanggal_perjanjian');
    }

    public function tipePajakKol(): BelongsTo
    {
        return $this->belongsTo(MasterPph::class, 'tipe_pajak_kol');
    }
}
