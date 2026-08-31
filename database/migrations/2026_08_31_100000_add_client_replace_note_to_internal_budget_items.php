<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usulan KOL pengganti dari client di halaman Link Review.
 *
 * Ditaruh sebaris dengan client_choice & client_feedback — sama-sama masukan
 * client, sama-sama ditulis seragam ke seluruh SOW milik satu KOL. Ini usulan,
 * bukan penggantian: yang benar-benar mengganti KOL tetap aksi "Ganti KOL" di
 * Media Plan External, dijalankan BV setelah usulannya dibaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            $table->text('client_replace_note')->nullable()->after('client_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            $table->dropColumn('client_replace_note');
        });
    }
};
