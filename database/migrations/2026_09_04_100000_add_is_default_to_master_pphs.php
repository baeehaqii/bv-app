<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipe pajak default dulu ditulis langsung di kode (`where('name', 'PT PKP')`)
 * di 4 tempat. Sekarang jadi kolom supaya bisa diganti dari UI Master PPH.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_pphs', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_active');
        });

        // Baris yang selama ini jadi default de-facto: koefisien 0.98 + PPN
        // (kolom X & Y sheet KOL List). Kalau tak ada, ambil order terkecil.
        $id = DB::table('master_pphs')->where('include_ppn', true)->orderBy('order')->value('id')
            ?? DB::table('master_pphs')->where('is_active', true)->orderBy('order')->value('id');

        if ($id) {
            DB::table('master_pphs')->where('id', $id)->update(['is_default' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('master_pphs', fn(Blueprint $table) => $table->dropColumn('is_default'));
    }
};
