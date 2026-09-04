<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tier brief KOL dulu disimpan huruf kecil ('mega') dengan daftar sendiri,
 * sementara DataKol & scraping menulis 'Mega'. Sekarang keduanya memakai label
 * dari master data Tier KOL, jadi baris lama diseragamkan.
 */
return new class extends Migration
{
    private const PETA = [
        'mega' => 'Mega',
        'macro' => 'Macro',
        'micro' => 'Micro',
        'nano' => 'Nano',
        'mini' => 'Mini',
        'celebrity' => 'Celebrity',
        'mid' => 'Macro', // band khas ThreadsService yang tidak dikenal modul lain
    ];

    public function up(): void
    {
        foreach (self::PETA as $lama => $baru) {
            DB::table('bv_campaign_kols')->where('tier', $lama)->update(['tier' => $baru]);
        }
    }

    public function down(): void
    {
        foreach (['Mega' => 'mega', 'Macro' => 'macro', 'Micro' => 'micro', 'Nano' => 'nano'] as $baru => $lama) {
            DB::table('bv_campaign_kols')->where('tier', $baru)->update(['tier' => $lama]);
        }
    }
};
