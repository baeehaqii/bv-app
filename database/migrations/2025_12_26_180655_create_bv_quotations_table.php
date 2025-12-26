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
        Schema::create('bv_quotations', function (Blueprint $table) {
            $table->id();

            // Identitas Quotation
            $table->string('quotation_number')->unique(); // BV/Q/27/12/25/001
            $table->date('quotation_date');
            $table->date('expiry_date')->nullable(); // Masa berlaku penawaran

            // Informasi Klien (Bisa diganti foreignId jika ada tabel clients)
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->text('client_address')->nullable();

            // Finansial
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            // Status & Keterangan
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->text('notes')->nullable(); // Catatan internal
            $table->text('terms_conditions')->nullable(); // Syarat & Ketentuan untuk klien

            // Metadata
            $table->foreignId('user_id')->constrained(); // Siapa yang membuat quotation ini
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_quotations');
    }
};
