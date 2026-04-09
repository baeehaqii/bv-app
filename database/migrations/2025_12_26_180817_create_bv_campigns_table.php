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

            // Sales relationship
            $table->foreignId('bv_sales_id')->nullable()->constrained('bv_sales')->nullOnDelete();

            // Form Brief relationship (FB-04)
            $table->foreignId('form_brief_id')->nullable()->constrained('form_briefs')->nullOnDelete();

            // Client/Brand relationship
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // Client Type (CP-01)
            $table->string('client_type')->default('direct')->comment('direct atau agency');
            $table->string('agency_name')->nullable()->comment('Nama agency jika client_type=agency');

            // Informasi Dasar Campaign
            $table->string('campaign_name');
            $table->text('campaign_description')->nullable();
            $table->string('campaign_image')->nullable();

            // Bulan & Tanggal Campaign (CP-03)
            $table->unsignedTinyInteger('campaign_month')->nullable()->comment('Bulan campaign 1-12');
            $table->date('campaign_date')->nullable()->comment('Tanggal spesifik campaign');

            // Media platforms (JSON array)
            $table->json('media_platforms')->nullable();

            // Campaign type
            $table->string('campaign_type')->default('regular');

            // Deal Value & Close Date berdekatan (CP-04)
            $table->decimal('deal_value', 18, 2)->default(0);
            $table->date('close_date')->nullable();

            // Total cost
            $table->decimal('total_cost', 15, 2)->default(0);

            // Tanggal Dapat Brief (CP-05)
            $table->date('brief_received_date')->nullable()->comment('Tanggal menerima brief dari client');

            // Penjadwalan
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Status Operasional
            $table->enum('status', ['draft', 'upcoming', 'ongoing', 'completed', 'cancelled'])->default('draft');

            // Retrieve options
            $table->string('retrieve_option')->nullable();
            $table->string('retrieve_template')->nullable();

            // Penanggung Jawab (PIC)
            $table->string('pic_internal')->nullable();

            // PIC Media Plan Internal (CP-06)
            $table->string('pic_media_plan')->nullable()->comment('PIC untuk media plan internal');

            // Tracking Link / Progress
            $table->string('report_link')->nullable();
            $table->integer('progress_percentage')->default(0);

            $table->text('brief_summary')->nullable();
            $table->json('client_brief_files')->nullable();
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
