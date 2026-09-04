<?php

namespace Database\Seeders;

use App\Models\MasterMargin;
use Illuminate\Database\Seeder;

class MasterMarginSeeder extends Seeder
{
    /**
     * Acuan: sheet `[INT] ... - KOL List.xlsx`, kolom AA = `Z / 0.5` di SEMUA baris
     * (Nano/Micro/Macro/Homeless Media, 197 baris, tanpa kecuali) — margin target
     * flat 50%, bukan bertingkat per nominal.
     *
     * Tabel bertingkat lama (80/40/30) tidak pernah benar-benar jalan: baris
     * "Default (sheet KOL List)" (min 0, max null) dari migration selalu menang di
     * `getMarginForAmount()`. Baris itu dibuang di sini supaya master data yang
     * kelihatan di panel admin = master data yang dipakai menghitung.
     */
    public function run(): void
    {
        MasterMargin::whereIn('name', [
            'Default (sheet KOL List)',
            'Low Budget',
            'Medium Budget',
            'High Budget',
        ])->delete();

        MasterMargin::updateOrCreate(
            ['name' => 'Default (sheet KOL List)'],
            [
                'min_amount' => 0,
                'max_amount' => null, // semua nominal
                'margin_percent' => 50.00,
                'order' => 1,
                'is_active' => true,
            ]
        );
    }
}
