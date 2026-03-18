<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bv_s_p_k_s', function (Blueprint $table) {
            $table->id();

            $table->string('spk_number')->unique();
            $table->date('tanggal_perjanjian')->nullable();

            $table->foreignId('client_id')->nullable()->constrained('data_clients')->nullOnDelete();
            $table->foreignId('form_brief_id')->nullable()->constrained('form_briefs')->nullOnDelete();

            // Snapshot pihak kedua (editable untuk kebutuhan legal final)
            $table->string('pihak_kedua_nama_lengkap')->nullable();
            $table->string('pihak_kedua_nama_akun')->nullable();
            $table->string('pihak_kedua_nik')->nullable();
            $table->text('pihak_kedua_alamat')->nullable();

            // Snapshot campaign/brief
            $table->string('nama_campaign')->nullable();
            $table->text('sow_disepakati')->nullable();
            $table->string('timeline_kerja_sama')->nullable();

            // Pembayaran
            $table->decimal('nominal_kesepakatan', 15, 2)->nullable();
            $table->string('nominal_terbilang')->nullable();
            $table->string('atas_nama_rekening')->nullable();
            $table->string('nomor_rekening')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('kantor_cabang_bank')->nullable();
            $table->string('termin_pembayaran_1')->nullable();
            $table->string('termin_pembayaran_2')->nullable();

            $table->enum('status', ['draft', 'active', 'signed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_s_p_k_s');
    }
};
