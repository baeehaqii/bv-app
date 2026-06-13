<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel revisi konten dinamis untuk Campaign Ongoing Internal (acuan: sheet "Tracker").
 *
 * Menggantikan kolom fixed bv_campaign_kols.feedback / revision_link / feedback_2 yang
 * hanya menampung 2 ronde. Satu baris = satu ronde revisi pada satu tahap
 * (storyline | video | caption) untuk satu KOL — tak terbatas jumlah ronde + "Final Revisi".
 *
 * Dua FK: bv_campaign_id (agar bisa jadi RelationManager di halaman campaign) +
 * bv_campaign_kol_id (nullable, tautan ke baris KOL Performance).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_kol_revisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bv_campaign_id')
                ->constrained('bv_campaigns')
                ->cascadeOnDelete();

            $table->foreignId('bv_campaign_kol_id')
                ->nullable()
                ->constrained('bv_campaign_kols')
                ->nullOnDelete();

            $table->string('kol_name');                       // denormalisasi utk tampilan
            $table->string('stage')->default('video');        // storyline | video | caption
            $table->unsignedInteger('round')->default(1);     // 1, 2, 3, ...
            $table->string('asset_link')->nullable();         // link Google Docs/Drive draft revisi
            $table->text('asset_text')->nullable();           // isi storyline/caption bila teks
            $table->text('client_feedback')->nullable();      // feedback client utk ronde ini
            $table->boolean('is_final')->default(false);      // tandai "Final Revisi"
            $table->string('status')->default('waiting_review'); // waiting_review | approved | revision
            $table->timestamp('submitted_at')->nullable();    // saat client submit keputusan

            $table->timestamps();

            $table->index(['bv_campaign_id', 'stage', 'round']);
            $table->index('bv_campaign_kol_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_kol_revisions');
    }
};
