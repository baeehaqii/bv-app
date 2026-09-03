<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan supaya bentuknya sama dengan template OKR Confluence:
 * Partner with, Expected EoQ key result score, dan Current status per bulan.
 *
 * results dibuang, bukan dibiarkan menganggur. Isinya persis yang sekarang
 * ditampung status_month_1..3 — bedanya yang baru memaksa perkembangan ditulis
 * per bulan, dan itu yang membuat perbedaan "sudah sejauh mana" kelihatan.
 * Tabelnya masih kosong, jadi tidak ada isi yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('okrs', function (Blueprint $table) {
            $table->string('partner_with')->nullable()->after('key_results');

            // 0.0-1.0 seperti template — dua digit sudah cukup, dan decimal
            // menahan "80%" yang tidak bisa dibandingkan dengan 0.8.
            $table->decimal('expected_score', 2, 1)->nullable()->after('partner_with');
            $table->decimal('objective_score', 2, 1)->nullable()->after('expected_score');

            $table->text('status_month_1')->nullable()->after('objective_score');
            $table->text('status_month_2')->nullable()->after('status_month_1');
            $table->text('status_month_3')->nullable()->after('status_month_2');

            $table->dropColumn('results');
        });
    }

    public function down(): void
    {
        Schema::table('okrs', function (Blueprint $table) {
            $table->text('results')->nullable();

            $table->dropColumn([
                'partner_with',
                'expected_score',
                'objective_score',
                'status_month_1',
                'status_month_2',
                'status_month_3',
            ]);
        });
    }
};
