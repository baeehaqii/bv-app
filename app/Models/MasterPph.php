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
    ];

    protected $casts = [
        'coefficient' => 'decimal:3',
        'ppn_percent' => 'decimal:2',
        'include_ppn' => 'boolean',
        'is_active' => 'boolean',
    ];

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

