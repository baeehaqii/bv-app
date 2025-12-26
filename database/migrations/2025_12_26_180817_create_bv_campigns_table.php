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
        Schema::create('bv_campaigns', function (Blueprint $table) {
            $table->id();

            // Informasi Dasar Campaign
            $table->string('campaign_name');
            $table->string('client_name')->index(); // Untuk pencarian cepat

            // Penjadwalan
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Status Operasional
            // 'waiting' = antrian setelah bayar, 'running' = sedang jalan, 'paused' = berhenti sementara
            $table->enum('status', ['waiting', 'running', 'paused', 'completed', 'cancelled'])->default('waiting');

            // Penanggung Jawab (PIC)
            $table->string('pic_internal')->nullable(); // Nama tim yang handle

            // Tracking Link / Progress
            $table->string('report_link')->nullable(); // Link Google Data Studio / Spreadsheet report
            $table->integer('progress_percentage')->default(0); // 0 - 100%

            $table->text('brief_summary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_campaigns');
    }
};
