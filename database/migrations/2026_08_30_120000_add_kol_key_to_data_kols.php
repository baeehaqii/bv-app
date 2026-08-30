<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `kol_key` = penanda "ini orang yang sama", pengganti pengelompokan lewat username.
 *
 * Sebelum ini satu KOL dikenali dari `username`, jadi orang yang handle-nya beda
 * tiap platform (@windabasudara_ di Instagram, @winda_basudara di TikTok) terbaca
 * sebagai dua KOL berbeda di seluruh sistem.
 *
 * Isi awalnya = username, jadi pengelompokan yang sudah berjalan tidak berubah
 * sama sekali; yang berubah cuma: sekarang bisa ditimpa lewat aksi "Gabungkan".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->string('kol_key')->nullable()->after('username')->index();
        });

        DB::table('data_kols')->whereNull('kol_key')->update(['kol_key' => DB::raw('username')]);
    }

    public function down(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->dropIndex(['kol_key']);
            $table->dropColumn('kol_key');
        });
    }
};
