<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Part C — Campaign On Going Internal (reuse BvCampign).
 *
 * bv_campaigns        : token publik TERPISAH untuk Link Approval Konten
 *                       (berbeda dari public_token yang dipakai halaman performance external)
 * campaign_storylines : keputusan & feedback client atas draft konten
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bv_campaigns', function (Blueprint $table) {
            $table->string('content_review_token', 64)->nullable()->unique()->after('public_token');
            $table->boolean('content_review_is_public')->default(false)->after('content_review_token');
            $table->timestamp('content_review_submitted_at')->nullable()->after('content_review_is_public');
        });

        Schema::table('campaign_storylines', function (Blueprint $table) {
            // Keputusan client atas draft: 'approved' (lanjut) | 'revision' (perlu revisi) | null
            $table->string('client_choice', 16)->nullable()->after('notes');
            $table->text('client_feedback')->nullable()->after('client_choice');
        });
    }

    public function down(): void
    {
        Schema::table('bv_campaigns', function (Blueprint $table) {
            $table->dropColumn(['content_review_token', 'content_review_is_public', 'content_review_submitted_at']);
        });

        Schema::table('campaign_storylines', function (Blueprint $table) {
            $table->dropColumn(['client_choice', 'client_feedback']);
        });
    }
};
