<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Form "List Data Karyawan" tidak menanyakan nomor WhatsApp, jadi kolomnya
 * dibuat nullable. Index unique tetap dipertahankan — MySQL mengizinkan
 * banyak baris NULL pada kolom unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_employes', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bv_employes', function (Blueprint $table) {
            $table->string('whatsapp')->nullable(false)->change();
        });
    }
};
