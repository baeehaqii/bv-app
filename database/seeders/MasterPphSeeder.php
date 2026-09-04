<?php

namespace Database\Seeders;

use App\Models\MasterPph;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterPphSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pphs = [
            [
                'name' => 'Pribadi',
                'entity_type' => 'Pribadi',
                'coefficient' => 0.975,
                'include_ppn' => false,
                'ppn_percent' => null,
                'description' => 'Coefficient for individual/personal tax',
                'order' => 1,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'PT Non PKP',
                'entity_type' => 'PT',
                'coefficient' => 0.98,
                'include_ppn' => false,
                'ppn_percent' => null,
                'description' => 'PT (Perseroan Terbatas) - Non Pengusaha Kena Pajak',
                'order' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'PT PKP',
                'entity_type' => 'PT',
                'coefficient' => 0.98,
                'include_ppn' => true,
                'ppn_percent' => 11.00,
                'description' => 'PT (Perseroan Terbatas) - Pengusaha Kena Pajak + PPN 11%',
                'order' => 3,
                // Sheet KOL List pakai gross-up 0.98 + PPN 11% di SEMUA baris.
                // Bisa dipindah ke tipe lain lewat toggle "Default" di Master PPH.
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'CV',
                'entity_type' => 'CV',
                'coefficient' => 0.995,
                'include_ppn' => false,
                'ppn_percent' => null,
                'description' => 'CV (Commanditaire Vennootschap)',
                'order' => 4,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($pphs as $pph) {
            MasterPph::updateOrCreate(
                ['name' => $pph['name']],
                $pph
            );
        }
    }
}

