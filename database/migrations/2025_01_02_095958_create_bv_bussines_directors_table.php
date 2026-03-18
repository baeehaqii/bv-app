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
        Schema::create('bv_bussines_directors', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('alamat_email')->unique();
            $table->string('no_wa');
            $table->date('tanggal_gabung')->nullable();
            $table->json('list_sales')->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_bussines_directors');
    }
};
