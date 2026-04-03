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
        Schema::create('bv_sales_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Akun user yang terhubung ke sales person ini (untuk akses personal target)');
            $table->foreignId('bv_bussines_director_id')->nullable()->constrained('bv_bussines_directors')->nullOnDelete();
            $table->string('nama_sales');
            $table->string('alamat')->nullable();
            $table->string('tanggal_gabung_bv')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_sales_lists');
    }
};
