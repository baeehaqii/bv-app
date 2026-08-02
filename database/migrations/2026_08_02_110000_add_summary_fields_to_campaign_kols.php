<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil analisis komentar per postingan KOL, untuk Campaign Summary.
 *
 * Disimpan per postingan (bukan per campaign) supaya bisa diambil ulang satu-satu
 * dan agregat campaign tinggal menjumlahkan. Komentar mentahnya ikut disimpan
 * karena leksikon sentimen bisa berubah — kalau tim menambah kata di
 * config/sentiment.php, analisis bisa dihitung ulang TANPA membayar API lagi.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            $table->json('comments_data')->nullable()->after('feedback_2');
            $table->timestamp('comments_fetched_at')->nullable()->after('comments_data');
        });
    }

    public function down(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            $table->dropColumn(['comments_data', 'comments_fetched_at']);
        });
    }
};
