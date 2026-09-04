<?php

namespace Database\Seeders;

use App\Models\MediaPlanCalcSetting;
use Illuminate\Database\Seeder;

/**
 * Nilai awal rumus Media Plan Internal = sheet `[INT] ... - KOL List.xlsx`.
 * Setelah ini semuanya diubah lewat halaman "Masterdata Media Plan Internal";
 * seeder sengaja TIDAK menimpa baris yang sudah ada agar setelan admin aman.
 */
class MediaPlanCalcSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (MediaPlanCalcSetting::query()->exists()) {
            return;
        }

        MediaPlanCalcSetting::create([
            'rounding_step' => 100000,      // kolom AC: ROUNDUP(AB, -5)
            'rounding_mode' => 'up',
            'default_margin_percent' => 50, // kolom AA: Z / 0.5
            'max_margin_percent' => 99,
            'tier_thresholds' => MediaPlanCalcSetting::DEFAULT_TIERS, // kolom I
        ]);
    }
}
