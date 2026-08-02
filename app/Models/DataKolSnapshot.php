<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu titik pada grafik Follower Growth: kondisi channel pada satu tanggal.
 * Diisi otomatis tiap channel di-scrape (lihat KolProfileImporter::save()).
 */
class DataKolSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'captured_on' => 'date',
        'engagement_rate' => 'decimal:2',
    ];

    public function dataKol(): BelongsTo
    {
        return $this->belongsTo(DataKol::class);
    }
}
