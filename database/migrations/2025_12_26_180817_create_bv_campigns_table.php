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

            // Client/Brand relationship (foreign key removed temporarily - add later when data_clients exists)
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // Informasi Dasar Campaign
            $table->string('campaign_name');
            $table->text('campaign_description')->nullable();
            $table->string('campaign_image')->nullable();

            // Media platforms (JSON array)
            $table->json('media_platforms')->nullable();

            // Campaign type
            $table->string('campaign_type')->default('regular');

            // Total cost
            $table->decimal('total_cost', 15, 2)->default(0);

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

            // Tracking Link / Progress
            $table->string('report_link')->nullable();
            $table->integer('progress_percentage')->default(0);

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
