<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil AI disimpan, bukan dihitung ulang tiap halaman dibuka: tiap panggilan
 * memakan kredit API, dan angkanya baru berubah kalau performa di-refresh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_campaigns', function (Blueprint $table) {
            $table->text('ai_summary')->nullable();
            $table->timestamp('ai_summary_at')->nullable();
        });

        Schema::table('data_kols', function (Blueprint $table) {
            $table->text('ai_insight')->nullable();
            $table->timestamp('ai_insight_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bv_campaigns', fn (Blueprint $table) => $table->dropColumn(['ai_summary', 'ai_summary_at']));
        Schema::table('data_kols', fn (Blueprint $table) => $table->dropColumn(['ai_insight', 'ai_insight_at']));
    }
};
