<?php

namespace App\Models;

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
     * Semua baris channel milik KOL yang sama (1 baris = 1 channel, dikelompokkan by username),
     * termasuk baris ini sendiri.
     */
    public function channelSiblings()
    {
        return static::with('rateCards')
            ->where('username', $this->username)
            ->orderBy('channel')
            ->get();
    }

    public function rateCards(): HasMany
    {
        return $this->hasMany(KolRateCard::class)->orderByDesc('valid_from');
    }

    public function tipePajakKol(): BelongsTo
    {
        return $this->belongsTo(MasterPph::class, 'tipe_pajak_kol');
    }
}
