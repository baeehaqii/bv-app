<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retrieve History — kondisi tiap postingan KOL pada satu tanggal.
 *
 * Hanya angka MENTAH yang disimpan. CPE/CPV/CPM/ER/VTR sengaja tidak dikolomkan:
 * semuanya turunan murni dari angka di sini, dan menyimpan turunan berarti
 * riwayat lama ikut salah begitu rumusnya diperbaiki.
 *
 * Satu baris per (postingan, tanggal) — fetch berkali-kali sehari memperbarui.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_kol_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bv_campaign_kol_id')->constrained('bv_campaign_kols')->cascadeOnDelete();
            $table->date('captured_on');

            $table->unsignedBigInteger('followers')->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('saves')->default(0);
            $table->unsignedBigInteger('engagement')->default(0);

            $table->timestamps();

            $table->unique(['bv_campaign_kol_id', 'captured_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_kol_snapshots');
    }
};
