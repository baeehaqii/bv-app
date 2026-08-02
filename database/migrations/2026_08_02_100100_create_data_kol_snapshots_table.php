<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat followers per channel — satu-satunya sumber grafik Follower Growth.
 *
 * ScrapeCreators tidak punya endpoint histori followers, jadi grafiknya HANYA
 * bisa dibangun dari catatan kita sendiri: tiap kali channel di-scrape/refresh,
 * angkanya dicatat di sini. Konsekuensinya grafik kosong sampai ada minimal dua
 * tanggal — itu wajar dan bukan bug.
 *
 * Unik per (channel, tanggal): refresh berkali-kali dalam sehari memperbarui
 * baris yang sama, tidak menumpuk.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_kol_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_kol_id')->constrained('data_kols')->cascadeOnDelete();
            $table->date('captured_on');
            $table->unsignedBigInteger('followers')->default(0);
            $table->decimal('engagement_rate', 8, 2)->default(0);
            $table->unsignedBigInteger('engagements')->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->timestamps();

            $table->unique(['data_kol_id', 'captured_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_kol_snapshots');
    }
};
