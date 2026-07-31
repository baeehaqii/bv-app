<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice / piutang usaha (AR). Satu invoice = satu tagihan = satu termin;
 * pembayaran bertahap dibuat sebagai invoice termin terpisah (DP, pelunasan),
 * bukan cicilan di dalam satu invoice.
 *
 * Piutang = amount - paid_amount untuk invoice yang belum void.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bv_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();

            $table->foreignId('bv_quotation_id')->nullable()->constrained('bv_quotations')->nullOnDelete();
            $table->foreignId('data_client_id')->nullable()->constrained('data_clients')->nullOnDelete();
            $table->string('client_name');

            $table->string('term_label')->nullable(); // mis. "DP 50%", "Pelunasan"
            $table->decimal('amount', 15, 2);
            $table->date('issue_date');
            $table->date('due_date');

            $table->string('status', 20)->default('draft')->index(); // draft|sent|partially_paid|paid|void
            $table->date('paid_at')->nullable();
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->foreignId('finance_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bv_invoices');
    }
};
