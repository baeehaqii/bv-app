<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda baris budget yang angkanya datang dari spreadsheet.
 *
 * Baris bertanda ini TIDAK dihitung ulang recalculate(): isinya catatan sejarah,
 * apa adanya seperti di sheet. Begitu disunting lewat sistem, penandanya dilepas
 * dan baris itu kembali mengikuti hitungan sistem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            $table->timestamp('imported_at')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('internal_budget_items', fn (Blueprint $table) => $table->dropColumn('imported_at'));
    }
};
