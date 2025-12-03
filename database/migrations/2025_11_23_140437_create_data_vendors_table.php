<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('nama_vendor');
            $table->string('email_vendor');
            $table->string('nama_pic');
            $table->string('no_ktp_pic');
            $table->string('role_pic');
            $table->string('email_pic');
            $table->date('tanggal_registrasi');
            $table->string('status');
            $table->text('catatan');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_vendors');
    }
};
