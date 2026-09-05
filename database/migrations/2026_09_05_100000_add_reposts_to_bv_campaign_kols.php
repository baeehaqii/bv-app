<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom "Repost" ada di tab Instagram sheet KOL Insights dan ikut dijumlahkan
 * ke Engagement di sana. Tanpa kolomnya, total engagement hasil migrasi selalu
 * kurang sebanyak jumlah repost (90 di file Ofero) dibanding tab Summary.
 *
 * Seperti `reach`, ini kolom yang hanya bisa diisi dari sheet: tak satu pun
 * platform mengembalikannya lewat scraping halaman publik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            $table->unsignedBigInteger('reposts')->default(0)->after('shares');
        });
    }

    public function down(): void
    {
        Schema::table('bv_campaign_kols', fn(Blueprint $table) => $table->dropColumn('reposts'));
    }
};
