<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TOP (term of payment, dalam hari) untuk KOL.
 *
 * `data_clients` sudah punya kolom serupa; sisi KOL belum, padahal sheet KOL List
 * mengisinya per KOL. Nullable karena kebanyakan KOL memang belum ditentukan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->unsignedSmallInteger('top')->nullable()->after('tipe_pajak_kol');
        });
    }

    public function down(): void
    {
        Schema::table('data_kols', fn (Blueprint $table) => $table->dropColumn('top'));
    }
};
