<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracking pembayaran KOL (Campaign Ongoing Internal) — acuan sheet "OFERO".
 * Di-anchor ke (campaign_id + kol_name) agar data bayar tahan terhadap re-sync KOL
 * (BvCampaignKol di-hapus & dibuat ulang saat InternalBudget::syncCampaignKolsFromApprovedBudget).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_kol_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                ->constrained('bv_campaigns')
                ->cascadeOnDelete();

            // Pointer lunak ke baris KOL aktif; null saat KOL di-wipe, di-relink ulang saat sync.
            $table->foreignId('bv_campaign_kol_id')
                ->nullable()
                ->constrained('bv_campaign_kols')
                ->nullOnDelete();

            // Identitas KOL (snapshot, kunci natural per-campaign)
            $table->string('kol_name');
            $table->string('username')->nullable();
            $table->string('platform')->nullable();
            $table->string('pic')->nullable();

            // Data pencairan
            $table->text('alamat')->nullable();
            $table->string('ktp')->nullable();
            $table->string('npwp')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('nomor_rekening')->nullable();
            $table->string('nama_rekening')->nullable();

            // Dokumen & kesepakatan
            $table->string('link_spk', 2048)->nullable();
            $table->string('link_invoice', 2048)->nullable();
            $table->text('detail_sow')->nullable();
            $table->string('est_timeline')->nullable();
            $table->string('paying_agreement')->nullable();

            // Nominal & status bayar
            $table->decimal('real_cost', 15, 2)->default(0);
            $table->decimal('cost_tax', 15, 2)->default(0);
            $table->string('payment_status', 32)->default('waiting_payment');
            $table->string('payment_schedule')->nullable();
            $table->date('invoice_date_received')->nullable();
            $table->string('link_bukti_transfer', 2048)->nullable();

            $table->timestamps();

            $table->unique(['campaign_id', 'kol_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_kol_payments');
    }
};
