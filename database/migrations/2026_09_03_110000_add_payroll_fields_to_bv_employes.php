<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data payroll dari form "List Data Karyawan": bank, nomor rekening, NPWP,
 * dan BPJS Kesehatan. Semuanya string — nomor rekening & BPJS punya angka nol
 * di depan yang hilang kalau disimpan sebagai integer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_employes', function (Blueprint $table) {
            $table->string('bank')->nullable()->after('kode_pos');
            $table->string('no_rekening')->nullable()->after('bank');
            $table->string('npwp')->nullable()->after('no_rekening');
            $table->string('bpjs_kesehatan')->nullable()->after('npwp');
        });
    }

    public function down(): void
    {
        Schema::table('bv_employes', function (Blueprint $table) {
            $table->dropColumn(['bank', 'no_rekening', 'npwp', 'bpjs_kesehatan']);
        });
    }
};
