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
        Schema::create('bv_cashflows', function (Blueprint $table) {
            $table->id();

            // Informasi Utama
            $table->date('transaction_date')->index(); // Tanggal transaksi (kunci untuk monitoring periode)
            $table->enum('type', ['income', 'expense']); // Jenis: Pemasukan atau Pengeluaran
            $table->decimal('amount', 15, 2); // Nominal uang (mendukung triliunan dengan 2 desimal)

            // Kategorisasi & Relasi
            $table->string('category')->index(); // Contoh: Gaji, Operasional, Marketing, Penjualan
            $table->string('reference_no')->nullable(); // No Invoice atau No Kwitansi

            // Deskripsi
            $table->text('description')->nullable(); // Keterangan tambahan

            // Metadata (Opsional tapi berguna)
            $table->string('payment_method')->default('transfer'); // cash, transfer, e-wallet
            $table->string('attachment')->nullable(); // Path untuk upload bukti transaksi

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_cashflows');
    }
};
