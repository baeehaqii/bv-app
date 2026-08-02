<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris Retrieve History: kondisi sebuah postingan KOL pada satu tanggal.
 * Turunan (CPE/CPV/CPM/ER/VTR) dihitung di sini, bukan disimpan — lihat migrasinya.
 */
class CampaignKolSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'captured_on' => 'date',
        'cost' => 'decimal:2',
    ];

    public function kol(): BelongsTo
    {
        return $this->belongsTo(BvCampaignKol::class, 'bv_campaign_kol_id');
    }

    /** Cost per Engagement. */
    public function cpe(): float
    {
        return $this->engagement > 0 ? round((float) $this->cost / $this->engagement, 2) : 0.0;
    }

    /** Cost per View. */
    public function cpv(): float
    {
        return $this->views > 0 ? round((float) $this->cost / $this->views, 2) : 0.0;
    }

    /** Cost per Mille — biaya per 1.000 views. */
    public function cpm(): float
    {
        return $this->views > 0 ? round((float) $this->cost / $this->views * 1000, 2) : 0.0;
    }

    /** ER dihitung dari views bila ada (konten video), selain itu dari followers. */
    public function engagementRate(): float
    {
        $basis = $this->views > 0 ? $this->views : $this->followers;

        return $basis > 0 ? round($this->engagement / $basis * 100, 2) : 0.0;
    }

    /** View-Through Rate: views dibanding followers. Bisa >100% kalau tembus FYP. */
    public function vtr(): float
    {
        return $this->followers > 0 ? round($this->views / $this->followers * 100, 2) : 0.0;
    }
}
