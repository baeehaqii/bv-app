<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BvSPK extends Model
{
    protected $table = 'bv_s_p_k_s';

    protected $fillable = [
        'spk_number',
        'tanggal_perjanjian',
        'client_id',
        'form_brief_id',
        'pihak_kedua_nama_lengkap',
        'pihak_kedua_nama_akun',
        'pihak_kedua_nik',
        'pihak_kedua_alamat',
        'nama_campaign',
        'sow_disepakati',
        'timeline_kerja_sama',
        'nominal_kesepakatan',
        'nominal_terbilang',
        'atas_nama_rekening',
        'nomor_rekening',
        'nama_bank',
        'kantor_cabang_bank',
        'termin_pembayaran_1',
        'termin_pembayaran_2',
        'status',
        'notes',
    ];

    protected $casts = [
        'tanggal_perjanjian' => 'date',
        'nominal_kesepakatan' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(DataClient::class, 'client_id');
    }

    public function formBrief(): BelongsTo
    {
        return $this->belongsTo(FormBrief::class, 'form_brief_id');
    }

    public function getFormattedNominalAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->nominal_kesepakatan, 0, ',', '.');
    }
}
