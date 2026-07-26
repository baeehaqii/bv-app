<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat versi konten storyline. revision_number 0 = kiriman awal,
 * 1..3 = perbaikan setelah client minta revisi (maks 3x revisi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_storyline_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_storyline_id')
                ->constrained('campaign_storylines')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('revision_number')->default(0);
            $table->json('images')->nullable();
            $table->string('content_link')->nullable();
            $table->text('caption_draft')->nullable();
            $table->text('notes')->nullable(); // catatan tim KOL untuk versi ini
            $table->timestamp('submitted_at')->nullable();
            $table->string('client_choice')->nullable(); // approved | revision
            $table->text('client_feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Nama index dipendekkan: auto-generate MySQL melebihi 64 karakter.
            $table->unique(['campaign_storyline_id', 'revision_number'], 'storyline_revision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_storyline_contents');
    }
};
