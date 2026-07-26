<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konten yang dibuat tim KOL internal untuk preview client:
 * gambar (maks 10) + link video/konten pada storyline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_storylines', function (Blueprint $table) {
            $table->json('images')->nullable()->after('caption_draft');
            $table->string('content_link')->nullable()->after('images');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_storylines', function (Blueprint $table) {
            $table->dropColumn(['images', 'content_link']);
        });
    }
};
