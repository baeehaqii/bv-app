<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda baris KOL yang datang dari migrasi spreadsheet.
 *
 * Sheet lama sering belum mengisi kolom Rate, jadi baris hasil migrasi boleh
 * ber-rate 0 dan dilengkapi manual belakangan. Baris yang diinput lewat form
 * tetap dijaga guardKolRateCards() seperti biasa — tanpa rate card, budget yang
 * tergenerate diam-diam salah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_plan_kols', function (Blueprint $table) {
            $table->timestamp('imported_at')->nullable()->after('row_number');
        });
    }

    public function down(): void
    {
        Schema::table('media_plan_kols', fn (Blueprint $table) => $table->dropColumn('imported_at'));
    }
};
